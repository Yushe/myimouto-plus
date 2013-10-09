<?php
namespace Rails\ActiveSupport\Inflector;

/**
 * This class is a port of Ruby on Rails' Inflector.
 */
class Inflector
{
    protected $inflections = [];
    
    public function __construct()
    {
        $this->inflections['en'] = new DefaultEnglishInflections();
    }
    
    public function inflections($locale = 'en', Closure $block = null)
    {
        if ($locale instanceof Closure) {
            $block = $locale;
            $locale = 'en';
        }
        
        $inflections = $this->getInflections($locale);
        
        if ($block) {
            $block($inflections);
        } else {
            return $inflections;
        }
    }
    
    public function pluralize($word, $locale = 'en')
    {
        $irregulars = $this->inflections()->irregulars();
        if (isset($irregulars[$word])) {
            return $this->inflections()->irregulars()[$word];
        }
        return $this->applyInflections($word, $this->inflections($locale)->plurals());
    }
    
    public function singularize($word, $locale = 'en')
    {
        if (is_string($key = array_search($word, $this->inflections()->irregulars()))) {
            return $key;
        }
        return $this->applyInflections($word, $this->inflections($locale)->singulars());
    }
    
    public function camelize($term, $uppercaseFirstLetter = true)
    {
        $string = (string)$term;
        $acronyms = $this->inflections()->acronyms();
        
        if ($uppercaseFirstLetter) {
            $string = preg_replace_callback('/^[a-z\d]*/', function($m) use ($acronyms) {
                if (isset($acronyms[$m[0]])) {
                    return $acronyms[$m[0]];
                } else {
                    return ucfirst($m[0]);
                }
            }, $string);
        } else {
            $acronymRegex = $this->inflections()->acronymRegex();
            $string = preg_replace_callback('/^(?:'.$acronymRegex.'(?=\b|[A-Z_])|\w)/', function($m) use($term) {
                return strtolower($m[0]);
            }, $string);
        }
        
        return preg_replace_callback('/(?:_|(\/))([a-z\d]*)/i', function($m) use ($acronyms) {
            if (isset($acronyms[$m[2]])) {
                return $m[1] . $acronyms[$m[2]];
            } else {
                return ucfirst($m[2]);
            }
        }, $string);
    }
    
    public function underscore($camelCasedWord)
    {
        $word = (string)$camelCasedWord;
        $word = preg_replace_callback('/(?:([A-Za-z\d])|^)(?=\b|[^a-z])/', function($m) use ($camelCasedWord) {
            $ret = '';
            if (isset($m[1])) {
                $ret = $m[1];
            }
            if (isset($m[2])) {
                $ret .= $m[2];
            }
            
            return $ret;
        }, $word);
        
        $word = preg_replace([
            '/([A-Z\d]+)([A-Z][a-z])/',
            '/([a-z\d])([A-Z])/'
        ], [
            '\1_\2',
            '\1_\2'
        ], $word);
        
        $word = strtr($word, '-\\', '_/');
        $word = strtolower($word);
        return $word;
    }
    
    public function humanize($lowerCaseAndUnderscoredWord)
    {
        $result = (string)$lowerCaseAndUnderscoredWord;
        foreach ($this->inflections()->humans() as $rule => $replacement) {
            $ret = preg_replace($rule, $replacement, $result, -1, $count);
            if ($count) {
                $result = $ret;
                break;
            }
        }
        
        if (strpos($result, '_id') === strlen($result) - 3) {
            $result = substr($result, 0, -3);
        }
        $result = strtr($result, '_', ' ');
        
        $acronyms = $this->inflections()->acronyms();
        
        $result = preg_replace_callback('/([a-z\d]*)/i', function($m) use ($acronyms) {
            if (isset($acronyms[$m[1]])) {
                return $acronyms[$m[1]];
            } else {
                return strtolower($m[1]);
            }
        }, $result);
        
        $result = preg_replace_callback('/^\w/', function($m) {
            return strtoupper($m[0]);
        }, $result);
        return $result;
    }
    
    public function titleize($word)
    {
        return ucwords($this->humanize($this->underscore($word)));
    }
    
    public function tableize($className)
    {
        return $this->pluralize($this->underscore($className));
    }
    
    public function classify($tableName)
    {
        return $this->camelize($this->singularize(preg_replace('/.*\./', '', $tableName)));
    }
    
    public function ordinal($number)
    {
        $absNumber = (int)$number;
        if (in_array($absNumber % 100, range(11, 13))) {
            return $absNumber . 'th';
        } else {
            switch ($absNumber % 100) {
                case 1:
                    return $absNumber . 'st';
                case 2:
                    return $absNumber . 'nd';
                case 3:
                    return $absNumber . 'rd';
                default:
                    return $absNumber . 'th';
            }
        }
    }
    
    protected function getInflections($locale)
    {
        if (!isset($this->inflections[$locale])) {
            $this->inflections[$locale] = new Inflections();
        }
        return $this->inflections[$locale];
    }
    
    protected function applyInflections($word, $rules)
    {
        if (
            !$word ||
            (
                preg_match('/\b\w+\Z/', strtolower($word), $m) &&
                in_array($m[0], $this->inflections()->uncountables())
            )
        ) {
            return $word;
        } else {
            foreach ($rules as $rule => $replacement) {
                $ret = preg_replace($rule, $replacement, $word, -1, $count);
                if ($count) {
                    $word = $ret;
                    break;
                }
            }
            return $word;
        }
    }
}