<?php

namespace MyImoutoTest\Post;

use DB;
use MyImoutoTest\TestCase;
use MyImouto\User;
use MyImouto\Post\QueryBuilder;
use MyImouto\Logical\TagQueryParser;
use MyImouto\Post\Query as PostQuery;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class QueryBuilderTest extends TestCase
{
    use DatabaseMigrations;
    
    public function testParse()
    {
        DB::enableQueryLog();
        
        $queryBuilder = new QueryBuilder();
        
        $this->createPost('medium_image', 'png', 'foo bar');
        $this->createPost('large_image', 'png', 'bar baz');
        
        $all        = $queryBuilder->build($this->createQuery(''))->get();
        $onePost    = $queryBuilder->build($this->createQuery('foo bar'))->get();
        $twoPosts   = $queryBuilder->build($this->createQuery('bar'))->get();
        $noFoo      = $queryBuilder->build($this->createQuery('-foo'))->get();
        $wildcard   = $queryBuilder->build($this->createQuery('fo*'))->get();
        
        $this->assertEquals(2, $all->count());
        $this->assertEquals(2, $twoPosts->count());
        $this->assertEquals(1, $onePost->count());
        $this->assertEquals(1, $noFoo->count());
        $this->assertEquals(1, $wildcard->count());
    }
    
    protected function createQuery(string $tags): PostQuery
    {
        return (new TagQueryParser())->parse($tags);
    }
    
    protected function createPost($fileName, $fileExt, $tags)
    {
        $filePath = base_path() . '/tests/files/' . $fileName . '.' . $fileExt;
        $copyPath = storage_path() . '/' . $fileName . '_test.' . $fileExt;
        
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
                    'tag_string' => $tags
                ],
                [],
                ['upload' => $uploadFile]
            );
        
        $json = json_decode($response->content(), true);
        
        $this->assertTrue(!empty($json['success']));
    }
}
