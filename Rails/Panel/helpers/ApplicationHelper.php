<?php
class ApplicationHelper extends Rails\ActionView\Helper
{
    public function linkTo($link, $url_params, array $attrs = array())
    {
        if ($url_params == 'root') {
            return $this->base()->linkTo($link, $url_params, $attrs);
        } else {
            $base_path = Rails::application()->router()->basePath();
            $attrs['href'] = $base_path . '/' . Rails::application()->config()->rails_panel_path . '/' . substr($url_params, 1);
            return $this->contentTag('a', $link, $attrs);
        }
    }
}