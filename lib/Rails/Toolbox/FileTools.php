<?php
namespace Rails\Toolbox;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

/**
 * Toolbox.
 * Abstract static class that wraps useful functions.
 */
abstract class FileTools
{
    /**
     * Deletes all files and directories recursively from a directory.
     * Note that existance of the dir must be previously checked.
     * The directory isn't deleted.
     * SO.com:1407338
     */
    static public function emptyDir($dirpath)
    {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirpath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
        }
    }
    
    /**
     * Lists all directories and subdirectories found in a path.
     */
    static public function listDirs($root)
    {
        $dirs = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            if ($path->isDir()) {
                $dirs[] = $path->getPathname();
            }
        }
        return $dirs;
    }
    
    /**
     * Search for all files matching a pattern within a directory and sub directories.
     */
    static public function searchFile($root, $pattern = '*', $flags = 0)
    {
        $dirs = self::listDirs($root);
        array_unshift($dirs, $root);
        
        $foundFiles = [];
        
        foreach ($dirs as $dir) {
            $filePatt = $dir . '/' . $pattern;
            $files = glob($filePatt, $flags);
        
            if ($files) {
                $foundFiles = array_merge($foundFiles, $files);
            }
        }
        
        return $foundFiles;
    }
    
    // static public function mod_time($path) 
    // {
        // return self::modTime($path);
    // }
    
    /**
     * File modification time.
     * Found at PHP.net
     * filemtime() returns invalid date on Windows, this function fixes that.
     */
    static public function modTime($path) 
    { 
        $time = filemtime($path);
        $is_dst = (date('I', $time) == 1);
        $system_dst = (date('I') == 1);
        $adjustment = 0;
        
        if($is_dst == false && $system_dst == true)
            $adjustment = 3600;
        elseif($is_dst == true && $system_dst == false)
            $adjustment = -3600;
        else
            $adjustment = 0;

        return ($time + $adjustment);
    }
}