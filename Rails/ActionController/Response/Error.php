<?php
namespace Rails\ActionController\Response;

/**
 * This class also logs the errors.
 */
class Error extends Base
{
    private
        $_e,
        $_buffer = '',
        $_report;
    
    public function __construct(\Exception $e, array $params)
    {
        $this->_params = $params;
        $this->_e = $e;
    }
    
    public function _render_view()
    {
        $buffer = '';
        $this->_report = $this->_params['report'];
        unset($this->_params['report']);
        
        if (\Rails::application()->config()->consider_all_requests_local) {
            $no_html = \Rails::cli();
            
            if ($no_html) {
                $buffer .= strip_tags($this->_report);
                $buffer .= "\n";
            } else {
                $buffer .= $this->_header();
                $buffer .= $this->_report;
                $buffer .= $this->_footer();
            }
        } else {
            $file = \Rails::publicPath() . '/' . $this->_params['status'] . '.html';
            if (is_file($file)) {
                $buffer = file_get_contents($file);
            }
        }
        
        $this->_buffer = $buffer;
    }
    
    public function _print_view()
    {
        return $this->_buffer;
    }
    
    private function _header()
    {
$h = <<<HEREDOC
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Exception caught</title>
  <style>
    body { background-color: #fff; color: #333; }

    body, p, ol, ul, td {
      font-family: helvetica, verdana, arial, sans-serif;
      font-size:   13px;
      line-height: 18px;
    }

    pre {
      background-color: #eee;
      padding: 10px;
      font-size: 11px;
      overflow: auto;
    }
    
    pre.scroll {
      max-height:400px;
    }

    a { color: #000; }
    a:visited { color: #666; }
    a:hover { color: #fff; background-color:#000; }
  </style>
</head>
<body>
HEREDOC;
        return $h;
    }
    
    private function _footer()
    {
        return "</body>\n</html>";
    }
}