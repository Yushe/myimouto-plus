<?php
namespace Rails\ActionView\Helper\Methods;

trait Number 
{
    public function numberToHumanSize($number, array $options = array())
    {
        $size = $number / 1024;
        if ($size < 1024) {
            $size  = number_format($size, 1);
            $size .= ' KB';
        } else {
            if (($size = ($size / 1024)) < 1024) {
                $size  = number_format($size, 1);
                $size .= ' MB';
            } elseif (($size = ($size / 1024)) < 1024) {
                $size  = number_format($size, 1);
                $size .= ' GB';
            } elseif (($size = ($size / 1024)) < 1024) {
                $size  = number_format($size, 1);
                $size .= ' TB';
            }
        }
        return $size; 
    }
}