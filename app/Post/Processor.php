<?php

namespace MyImouto\Post;

use Imagick;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use InvalidArgumentException;
use MyImouto\Post;
use MyImouto\User;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\MessageBag;
use finfo;

class Processor
{
    const EVENT_NAME = 'validating';
    
    const EVENTS = [
        'ensureTempfileExists',
        'determineContentType',
        'convertPng',
        'generateHash',
        'setImageDimensions',
        'setPostStatus',
        'checkPendingCount',
        'generateSample',
        'generateJpeg',
        'generatePreview',
        'moveFiles',
    ];
    
    /**
     * Supported mime types. Could be moved to
     * config so more types can be supported.
     */
    const MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif'
    ];
    
    /**
     * Using "setImageFormat('jpg')" isn't enough to
     * convert a PNG to JPG, so we have to composite them.
     */
    public static function pngToJpg(Imagick $png): Imagick
    {
        $jpg = new Imagick();
        
        $jpg->newImage(
            $png->getImageWidth(),
            $png->getImageHeight(),
            sprintf(
                'rgb(%d, %d, %d)',
                config('myimouto.conf.bgcolor')[0],
                config('myimouto.conf.bgcolor')[1],
                config('myimouto.conf.bgcolor')[2]
            )
        );
        
        $jpg->compositeImage($png, Imagick::COMPOSITE_OVER, 0, 0);
        $jpg->setImageFormat('jpg');
        $jpg->setImageCompressionQuality(config('myimouto.conf.jpeg_quality'));
        
        return $jpg;
    }
    
    /**
     * @var Illuminate\Events\Dispatcher
     */
    protected $dispatcher;
    
    /**
     * @var Illuminate\Support\MessageBag
     */
    protected $errors;
    
    /**
     * @var MyImouto\Post
     */
    protected $post;
    
    /**
     * @var Symfony\Component\HttpFoundation\File\File
     */
    protected $file;
    
    /**
     * @var string
     */
    protected $mimeType;
    
    /**
     * @param UploadedFile|File|string $file
     */
    public function __construct(Post $post, $file)
    {
        if (is_string($file)) {
            $file = new File($file);
        } elseif (!$file instanceof UploadedFile && !$file instanceof File) {
            throw new InvalidArgumentException('Invalid $file argument');
        }
        
        $this->post = $post;
        
        $this->file = $file;
        
        $this->errors = new MessageBag();
        
        $this->initDispatcher();
    }
    
    public function errors()
    {
        return $this->errors;
    }
    
    /**
     * Starts the processing.
     *
     * return @void
     */
    public function process()
    {
        $this->dispatcher->until(self::EVENT_NAME);
    }
    
    /**
     * Deletes all files, both temporal and processed, so
     * this should be used only if the processing failed.
     * The original file isn't deleted because A. it's an
     * uploaded file and it'll be deleted automatically,
     * or B. it's an imported file and shouldn't deleted
     * automatically.
     *
     * @return void
     */
    public function deleteFiles()
    {
        if (is_file($this->tempfileJpegPath())) {
            unlink($this->tempfileJpegPath());
        }
        
        if (is_file($this->tempfileSamplePath())) {
            unlink($this->tempfileSamplePath());
        }
        
        if (is_file($this->tempfilePreviewPath())) {
            unlink($this->tempfilePreviewPath());
        }
        
        $this->post->deleteFiles();
    }
    
    protected function ensureTempfileExists()
    {
        if ($this->file instanceof UploadedFile) {
            if (!$this->file->isValid()) {
                $this->errors->add('file', $this->file->getErrorMessage());
                
                return false;
            }
        } else {
            if (!$this->file->isFile()) {
                $this->errors->add('file', "File doesn't exist");
                
                return false;
            }
        }
    }
    
    protected function determineContentType()
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $this->mimeType = $finfo->file($this->file->getRealPath());
        
        if (!isset(self::MIME_TYPES[$this->mimeType])) {
            $this->errors->add('file', 'File is an invalid type');
            
            return false;
        }
        
        $this->post->file_ext = self::MIME_TYPES[$this->mimeType];
    }
    
    protected function generateHash()
    {
        $this->post->md5 = md5_file($this->file->getRealPath());
        
        if (Post::where('md5', $this->post->md5)->exists()) {
            $this->errors()->add('md5', 'MD5 already exists');
            
            return false;
        }
    }
    
    // /**
     // * Deletes the input file only if it's an UploadFile.
     // *
     // * @return void
     // */
    // protected function deleteTempfile()
    // {
        // if ($this->file instanceof UploadedFile) {
            // unlink($this->file->getRealPath());
        // }
    // }
    
    protected function initDispatcher()
    {
        $this->dispatcher = new Dispatcher();
        
        foreach (self::EVENTS as $eventName) {
            $this->dispatcher->listen(
                self::EVENT_NAME,
                function() use ($eventName) {
                    return $this->{$eventName}();
                }
            );
        }
    }
    
    protected function setImageDimensions()
    {
        if ($this->post->isImage()/* or $this->flash()*/) {
            list($this->post->width, $this->post->height) = getimagesize($this->file->getRealPath());
        }
        $this->post->file_size = filesize($this->file->getRealPath());
    }
    
    protected function setPostStatus()
    {
        if (
            !$this->imageIsTooSmall() ||
            $this->post->user->level >= User::LEVEL_CONTRIBUTOR
        ) {
            return;
        }
        
        $this->post->status = "pending";
    }
    
    # If the image resolution is too low and the user is privileged or below, force the
    # image to pending.    If the user has too many pending posts, raise an error.
    #
    # We have to do this here, so on creation it's done after set_image_dimensions so
    # we know the size.    If we do it in another module the order of operations is unclear.
    protected function imageIsTooSmall()
    {
        if (
            !config('myimouto.conf.min_mpixels') ||
            !$this->post->width ||
            $this->post->width * $this->post->height >= !config('myimouto.conf.min_mpixels')
        ) {
            return false;
        }
        
        return true;
    }
    
    # If this post is pending, and the user has too many pending posts, reject the upload.
    # This must be done after set_image_status.
    public function checkPendingCount()
    {
        if (
            !config('myimouto.conf.max_pending_images') ||
            $this->post->status != 'pending' ||
            $this->post->user->level >= User::LEVEL_CONTRIBUTOR
        ) {
            return;
        }
        
        $pendingPosts = Post::
              where("user_id", $this->post->user->id)
            ->where('status', 'pending')
            ->count();
        
        if ($pendingPosts < config('myimouto.conf.max_pending_images')) {
            return;
        }

        $this->errors()->add('base', "You have too many posts pending moderation");
        return false;
    }
    
    public function generateSample($forceRegen = false)
    {
        if (
            !config('myimouto.conf.image_samples') ||
            ($this->post->isGif() || !$this->post->isImage())
        ) {
            return;
        }
        
        $ratio = $this->post->file_ext == 'png' ?
                    config('myimouto.conf.png_to_jpg_ratio') :
                    config('myimouto.conf.sample_ratio');
        
        $size = [
            'width' => $this->post->width,
            'height' => $this->post->height,
        ];
        
        if (config('myimouto.conf.sample_width')) {
            $maxSize = [
                'width'  => config('myimouto.conf.sample_width'),
                'height' => config('myimouto.conf.sample_height'),
            ];
            
            $size = Resizer::reduceTo($size, $maxSize, $ratio);
        }
        
        $maxSize = [
            'width'  => config('myimouto.conf.sample_max'),
            'height' => config('myimouto.conf.sample_min'),
        ];
        
        $size = Resizer::reduceTo($size, $maxSize, $ratio, false, true);
        
        $path = $this->file->getRealPath();
        
        # If we're not reducing the resolution for the sample image, only reencode if the
        # source image is above the reencode threshold.    Anything smaller won't be reduced
        # enough by the reencode to bother, so don't reencode it and save disk space.
        if (
            $size['width']  == $this->post->width &&
            $size['height'] == $this->post->height &&
            filesize($path) < config('myimouto.conf.sample_always_generate_size')
        ) {
            $this->post->sample_width = null;
            
            $this->post->sample_height = null;
            
            return;
        }
        
        # If we already have a sample image, and the parameters havn't changed,
        # don't regenerate it.
        if (
            $this->post->hasSample() && !$forceRegen &&
            (
                $size['width'] == $this->post->sample_width &&
                $size['height'] == $this->post->sample_height
            )
        ) {
            return;
        }
        
        try {
            Resizer::resize(
                $this->post->file_ext,
                $path,
                $this->tempfileSamplePath(),
                $size,
                config('myimouto.conf.sample_quality')
            );
        } catch (\RuntimeException $e) {
            $this->errors()->add('sample', 'Sample couldn\'t be created: '. $e->getMessage());
            
            return false;
        }
        
        $this->post->sample_width  = $size['width'];
        $this->post->sample_height = $size['height'];
        $this->post->sample_size   = filesize($this->tempfileSamplePath());
    }
    
    # Only generate JPEGs for PNGs.
    # Don't do it for files that are already JPEGs; we'll just add
    # artifacts and/or make the file bigger.
    # Don't do it for GIFs; they're usually animated.
    protected function generateJpeg($forceRegen = false)
    {
        if (
            $this->post->file_ext != 'png' ||
            config('myimouto.conf.png_process') != 'sample' ||
            ($this->post->isGif() || !$this->post->isImage()) ||
            (!$this->post->width && !$this->post->height)
        ) {
            return;
        }
        
        $path = $this->file->getRealPath();
        
        # If we already have the image, don't regenerate it.
        if (!$forceRegen && ctype_digit((string)$this->post->jpeg_width)) {
            return;
        }
        
        $size = Resizer::reduceTo(
            [
                'width' => $this->post->width,
                'height' => $this->post->height
            ],
            [
                'width' => config('myimouto.conf.jpeg_width'),
                'height' => config('myimouto.conf.jpeg_height')
            ],
            config('myimouto.conf.jpeg_ratio')
        );
        
        try {
            Resizer::resize(
                $this->post->file_ext,
                $path,
                $this->tempfileJpegPath(),
                $size,
                config('myimouto.conf.jpeg_quality')['max']
            );
        } catch (\RuntimeException $e) {
            $this->errors()->add("jpeg", "JPEG couldn't be created: {$e->getMessage()}");
            return false;
        }
        
        $this->post->jpeg_width  = $size['width'];
        $this->post->jpeg_height  = $size['height'];
        $this->post->jpeg_size    = filesize($this->tempfileJpegPath());
    }
    
    protected function generatePreview($forceRegen = false)
    {
        if (
            (!$this->post->isImage() || (!$this->post->width && !$this->post->height)) //||
            // (is_file($this->post->previewPath()) && !$forceRegen)
        ) {
            return;
        }
        
        $size = Resizer::reduceTo(
            [
                'width' => $this->post->width,
                'height' => $this->post->height
            ],
            [
                'width' => config('myimouto.conf.max_preview_width'),
                'height' => config('myimouto.conf.max_preview_height')
            ]
        );

        # Generate the preview from the new sample if we have one to save CPU, otherwise from the image.
        if (is_file($this->tempfileSamplePath())) {
            $path = $this->tempfileSamplePath();
            $ext = 'jpg';
        } elseif (is_file($this->post->samplePath())) {
            $path = $this->post->samplePath();
            $ext = 'jpg';
        } elseif (is_file($this->file->getRealPath())) {
            $path = $this->file->getRealPath();
            $ext = $this->post->file_ext;
        } elseif (is_file($this->post->filePath())) {
            $path = $this->post->filePath();
            $ext = $this->post->file_ext;
        } else {
            return false;
        }
        
        try {
            Resizer::resize(
                $ext,
                $path,
                $this->tempfilePreviewPath(),
                $size,
                config('myimouto.conf.preview_quality')
            );
        } catch (RuntimeException $e) {
            $this->errors()->add("preview", "Preview couldn't be generated: ".$e->getMessage());
            
            return false;
        }
        
        $this->post->preview_width  = $size['width'];
        $this->post->preview_height = $size['height'];
    }
    
    protected function tempfileJpegPath()
    {
        return storage_path() . '/' . $this->post->md5 . '-jpeg.jpg';
    }
    
    protected function tempfileSamplePath()
    {
        return storage_path() . '/' . $this->post->md5 . '-sample.jpg';
    }
    
    protected function tempfilePreviewPath()
    {
        return storage_path() . '/' . $this->post->md5 . '-preview.jpg';
    }
    
    protected function convertPng()
    {
        if (
            $this->post->file_ext == 'png' &&
            config('myimouto.conf.png_process') == 'discard'
        ) {
            $this->post->file_ext = 'jpg';
            
            $jpg = static::pngToJpg(new Imagick($this->file->getRealPath()));
            
            $fh = fopen($this->file->getRealPath(), 'w+');
            
            $jpg->writeImageFile($fh);
            
            fclose($fh);
        }
    }
    
    protected function moveFiles()
    {
        $this->createDir($this->post->filePath());
        
        $dirPath = pathinfo($this->post->filePath(), PATHINFO_DIRNAME);
        
        $this->file->move($dirPath, $this->post->fileName());
        
        if ($this->post->isImage() && is_file($this->tempfilePreviewPath())) {
            $this->createDir($this->post->previewPath());
            
            rename($this->tempfilePreviewPath(), $this->post->previewPath());
        }

        if (is_file($this->tempfileSamplePath())) {
            $this->createDir($this->post->samplePath());
            
            rename($this->tempfileSamplePath(), $this->post->samplePath());
        }

        if (is_file($this->tempfileJpegPath())) {
            $this->createDir($this->post->jpegPath());
            
            rename($this->tempfileJpegPath(), $this->post->jpegPath());
        }
    }
    
    protected function createDir($dir)
    {
        $dirPath = pathinfo($dir, PATHINFO_DIRNAME);
        
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }
    }
}
