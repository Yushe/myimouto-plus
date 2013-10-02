<?php
namespace Rails\ActionView\Helper\WillPaginate;

use Rails;
use Rails\ActiveRecord\Collection;

abstract class AbstractRenderer
{
    protected $collection;
    
    protected $options;
    
    protected $helper;
    
    protected $pages;
    
    protected $page;
    
    protected $url;
    
    public function __construct($helper, Collection $collection, array $options = [])
    {
        $this->collection = $collection;
        $this->helper     = $helper;
        $this->options    = array_merge($this->defaultOptions(), $options);
    }
    
    public function toHtml()
    {
        $html = implode(array_map(function($item){
            if (is_int($item))
                return $this->pageNumber($item);
            else
                return $this->$item();
        }, $this->pagination()), $this->options['link_separator']);
        
        return $this->options['container'] ? $this->htmlContainer($html) : $html;
    }
    
    protected function defaultOptions()
    {
        return [
            'previous_label' => '&#8592; ' . $this->helper->t('actionview.helper.will_paginate.previous'),
            'next_label'     => $this->helper->t('actionview.helper.will_paginate.next') . ' &#8594;',
            'container'      => true,
            'link_separator' => ' '
        ];
    }
    
    protected function pageNumber($page)
    {
        if ($page != $this->collection->currentPage())
            return $this->link($page, $page, ['rel' => $this->relValue($page)]);
        else
            return $this->tag('span', $page, ['class' => 'current']);
    }
    
    protected function gap()
    {
        return '<span class="gap">&hellip;</span>';
    }
    
    protected function previousPage()
    {
        $num = $this->collection->currentPage() > 1 ?
                    $this->collection->currentPage() - 1 : false;
        return $this->previousOrNextPage($num, $this->options['previous_label'], 'previousPage');
    }
    
    protected function nextPage()
    {
        $num = $this->collection->currentPage() < $this->collection->totalPages() ?
                    $this->collection->currentPage() + 1 : false;
        return $this->previousOrNextPage($num, $this->options['next_label'], 'nextPage');
    }
    
    protected function previousOrNextPage($page, $text, $classname)
    {
        if ($page)
            return $this->link($text, $page, ['class' => $classname]);
        else
            return $this->tag('span', $text, ['class' => $classname . ' disabled']);
    }
    
    protected function htmlContainer($html)
    {
        return $this->tag('div', $html, $this->containerAttributes());
    }
    
    protected function containerAttributes()
    {
        return ['class' => 'pagination'];
    }
    
    protected function relValue($page)
    {
        if ($this->collection->currentPage() - 1 == $page)
            return 'prev' . ($page == 1 ? ' start' : '');
        elseif ($this->collection->currentPage() + 1 == $page)
            return 'next';
        elseif ($page == 1)
            return 'start';
    }
    
    protected function pagination()
    {
        $pages = $this->collection->totalPages();
        $page  = $this->collection->currentPage();
        $pagination = [];
        
        $pagination[] = 'previousPage';
        $pagination[] = 1;
        
        if ($pages < 10){
            for ($i = 2; $i <= $pages; $i++){
                $pagination[] = $i;
            }
        } elseif ($page > ($pages - 4)) {
            $pagination[] = 'gap';
            for ($i = ($pages - 4); $i < ($pages); $i++) {
                $pagination[] = $i;
            }
        } elseif ($page > 4) {
            $pagination[] = 'gap';
            for ($i = ($page - 1); $i <= ($page + 2); $i++) {
                $pagination[] = $i;
            }
            $pagination[] = 'gap';
        } else {
            if ($page >= 3){
                for ($i = 2; $i <= $page+2; $i++) {
                    $pagination[] = $i;
                }
            } else {
                for ($i = 2; $i <= 5; $i++) {
                    $pagination[] = $i;
                }
            }
            $pagination[] = 'gap';
        }

        if ($pages >= 10) {
            if ($pages == $page)
                $pagination[] = $i;
            else
                $pagination[] = $pages;
        }

        $pagination[] = 'nextPage';
        
        return $pagination;
    }
    
    protected function link($text, $page, array $attrs = [])
    {
        return $this->helper->linkTo($text, array_merge(['#index'], $this->params()->query_parameters(), ['page' => $page]), $attrs);
    }
    
    protected function tag($type, $content, array $attrs = [])
    {
        return $this->helper->contentTag($type, $content, $attrs);
    }
    
    protected function params()
    {
        return Rails::application()->dispatcher()->parameters();
    }
}