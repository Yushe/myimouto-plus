<?php

namespace MyImouto\Post;

use Imagick;
use RuntimeException;

abstract class Resizer
{
    public static function resize(
        $fileExt,
        $readPath,
        $writePath,
        $outputSize,
        $outputQuality
    ) {
        list ($readW, $readH) = getimagesize($readPath);
        
        extract($outputSize);
        
        !isset($width) && $width = $readW;
        !isset($height) && $height = $readH;
        !isset($cropTop) && $cropTop = 0;
        !isset($cropBottom) && $cropBottom = $readH;
        !isset($cropLeft) && $cropLeft = 0;
        !isset($cropRight) && $cropRight = $readW;
        !isset($cropWidth) && $cropWidth = $cropRight - $cropLeft;
        !isset($cropHeight) && $cropHeight = $cropBottom - $cropTop;
        
        if (class_exists('Imagick', false)) {
            $image = new Imagick($readPath);
            
            if ($fileExt == 'gif' && $image->getNumberImages()) {
                /**
                 * Coalesce GIF if it has many layers (animated),
                 * to ensure a good output. Otherwise the resulting image could
                 * look "corrupt".
                 */
                $image = $image->coalesceImages()->current();
            } elseif ($fileExt == 'png') {
                $jpg = Processor::pngToJpg($image);
                
                $image->clear();
                
                $image = $jpg;
            }
            
            $image->cropImage($cropWidth, $cropHeight, $cropLeft, $cropTop);
            $image->thumbnailImage($width, $height);
            $image->setImageFormat('jpg');
            
            $fh = fopen($writePath, 'w');
            
            $image->writeImageFile($fh);
            
            fclose($fh);
        } else {
            $sample = imagecreatetruecolor($width, $height);
            
            $gray = imagecolorallocate(
                $sample,
                config('myimouto.conf.bgcolor')[0],
                config('myimouto.conf.bgcolor')[1],
                config('myimouto.conf.bgcolor')[2]
            );
            
            imagefilledrectangle($sample, 0, 0, $width, $height, $gray);
            
            switch($fileExt) {
                case 'jpg':
                    $source = imagecreatefromjpeg($readPath);
                    
                    break;
                    
                case 'png':
                    $source = imagecreatefrompng($readPath);
                    
                    break;
                    
                case 'gif':
                    $source = imagecreatefromgif($readPath);
                    
                    break;
                    
                default:
                    throw new RuntimeException('Unsupported file extension');
                    
                    break;
            }
            
            if (!$source) {
                throw new RuntimeException("Error while creating image resource");
            }
            
            $result = imagecopyresampled(
                $sample,
                $source,
                0,
                0,
                $cropLeft,
                $cropTop,
                $width,
                $height,
                $cropWidth,
                $cropHeight
            );
            
            if (!$result) {
                throw new RuntimeException("Error while resampling image");
            }
            
            $result = imagejpeg($sample, $writePath, $outputQuality);
            
            if (!$result) {
                throw new RuntimeException("Error while writing image");
            }
        }
    }
    
    public static function reduceTo(
        $size,
        $maxSize,
        $ratio = 1,
        $allowEnlarge = false,
        $minMax = false
    ) {
        $ret = $size;

        if ($minMax) {
            if (($maxSize['width'] < $maxSize['height']) != ($size['width'] < $size['height'])) {
                list($maxSize['width'], $maxSize['height']) = array($maxSize['height'], $maxSize['width']);
            }
        }
        
        if ($allowEnlarge) {
            if ($ret['width'] < $maxSize['width']) {
                $scale          = (float)$maxSize['width']/(float)$ret['width'];
                $ret['width']   = $ret['width'] * $scale;
                $ret['height']  = $ret['height'] * $scale;
            }
	        
            if (($maxSize['height'] && $ret['height']) < ($ratio*$maxSize['height'])) {
                $scale          = (float)$maxSize['height']/(float)$ret['height'];
                $ret['width']   = $ret['width'] * $scale;
                $ret['height']  = $ret['height'] * $scale;
            }
        }

        if ($ret['width'] > $ratio*$maxSize['width']) {
            $scale          = (float)$maxSize['width']/(float)$ret['width'];
            $ret['width']   = $ret['width'] * $scale;
            $ret['height']  = $ret['height'] * $scale;
        }

        if ($maxSize['height'] && ($ret['height'] > $ratio*$maxSize['height'])) {
            $scale          = (float)$maxSize['height']/(float)$ret['height'];
            $ret['width']   = $ret['width'] * $scale;
            $ret['height']  = $ret['height'] * $scale;
        }

        $ret['width']   = round($ret['width']);
        $ret['height']  = round($ret['height']);
        
        return $ret;
    }
}
