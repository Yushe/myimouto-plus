<?php

namespace MyImouto\Post;

use DB;
use DateTime;
use MyImouto\Tag;
use MyImouto\Post;
use MyImouto\Post\Query;
use MyImouto\Post\QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class QueryBuilder
{
    protected function processRelatedTags(Query $query, EloquentBuilder $sqlQuery)
    {
        if (!$query->tags['related']) {
            return;
        }
        
        $where = [];
        
        foreach ($query->tags['related'] as $k => $relatedTag) {
            $k++;
            
            $ptAlias = 'pt' . $k;
            $tAlias  = 't'  . $k;
            
            $sqlQuery->join(
                'posts_tags as ' . $ptAlias,
                'posts.id', '=', $ptAlias . '.post_id'
            );
            $sqlQuery->join(
                'tags as ' . $tAlias,
                $ptAlias. '.tag_id', '=', $tAlias . '.id'
            );
            
            $where[$tAlias . '.name'] = $relatedTag;
        }
        
        $sqlQuery->where($where);
    }
    
    protected function processExcludeTags(Query $query, EloquentBuilder $sqlQuery)
    {
        if (!$query->tags['exclude']) {
            return;
        }
        
        $sqlQuery->whereNotExists(function ($q) use ($query) {
            $q
                ->from('posts_tags as pt')
                ->join('tags as t', 'pt.tag_id', '=', 't.id')
                ->where('posts.id', $q->raw($q->getGrammar()->wrapTable('pt.post_id')))
                ->whereIn('t.name', $query->tags['exclude']);
        });
    }
    
    protected function processIncludeTags(Query $query, EloquentBuilder $sqlQuery)
    {
        if (!$query->tags['include']) {
            return;
        }
        
        $sqlQuery
            ->join('posts_tags as pti', 'posts.id', '=', 'pti.post_id')
            ->join('tags as ti', 'pti.tag_id', '=', 'ti.id')
            ->whereIn('ti.name', $query->tags['include']);
    }
    
    protected function processWildcards(Query $query, EloquentBuilder $sqlQuery)
    {
        if (!$query->tags['wildcards']) {
            return;
        }
        
        $tags = [];
        
        foreach ($query->tags['wildcards'] as $tag) {
            $matches = Tag
                ::where('name', 'LIKE', 'fo%')
                ->select('name', 'post_count')
                ->orderBy("post_count DESC")
                ->limit(config('myimouto.conf.tag_query_limit'))
                ->lists('name')
                ->toArray();
            
            $tags = array_merge($tags, $matches);
        }
        
        if (!$tags) {
            $tags = [ '~no_matches~' ];
        }
        
        $query->tags['include'] = array_merge($query->tags['include'], $tags);
    }
    
    /**
     * @see http://stackoverflow.com/questions/8106547/how-to-search-on-mysql-using-joins
     */
    public function build(Query $query): EloquentBuilder
    {
        $sqlQuery = Post::query();
        
        $this->addRangeCondition($query->id, 'posts.id', $sqlQuery);
        $this->addRangeCondition($query->mPixels, 'posts.width * posts.height / 1000000.0', $sqlQuery);
        $this->addRangeCondition($query->width, 'posts.width', $sqlQuery);
        $this->addRangeCondition($query->height, 'posts.height', $sqlQuery);
        $this->addRangeCondition($query->score, 'posts.score', $sqlQuery);
        $this->addRangeCondition($query->date, 'posts.created_at', $sqlQuery);
        
        if ($query->md5) {
            $sqlQuery->where('posts.md5', $query->md5);
        }
        
        // ...etc
        
        $this->processWildcards($query, $sqlQuery);
        $this->processIncludeTags($query, $sqlQuery);
        $this->processRelatedTags($query, $sqlQuery);
        $this->processExcludeTags($query, $sqlQuery);

        if (!$query->order) {
            $sqlQuery->groupBy('posts.id');
        }
        
        return $sqlQuery;
    }
    
    protected function addRangeCondition(array $condition, $field, EloquentBuilder $sqlQuery)
    {
        if (!$condition) {
            return;
        }
        
        switch ($condition[0]) {
            case 'eq':
                if ($condition[1] instanceof DateTime) {
                    $sqlQuery->whereBetween(
                        $field,
                        [
                            strtotime('mightnight', $condition[1]->format('U')),
                            strtotime('tomorrow', $condition[1]->format('U'))
                        ]
                    );
                } else {
                    $sqlQuery->where($field, $condition[1]);
                }
                
                break;
            
            case 'gt':
                $sqlQuery->where($field, '>', $condition[1]);
                
                break;
            
            case 'gte':
                $sqlQuery->where($field, '>=', $condition[1]);
                
                break;
            
            case 'lt':
                $sqlQuery->where($field, '<', $condition[1]);
                
                break;
            
            case 'lte':
                $sqlQuery->where($field, '<=', $condition[1]);
                
                break;
            
            case 'in':
                $sqlQuery->whereIn($field, $condition[1]);
                
                break;
            
            case 'between':
                $sqlQuery->whereBetween($field, $condition[1], $condition[2]);
                
                break;
        }
    }
}
