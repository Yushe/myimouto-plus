<?php
namespace Rails\ActionMailer;

use stdClass;
use Rails;
use Rails\ActionView;
use Zend\Mail;
use Zend\Mime;

/**
 * Builds and delivers mail.
 *
 * This class should only be used by Rails\ActionMailer\Base.
 * In order to create a custom Mail, Zend\Mail should be used
 * directly instead.
 */
class Deliverer
{
    /**
     * Rails mail that will be processed.
     *
     * @var Rails\ActionMailer\Base
     */
    protected $mail;
    
    /**
     * Mail message that will be delivered.
     *
     * @var Zend\Mail\Message
     */
    protected $message;
    
    protected $textTemplate;
    
    protected $htmlTemplate;
    
    /**
     * @var Zend\Mime\Message
     */
    protected $body;
    
    public function __construct(Base $mail)
    {
        $this->mail = $mail;
        $this->buildMessage();
    }
    
    public function deliver()
    {
        ActionMailer::transport()->send($this->message);
        return $this;
    }
    
    public function mail()
    {
        return $this->mail;
    }
    
    public function message()
    {
        return $this->message;
    }
    
    private function buildMessage()
    {
        $this->message = new Mail\Message();
        
        $this->setCharset();
        $this->setFrom();
        $this->setTo();
        $this->setSubject();
        
        ActionView\ViewHelpers::load();
        
        $this->createTextPart();
        $this->createHtmlPart();
        
        $this->body = new Mime\Message();
        
        $this->addTemplates();
        $this->addAttachments();
        
        $this->message->setBody($this->body);
        
        unset($this->textTemplate, $this->htmlTemplate);
    }
    
    private function setCharset()
    {
        if (!$charset = $this->mail->charset) {
            $charset = mb_detect_encoding($this->mail->subject);
            if (!$charset)
                $charset = null;
        }
        
        $this->message->setEncoding($charset);
    }
    
    private function setFrom()
    {
        if (!is_array($this->mail->from)) {
            $email = $this->mail->from;
            $name = null;
        } else {
            list($email, $name) = $this->mail->from;
        }
        
        $this->message->setFrom($email, $name);
    }
    
    private function setTo()
    {
        $this->message->addTo($this->mail->to);
    }
    
    private function setSubject()
    {
        $this->message->setSubject($this->mail->subject);
    }
    
    private function createTextPart()
    {
        $template_file = $this->templateBasename() . '.text.php';
        try {
            $template = new Template($template_file);
            $template->setLocals($this->mail->vars());
            $this->textTemplate = $template;
        } catch (Exception\ExceptionInterface $e) {
        }
    }
    
    private function createHtmlPart()
    {
        $template_file = $this->templateBasename() . '.php';
        try {
            $template = new Template($template_file);
            $template->setLocals($this->mail->vars());
            $this->htmlTemplate = $template;
        } catch (Exception\ExceptionInterface $e) {
        }
    }
    
    private function templateBasename()
    {
        return Rails::config()->paths->views . '/' .
               $this->mail->templatePath . '/' .
               $this->mail->templateName;
    }
    
    private function addTemplates()
    {
        if ($this->textTemplate) {
            $content = $this->textTemplate->renderContent();
            $part = new Mime\Part($content);
            $part->type = 'text/plain';
            $part->encoding = Mime\Mime::ENCODING_QUOTEDPRINTABLE;
            $this->body->addPart($part);
        }
        
        if ($this->htmlTemplate) {
            $content = $this->htmlTemplate->renderContent();
            $part = new Mime\Part($content);
            $part->type = 'text/html';
            $part->encoding = Mime\Mime::ENCODING_QUOTEDPRINTABLE;
            $this->body->addPart($part);
        }
    }
    
    /**
     * Requires Fileinfo.
     */
    private function addAttachments()
    {
        if (class_exists('Finfo', false)) {
            $finfo = new \Finfo(FILEINFO_MIME_TYPE);
        } else {
            $finfo = false;
        }
        
        foreach ($this->mail->attachments as $filename => $attachment) {
            if (!is_array($attachment)) {
                throw new Exception\RuntimeException(
                    sprintf("Attachments must be array, %s passed", gettype($attachment))
                );
            } elseif (
                !is_string($attachment['content']) &&
                (
                 !is_resource($attachment['content']) ||
                 !get_resource_type($attachment['content']) == 'stream'
                )
            ) {
                throw new Exception\RuntimeException(
                    sprintf(
                        "Attachment content must be string or stream, %s passed",
                        gettype($attachment['content'])
                    )
                );
            }
            
            $type = null;
            
            if (empty($attachment['mime_type']) && $finfo) {
                if (is_resource($attachment['content'])) {
                    $type = $finfo->buffer(stream_get_contents($attachment['content']));
                    rewind($attachment['content']);
                } else {
                    $type = $finfo->buffer($attachment['content']);
                }
            }
            
            $part = new Mime\Part($attachment['content']);
            
            if (empty($attachment['encoding'])) {
                $attachment['encoding'] = Mime\Mime::ENCODING_BASE64;
            }
            
            $part->encoding = $attachment['encoding'];
            
            if ($type) {
                $part->type = $type;
            }
            
            $part->disposition = !empty($attachment['inline']) ?
                                  Mime\Mime::DISPOSITION_INLINE :
                                  Mime\Mime::DISPOSITION_ATTACHMENT;
            
            $this->body->addPart($part);
        }
    }
}