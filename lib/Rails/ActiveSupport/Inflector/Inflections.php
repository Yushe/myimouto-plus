<?php
namespace Rails\ActiveSupport\Inflector;

class Inflections
{
    protected $acronyms = [];
    
    protected $plurals = [];
    
    protected $singulars = [];
    
    protected $irregulars = [];
    
    protected $uncountables = [];
    
    protected $humans = [];
    
    protected $acronymRegex = '';
    
    public function acronym($word)
    {
        $this->acronyms[strtolower($word)] = $word;
        $this->acronymRegex = '/' . implode('|', $this->acronymRegex) . '/';
    }
    
    public function plural($rule, $replacement)
    {
        unset($this->uncountables[$rule]);
        unset($this->uncountables[$replacement]);
        $this->plurals[$rule] = $replacement;
    }
    
    public function singular($rule, $replacement)
    {
        unset($this->uncountables[$rule]);
        unset($this->uncountables[$replacement]);
        $this->singulars[$rule] = $replacement;
    }
    
    public function irregular($singular, $plural)
    {
        unset($this->uncountables[$singular]);
        unset($this->uncountables[$plural]);
        
        $s0 = $singular[0];
        $srest = substr($singular, 1, -1);
        
        $p0 = $plural[0];
        $prest = substr($plural, 1, -1);
        
        if (strtoupper($s0) == strtoupper($p0)) {
            $this->plural("/($s0)$srest$/i", '\1' . $prest);
            $this->plural("/($p0)$prest$/i", '\1' . $prest);
            
            $this->singular("/($s0)$srest$/i", '\1' . $srest);
            $this->singular("/($p0)$prest$/i", '\1' . $srest);
        } else {
            $this->plural("/".strtoupper($s0)."(?i)$srest$/", strtoupper($p0) . $prest);
            $this->plural("/".strtolower($s0)."(?i)$srest$/", strtolower($p0) . $prest);
            $this->plural("/".strtoupper($p0)."(?i)$prest$/", strtoupper($p0) . $prest);
            $this->plural("/".strtolower($p0)."(?i)$prest$/", strtolower($p0) . $prest);
            
            $this->singular("/".strtoupper($s0)."(?i)$srest$/", strtoupper($s0) . $srest);
            $this->singular("/".strtolower($s0)."(?i)$srest$/", strtolower($s0) . $srest);
            $this->singular("/".strtoupper($p0)."(?i)$prest$/", strtoupper($s0) . $srest);
            $this->singular("/".strtolower($p0)."(?i)$prest$/", strtolower($s0) . $srest);
        }
    }
    
    public function uncountable()
    {
        $this->uncountables = array_merge($this->uncountables, func_get_args());
    }
    
    public function human($rule, $replacement)
    {
        $this->humans[$rule] = $replacement;
    }
    
    public function clear($scope = 'all')
    {
        switch ($scope) {
            case 'all':
                $this->plurals = $this->singulars = $this->uncountables = $this->humans = [];
                break;
            
            default:
                if (!isset($this->$scope)) {
                    throw new Exception\InvalidArgumentException(
                        sprintf("Unknown scope to clear: %s", $scope)
                    );
                }
                $this->$scope = [];
                break;
        }
    }
    
    public function acronyms()
    {
        return $this->acronyms;
    }
    
    public function plurals()
    {
        return $this->plurals;
    }
    
    public function singulars()
    {
        return $this->singulars;
    }
    
    public function irregulars()
    {
        return $this->irregulars;
    }
    
    public function uncountables()
    {
        return $this->uncountables;
    }
    
    public function humans()
    {
        return $this->humans;
    }
    
    public function acronymRegex()
    {
        return $this->acronymRegex;
    }
}