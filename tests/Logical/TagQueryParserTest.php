<?php

namespace MyImoutoTest\Logical;

use MyImoutoTest\TestCase;
use MyImouto\Logical\TagQueryParser;

class TagQueryParserTest extends TestCase
{
    public function testParse()
    {
        $parser = new TagQueryParser();
        
        $tagQuery = 'foo -bar ~baz fo* user:123 user:admin limit:1000 s';
        
        $query = $parser->parse($tagQuery);
        
        $this->assertEquals($query->tags['related'], ['foo']);
        $this->assertEquals($query->tags['exclude'], ['bar']);
        $this->assertEquals($query->tags['include'], ['baz']);
        $this->assertEquals($query->tags['wildcards'], ['fo*']);
        $this->assertEquals($query->userId, 123);
        $this->assertEquals($query->userName, 'admin');
        $this->assertEquals($query->limit, 1000);
        $this->assertEquals($query->rating, 's');
        
        // Ranges
        
        $tagQuery = 'rating:s id:10..20 width:<900 height:<=900 score:10';
        
        $query = $parser->parse($tagQuery);
        
        $this->assertEquals($query->rating, 's');
        $this->assertEquals($query->id, [ 'between', 10, 20 ]);
        $this->assertEquals($query->width, [ 'lt', 900 ]);
        $this->assertEquals($query->height, [ 'lte', 900 ]);
        $this->assertEquals(['eq', 10], $query->score);
        
        // More ranges
        
        $tagQuery = 'id:10,20,30 width:>900 height:>=900 mpixels:2.5 score:?30';
        
        $query = $parser->parse($tagQuery);
        
        $this->assertEquals(['in', [ 10, 20, 30 ]], $query->id);
        $this->assertEquals(['gt', 900], $query->width);
        $this->assertEquals(['gte', 900], $query->height);
        $this->assertEquals(['eq', 2.5], $query->mPixels);
        $this->assertEquals(['eq', 0], $query->score);
    }
}
