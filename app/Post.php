<?php

namespace MyImouto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes, Traits\ModelEventTrigger, Traits\BelongsToUser;
    
    const IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'gif',
        'png'
    ];
    
    const RATINGS = [ 's', 'q', 'e' ];
    
    protected static $triggerEvents = [
        'creating',
        'created',
        'updating',
        'updated',
        'saving',
        'saved',
        'deleting',
        'deleted',
        'restoring',
        'restored',
    ];
    
    public $oldTags;
    
    public $newTags;
    
    public $oldCachedTags;
    
    protected $dates = [ 'deleted_at' ];
    
    public function isGif()
    {
        return $this->file_ext == 'gif';
    }
    
    public function isImage()
    {
        return in_array($this->file_ext, self::IMAGE_EXTENSIONS);
    }
    
    public function fileName()
    {
        return $this->md5 . "." . $this->file_ext;
    }
    
    public function filePath()
    {
        return public_path() .
                '/data/image/' .
                $this->fileHierarchy() . '/' .
                $this->fileName();
    }
    
    public function samplePath()
    {
        return public_path() . '/data/sample/' .
                $this->fileHierarchy() . '/' .
                config('myimouto.conf.sample_filename_prefix') .
                $this->md5 . '.jpg';
    }
    
    public function previewPath()
    {
        if ($this->isImage()) {
            return public_path() . '/data/preview/' .
                    $this->fileHierarchy() . '/' .
                    $this->md5 . '.jpg';
        } else {
            return public_path() . 'download-preview.png';
        }
    }
    
    public function jpegPath()
    {
        return public_path() . '/data/jpeg/' .
                $this->fileHierarchy() . '/' .
                $this->md5 . '.jpg';
    }

    public function fileUrl()
    {
        if (config('myimouto.conf.use_pretty_image_urls')) {
            $path = '/image/' . $this->md5 . '/' .
                    $this->prettyFileName() . '.' . $this->file_ext;
        } else {
            $path = '/data/image/' . $this->fileName();
        }
        
        return $path;
    }
    
    public function previewUrl()
    {
        if ($this->status == 'deleted') {
            $path = '/assets/images/deleted-preview.png';
        } elseif ($this->isImage()) {
            $path = '/data/preview/' . $this->md5 . '.jpg';
        } else {
            $path = '/assets/images/download-preview.png';
        }
        
        return $path;
    }
    
    public function jpegUrl()
    {
         if (config('myimouto.conf.use_pretty_image_urls')) {
            $path = '/jpeg/' . $this->md5 . '/' .
                $this->prettyFileName() . '.jpg';
        } else {
            $path = '/data/jpeg/' . $this->md5 . '.jpg';
        }
        
        return $path;
    }

    public function sampleUrl()
    {
         if (config('myimouto.conf.use_pretty_image_urls')) {
            $path = '/sample/' . $this->md5 . '/' .
                    $this->prettyFileName(true).'.jpg';
        } else {
            $path = '/data/sample/' .
                    config('myimouto.conf.sample_filename_prefix') .
                    $this->md5 . '.jpg';
        }

        return $path;
    }
    
    public function hasSample()
    {
        return (bool)$this->sample_size;
    }
    
    public function deleteFiles()
    {
        if (is_file($this->filePath())) {
            unlink($this->filePath());
        }
        
        if (is_file($this->samplePath())) {
            unlink($this->samplePath());
        }
        
        if (is_file($this->previewPath())) {
            unlink($this->previewPath());
        }
        
        if (is_file($this->jpegPath())) {
            unlink($this->jpegPath());
        }
    }
    
    public function fileHierarchy()
    {
        return substr($this->md5, 0, 2) . '/' . substr($this->md5, 2, 2);
    }
    
    public function prettyFileName($sample = false)
    {
        # Include the post number and tags.    Don't include too many tags for posts that have too
        # many of them.
        
        # If the filename is too long, it might fail to save or lose the extension when saving.
        # Cut it down as needed.    Most tags on moe with lots of tags have lots of characters,
        # and those tags are the least important (compared to tags like artists, circles, "fixme",
        # etc).
        #
        # Prioritize tags:
        # - remove artist and circle tags last; these are the most important
        # - general tags can either be important ("fixme") or useless ("red hair")
        # - remove character tags first; 

     
        if ($sample) {
            $tags = "sample";
        } else {
            $tags = Tag::compactTags($this->cached_tags, 150);
        }
        
        # Filter characters.
        $tags = str_replace(['/', '?'], ['_', ''], $tags);

        $name = $this->id . ' ' . $tags;
        
        if (config('myimouto.conf.download_filename_prefix')) {
            $name = config('myimouto.conf.download_filename_prefix') . " " . $name;
        }
        
        return $name;
    }
    
    public function apiAttributes($urlBase = '', User $user = null)
    {
        $ret = [
            'id' => (int)$this->id,
            'tags' => $this->tags_string,
            'created_at' => $this->created_at->getTimestamp(),
            'creator_id' => (int)$this->user_id,
            'author' => $this->user->name,
            // 'source' => (string)$this->source,
            // 'score' => $this->score,
            'md5' => $this->md5,
            'file_size' => (int)$this->file_size,
            'file_url' => $this->fileUrl(),
            // 'is_shown_in_index' => (bool)$this->is_shown_in_index,
            'preview_url' => $this->previewUrl(),
            'preview_width' => $this->preview_width,
            'preview_height' => $this->preview_height,
            'sample_url' => $this->sampleUrl(),
            'sample_width' => (int)($this->sample_width ?: $this->width),
            'sample_height' => (int)($this->sample_height ?: $this->height),
            'sample_file_size' => (int)$this->sample_size,
            // 'jpeg_url' => $this->jpeg_url(),
            // 'jpeg_width' => (int)($this->jpeg_width ?: $this->width),
            // 'jpeg_height' => (int)($this->jpeg_height ?: $this->height),
            // 'jpeg_file_size' => (int)$this->jpeg_size,
            'rating' => $this->rating,
            // 'has_children' => (bool)$this->has_children,
            // 'parent_id' => (int)$this->parent_id ?: null,
            'status' => $this->status,
            'width' => (int)$this->width,
            'height' => (int)$this->height,
            // 'is_held' => (bool)$this->is_held,
            // 'frames_pending_string' => '', //$this->frames_pending,
            // 'frames_pending' => [], //$this->frames_api_data($this->frames_pending),
            // 'frames_string' => '', //$this->frames,
            // 'frames' => [] //frames_api_data(frames)
        ];
        
        if ($this->status == "deleted") {
            unset($ret['sample_url']);
            unset($ret['jpeg_url']);
            unset($ret['file_url']);
        }

        if (
            $user &&
            (
                $this->status == "flagged" ||
                $this->status == "deleted" ||
                $this->status == "pending"
            ) &&
            $this->flag_detail
        ) {
            $ret['flag_detail'] = $this->flag_detail->apiAttributes();
            
            $this->flag_detail->hide_user =
                    $this->status == "deleted" &&
                    $user->level >= User::LEVEL_MODERATOR;
        }
        
        # For post/similar results:
        if ($this->similarity) {
            $ret['similarity'] = $this->similarity;
        }
        
        return $ret;
    }
}
