<?php
namespace Rails\Assets\Traits;

trait AssetPathTrait
{
    /**
     * Returns the asset path (i.e., the URL) for a file.
     * If the digest option is true, the path to the compiled file
     * (with fingerprint) will be returned, if found. Otherwise, $file
     * will just be appended to the assets path.
     * Note that $file could include path relative to assets path, if necessary,
     * like $this->assetPath('jquery-ui/loading.gif');
     */
    protected function assetPath($file, array $options = [])
    {
        if (!isset($options['digest'])) {
            $options['digest'] = true;
        }
        
        if ($options['digest']) {
            if ($path = \Rails::assets()->findCompiledFile($file)) {
                return $path;
            }
        }
        $root = \Rails::application()->router()->rootPath();
        if ($root == '/') {
            $root = '';
        }
        return $root . \Rails::assets()->prefix() . '/' . $file;
    }
}
