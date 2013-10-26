<?php
class ExtractUrls
{
    # Extract image URLs from HTML.
    static public function extract_image_urls($url, $body)
    {
        $parts = parse_url($url);
        $base_url = rtrim(str_replace($parts['path'], '', $url), '/');
        
        $urls = [];
        $regex = '/(<a [^>]+>)/';
        preg_match_all($regex, $body, $ms);
        $regex = '/href=(?:\"|\')([^\"\']+\.(?:png|jpe?g))(?:\"|\')/i';
        
        if ($ms[0]) {
            foreach ($ms[1] as $href) {
                if (preg_match($regex, $href, $m)) {
                    if (substr($m[1], 0, 1) == '/')
                        $urls[] = $base_url . $m[1];
                    else
                        $urls[] = $m[1];
                }
            }
        }
        
        return $urls;
    }
}