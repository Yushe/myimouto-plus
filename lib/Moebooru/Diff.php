<?php
namespace Moebooru;

class Diff
{
    static public function generate($fromText, $toText)
    {
        if (!is_array($fromText)) {
            $fromText = preg_split('/\v/', $fromText);
        }
        if (!is_array($toText)) {
            $toText = preg_split('/\v/', $toText);
        }
        
        $diff     = new \Horde_Text_Diff('auto', [$fromText, $toText]);
        $renderer = new \Horde_Text_Diff_Renderer_Inline();
        $result   = $renderer->render($diff);
        return nl2br(trim($result));
    }
}
