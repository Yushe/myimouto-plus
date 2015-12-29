<?php

namespace MyImoutoTest\Post;

use MyImoutoTest\TestCase;
use ReflectionClass;
use MyImouto\Post as PostModel;
use MyImouto\Post\TagProcessor;

class TagProcessorTest extends TestCase
{
    public function testNormalizeTags()
    {
        $post       = new PostModel();
        $processor  = new TagProcessor($post);
        
        $expectedValue = [ config('myimouto.conf.default_tag') ];
        
        $refl   = new ReflectionClass(TagProcessor::class);
        $method = $refl->getMethod('normalizeTags');
        $method->setAccessible(true);
        
        $normalizedTags = $method->invokeArgs($processor, [ $post ]);
        
        $this->assertEquals($expectedValue, $normalizedTags);
    }
    
    public function testDefaultTag()
    {
        $defaultTag = config('myimouto.conf.default_tag');
        
        $post = new PostModel();
        
        $processor = new TagProcessor($post);
        
        $processor->parseTags($post);
        
        $this->assertEquals($defaultTag, $post->tag_string);
    }
    
    public function testCustomTags()
    {
        // $defaultTag = config('myimouto.conf.default_tag');
        
        $post = new PostModel();
        
        $post->tag_string = 'foo bar';
        
        $processor = new TagProcessor($post);
        
        $processor->parseTags($post);
        
        $this->assertEquals('bar foo', $post->tag_string);
    }
}
