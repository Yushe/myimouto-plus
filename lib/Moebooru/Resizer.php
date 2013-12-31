<?php
namespace Moebooru;

use Imagick;

abstract class Resizer
{
    static public function resize($file_ext, $read_path, $write_path, $output_size, $output_quality)
    {
        list ($read_w, $read_h) = getimagesize($read_path);
        
        extract($output_size);
        
        !isset($width) && $width = $read_w;
        !isset($height) && $height = $read_h;
        !isset($crop_top) && $crop_top = 0;
        !isset($crop_bottom) && $crop_bottom = $read_h;
        !isset($crop_left) && $crop_left = 0;
        !isset($crop_right) && $crop_right = $read_w;
        !isset($crop_width) && $crop_width = $crop_right - $crop_left;
        !isset($crop_height) && $crop_height = $crop_bottom - $crop_top;
        
        if (class_exists('Imagick', false)) {
            $image = new Imagick($read_path);
            
            /**
             * Coalesce GIF if it has many layers (animated),
             * to ensure a good output. Otherwise the resulting image could
             * look "corrupt".
             */
            if ($file_ext == 'gif' && $image->getNumberImages()) {
                $image = $image->coalesceImages()->current();
            }
            
            $image->cropImage($crop_width, $crop_height, $crop_left, $crop_top);
            $image->thumbnailImage($width, $height);
            $image->setImageFormat('jpg');
            
            $fh = fopen($write_path, 'w');
            $image->writeImageFile($fh);
            fclose($fh);
        } else {
            $sample = imagecreatetruecolor($width, $height);
            $gray = imagecolorallocate($sample, CONFIG()->bgcolor[0], CONFIG()->bgcolor[1], CONFIG()->bgcolor[2]);
            imagefilledrectangle($sample, 0, 0, $width, $height, $gray);
            
            $e = '';
            
            switch($file_ext) {
                case 'jpg':
                    $source = imagecreatefromjpeg($read_path);
                    break;
                    
                case 'png':
                    $source = imagecreatefrompng($read_path);
                    break;
                    
                case 'gif':
                    $source = imagecreatefromgif($read_path);
                    break;
                    
                default:
                    $e = 'Wrong file extension';
                    break;
            }
            
            if (!$e) {
                if (!$source)
                    $e = "Error while creating image resource";
                elseif (!imagecopyresampled($sample, $source, 0, 0, $crop_left, $crop_top, $width, $height, $crop_width, $crop_height))
                    $e = "Error while resampling image";
                elseif (!imagejpeg($sample, $write_path, $output_quality))
                    $e = "Error while writing image";
            }
            
            if ($e) {
                throw new Exception\ResizeErrorException($e);
            }
        }
    }
    
    static public function reduce_to($size, $max_size, $ratio = 1, $allow_enlarge = false, $min_max = false)
    {
        $ret = $size;

        if ($min_max) {
            if (($max_size['width'] < $max_size['height']) != ($size['width'] < $size['height'])) {
                list($max_size['width'], $max_size['height']) = array($max_size['height'], $max_size['width']);
            }
        }
        
        if ($allow_enlarge) {
            if ($ret['width'] < $max_size['width']) {
                $scale = (float)$max_size['width']/(float)$ret['width'];
                $ret['width'] = $ret['width'] * $scale;
                $ret['height'] = $ret['height'] * $scale;
            }
	        
            if (($max_size['height'] && $ret['height']) < ($ratio*$max_size['height'])) {
                $scale = (float)$max_size['height']/(float)$ret['height'];
                $ret['width'] = $ret['width'] * $scale;
                $ret['height'] = $ret['height'] * $scale;
            }
        }

        if ($ret['width'] > $ratio*$max_size['width']) {
            $scale = (float)$max_size['width']/(float)$ret['width'];
            $ret['width'] = $ret['width'] * $scale;
            $ret['height'] = $ret['height'] * $scale;
        }

        if ($max_size['height'] && ($ret['height'] > $ratio*$max_size['height'])) {
            $scale = (float)$max_size['height']/(float)$ret['height'];
            $ret['width'] = $ret['width'] * $scale;
            $ret['height'] = $ret['height'] * $scale;
        }

        $ret['width'] = round($ret['width']);
        $ret['height'] = round($ret['height']);
        return $ret;
    }
}
