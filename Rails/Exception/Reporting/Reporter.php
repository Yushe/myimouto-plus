<?php
namespace Rails\Exception\Reporting;

use Rails;
use Rails\Exception\ExceptionInterface as RailsException;
use Rails\Exception\PHPError\ExceptionInterface as PHPError;

class Reporter
{
    static public function create_report($e, array $options = [])
    {
        $errorScope = self::findErrorScope($e);
        
        if ($e instanceof RailsException && $e->title()) {
            if ($e instanceof PHPError) {
                if ($errorScope) {
                    $error_name = $e->title() . ' in ' . $errorScope;
                } else {
                    $error_name = $e->title();
                }
            } else {
                $error_name = $e->title();
            }
        } else {
            if ($errorScope) {
                $error_name = get_class($e) . ' in ' . $errorScope;
            } else {
                $error_name = get_class($e) . ' thrown';
            }
        }
        
        if (Rails::cli()) {
            $error_name = '*** ' . $error_name . ' ***';
        }
        
        $html  = '';
        $html .= '<h1>'.$error_name.'</h1>'."\n";
        
        $html .= '<pre class="scroll">'.$e->getMessage();
        if ($e instanceof RailsException && ($postMessage = $e->postMessage())) {
            $html .= "\n" . $postMessage;
        }
        if (!empty($options['extraMessages'])) {
            $html .= "\n" . $options['extraMessages'];
        }
        
        if ($e instanceof RailsException && $e->skip_info()) {
            $html .= "</pre>\n";
        } else {
            $base_dir = Rails::root() . DIRECTORY_SEPARATOR;
            $rails_base_dir = Rails::path() . '/';
            
            $file = $line = $context_args = null;
            
            $trace = $e->getTrace();
            
            if ($e instanceof PHPError) {
                if (!empty($e->error_data()['errargs']))
                    $context_args = $e->error_data()['errargs'];
            } else {
                if (isset($trace[0]['args']))
                    $context_args = $trace[0]['args'];
            }
            
            $trace = $e->getTraceAsString();
            
            $trace = str_replace([Rails::path(), Rails::root() . '/'], ['Rails', ''], $trace);
            
            $file = self::pretty_path($e->getFile());
            
            $line  = $e->getLine();
            
            $html .= '</pre>';
            $html .= "\n";
            $html .= self::rootAndTrace();
            $html .= '<pre>' . $trace . '</pre>';
            
            if ($context_args) {
                ob_start();
                var_dump($context_args);
                $context = ob_get_clean();
            } else {
                $context = '';
            }
            $html .= self::showContextLink();
            
            if (!Rails::cli()) {
                $html .= '<div id="error_context" style="display: none;"><pre class="scroll">' . $context . '</pre></div>';
            }
        }
        
        return $html;
    }
    
    /**
     * Cleans up the HTML report created by create_html_report() for logging purposes.
     */
    static public function cleanup_report($log)
    {
        return strip_tags(str_replace(self::removableLines(), ['', "\nError context:\n"], $log));
    }
    
    static public function pretty_path($path)
    {
        if (strpos($path, Rails::path()) === 0)
            return 'Rails' . substr($path, strlen(Rails::path()));
        elseif (strpos($path, Rails::root()) === 0)
            return substr($path, strlen(Rails::root()));
        else
            return $path;
    }
    
    /**
     * Experimental (like everything else):
     * These functions will be used to skip lines in the trace,
     * just to give the title of the exception a more accurate
     * scope (the function/method where the error occured).
     * If the validation returns true, a line will be skipped.
     * This is particulary useful when the exception was thrown by
     * the error handler, or the like.
     */
    static protected function traceSkippers()
    {
        return [
            function ($trace) {
                if (
                    isset($trace[0]['class'])        &&
                    isset($trace[0]['function'])     &&
                    $trace[0]['class']    == 'Rails' &&
                    $trace[0]['function'] == 'errorHandler'
                ) {
                    return true;
                }
            },
            
            function ($trace) {
                if (
                    isset($trace[0]['class'])        &&
                    isset($trace[0]['function'])     &&
                    $trace[0]['class']    == 'Rails\ActiveRecord\Base' &&
                    $trace[0]['function'] == '__get'
                ) {
                    return true;
                }
            }
        ];
    }
    
    static private function rootAndTrace()
    {
        $lines  = '<code>Rails::root(): ' . Rails::root() . "</code>";
        $lines .= '<h3>Trace</h3>'."\n";
        return $lines;
    }
    
    static private function showContextLink()
    {
        $lines = '<a href="#" onclick="document.getElementById(\'error_context\').style.display=\'block\'; return false;">Show error context</a>';
        return $lines;
    }
    
    static private function removableLines()
    {
        return [self::rootAndTrace(), self::showContextLink()];
    }
    
    static protected function findErrorScope($e)
    {
        $tr = $e->getTrace();
        
        foreach (self::traceSkippers() as $skipper) {
            if (true === $skipper($tr)) {
                array_shift($tr);
            }
        }
        
        if (isset($tr[0]['class']) && isset($tr[0]['function']) && isset($tr[0]['type'])) {
            if ($tr[0]['type'] == '->') {
                // # looks better than ->
                $type = '#';
                // $type = '-&gt;';
            } else {
                $type = $tr[0]['type'];
            }
            $errorScope = $tr[0]['class'] . $type . $tr[0]['function'] . '';
        } elseif (!$e instanceof PHPError && isset($tr[0]['function'])) {
            $errorScope = $tr[0]['function'] . '';
        } else {
            $errorScope = '';
        }
        
        return $errorScope;
    }
}