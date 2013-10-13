<?php
namespace Rails\I18n;

use Rails;

class I18n
{
    /**
     * Locale name.
     */
    private $_locale;
    
    private $_available_locales;
    
    private $loaded_locales = [];
    
    /**
     * Array with translations.
     */
    private $_tr = array();
    
    public function __construct($locale = null)
    {
        if ($locale !== null)
            $this->setLocale($locale);
        else {
            $this->_locale = $this->config()->default_locale;
        }
        $this->_load_rails_locale();
        $this->loadLocale();
    }
    
    public function config()
    {
        return Rails::application()->config()->i18n;
    }
    
    public function locale($val = null)
    {
        if ($val !== null) {
            throw new \Exception("Deprecated - use setLocale()");
        } else
            return $this->_locale;
    }
    
    public function setLocale($value)
    {
        if (!is_string($value)) {
            throw new Exception\InvalidArgumentException(
                sprintf('Locale value must be a string, %s passed', gettype($value))
            );
        } elseif ($this->_locale == $value) {
            return;
        }
        $this->_locale = $value;
        $this->_load_rails_locale($value);
        $this->loadLocale($value);
    }
    
    public function t($name, array $params = [])
    {
        if (is_array($name)) {
            $params = $name;
            $name = array_shift($params);
        } elseif (!is_string($name)) {
            throw new Exception\InvalidArgumentException(
                sprintf('Argument must be either an array or string, %s passed', gettype($params))
            );
        }
        
        if (is_null($tr = $this->_get_translation($this->_locale, $name))
            && ($this->_locale != $this->config()->default_locale ? is_null($tr = $this->_get_translation($this->config()->default_locale, $name)) : true))
        {
            return false;
        }
        
        /**
         * When adding new translations with the same name, since the arrays are
         * recursively merged, the result will not be a string, but an array.
         * If this is the case, the latter value will be used.
         */
        if (is_array($tr))
            $tr = array_pop($tr);
        
        if (is_int(strpos($tr, '%{'))) {
            foreach ($params as $k => $param) {
                $tr = str_replace('%{'.$k.'}', $param, $tr);
                unset($params[$k]);
            }
        }
        if ($params) {
            call_user_func_array('sprintf', array_merge(array($tr), $params));
        }
        
        return $tr;
    }
    
    public function available_locales()
    {
        if (!is_array($this->_available_locales)) {
            $this->_get_available_locales();
        }
        return $this->_available_locales;
    }
    
    public function defaultLocale()
    {
        return $this->config()->default_locale;
    }
    
    private function _get_translation($lang, $name)
    {
        $tr = null;
        
        if (isset($this->_tr[$lang])) {
            if (is_int(strpos($name, '.'))) {
                $tr = $this->_tr[$lang];
                foreach (explode('.', $name) as $idx) {
                    if (isset($tr[$idx])) {
                        $tr = $tr[$idx];
                    } else {
                        break;
                    }
                }
                
                if (!is_string($tr))
                    $tr = null;
            } else {
                if (isset($this->_tr[$lang][$name]))
                    $tr = $this->_tr[$lang][$name];
            }
        }
        
        return $tr;
    }
    
    private function _get_available_locales()
    {
        $dh = opendir($this->config()->path);
        
        $this->_available_locales = array();
        
        while (!is_bool($file = readdir($dh))) {
            if ($file == '.' || $file == '..')
                continue;
            $locale = pathinfo($file, PATHINFO_FILENAME);
            $this->_available_locales[] = $locale;
        }
        closedir($dh);
    }
    
    /**
     * Loads locale file.
     * If not a full path (i.e. doesn't start with / or x:), it'd be
     * taken as relative path to locales path (i.e. config/locales). In this
     * case, the extension of the file must be omitted.
     *
     * $i18n->loadLocale("/home/www/foo/bar/es.yml");
     * $i18n->loadLocale("es"); <- loads config/locales/es.php|yml
     * $i18n->loadLocale("subdir/es"); <- loads config/locales/subdir/es.php|yml
     * $i18n->loadLocale("/some/path/locales/%locale%.yml"); %locale% will be replaced with current locale
     */
    public function loadLocale($locale = null)
    {
        !$locale && $locale = $this->_locale;
        
        if (in_array($locale, $this->loaded_locales)) {
            return;
        }
        
        if (is_int(($pos = strpos($locale, '%locale%')))) {
            $locale = substr_replace($locale, $this->locale(), $pos, 8);
        }
        
        if (substr($locale, 0, 1) == '/' || substr($locale, 1, 1) == ':') {
            $file = $locale;
        } else {
            $patt = $this->config()->path . '/' . $locale . '.{php,yml}';
            $files = glob($patt, GLOB_BRACE);
            if ($files) {
                $file = $files[0];
            } else {
                return false;
            }
        }
        
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if ($ext == 'yml') {
            $locale_data = Rails\Yaml\Parser::readFile($file);
        } else {
            $locale_data = require $file;
        }
        $this->_tr = array_merge_recursive($this->_tr, $locale_data);
        
        $this->loaded_locales[] = $locale;
        return true;
    }

    private function _load_rails_locale($locale = null)
    {
        !func_num_args() && $locale = $this->_locale;
        
        
        $locale = ucfirst(strtolower($locale));
        $class_name = 'Rails\I18n\Locales\\' . $locale;
        
        if (class_exists($class_name, false))
            return;
        
        $file = __DIR__ . '/Locales/' . $locale . '.';
        $exts = ['php'];
        
        foreach ($exts as $ext) {
            $f = $file.$ext;
            if (is_file($f)) {
                require $f;
                $obj = new $class_name();
                $this->_tr = array_merge_recursive($this->_tr, $obj->tr());
                break;
            }
        }
        
        /**
         * If there's nothing in the array, it means we've just tried to load the default
         * application locale for Rails that isn't supported. Load english locale instead.
         */
        if (!$this->_tr)
            $this->_load_rails_locale('en');
    }
}