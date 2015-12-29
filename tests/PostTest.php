<?php

namespace MyImoutoTest;

use MyImouto\User;
use MyImouto\Post;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PostTest extends TestCase
{
    use DatabaseMigrations;
    
    public function testCreate()
    {
        $filePath = base_path() . '/tests/files/medium_image.png';
        $copyPath = storage_path() . '/medium_image_test.png';
        
        copy($filePath, $copyPath);
        
        $fileMd5 = md5_file($filePath);
        
        $uploadFile = new UploadedFile(
            $copyPath,
            pathinfo($copyPath, PATHINFO_BASENAME),
            'image/png',
            filesize($copyPath),
            UPLOAD_ERR_OK,
            true
        );
        
        $admin = factory(User::class, 'admin')->create();
        
        $response = $this
            ->actingAs($admin)
            ->call(
                'POST',
                'posts/store',
                [
                    'tag_string' => 'foo bar'
                ],
                [],
                ['upload' => $uploadFile]
            );
        
        $json = json_decode($response->content(), true);
        
        $this->assertTrue(!empty($json['success']));
        
        $post = Post::orderBy('id', 'DESC')->limit(1)->first();
        
        $this->assertEquals('bar foo', $post->tag_string);

        $post->deleteFiles();
    }
}
