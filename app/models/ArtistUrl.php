<?php
class ArtistUrl extends Rails\ActiveRecord\Base
{
    static public function tableName()
    {
        return 'artists_urls';
    }
    
    protected function callbacks()
    {
        return [
            'before_save' => ['normalize_url']
        ];
    }
    
    protected function validations()
    {
        return [
            'url' => [
                'presence' => true
            ]
        ];
    }
    
    static public function normalize($url)
    {
        if ($url) {
            $url = preg_replace(
                array('/^http:\/\/blog\d+\.fc2/', '/^http:\/\/blog-imgs-\d+\.fc2/', '/^http:\/\/img\d+\.pixiv\.net/'),
                array("http://blog.fc2", "http://blog.fc2", "http://img.pixiv.net"),
                $url
            );
            return $url;
        }
    }
    
    static public function normalize_for_search($url)
    {
        if (preg_match('/\.\w+$/', $url) && preg_match('/\w\/\w/', $url))
            $url = dirname($url);
        
        $url = preg_replace(
            array('/^http:\/\/blog\d+\.fc2/', '/^http:\/\/blog-imgs-\d+\.fc2/', '/^http:\/\/img\d+\.pixiv\.net/'),
            array("http://blog*.fc2", "http://blog*.fc2", "http://img*.pixiv.net"),
            $url
        );
    }

    public function normalize_url()
    {
        $this->normalized_url = self::normalize($this->url);
    }
}