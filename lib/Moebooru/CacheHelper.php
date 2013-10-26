<?php
namespace Moebooru;

class CacheHelper
{
    static public function expire(array $options = [])
    {
        \Rails::cache()->write('$cache_version', time());
    }
    
    static public function expire_tag_version()
    {
        # $tag_version is bumped when the type of a tag is changed in Tags, if
        # a new tag is created, or if a tag's post_count becomes nonzero.
        \Rails::cache()->write('$tag_version', time());
    }
}
