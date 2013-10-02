<?php
namespace Rails\Assets\Parser\Javascript\ClosureApi;

class ClosureApi
{
    const API_URL = 'http://closure-compiler.appspot.com/compile';
    
    static public
        $save_file_on_error = true,
        $save_path          = null,
        $errorFile_name    = 'closure_api_errorFile.js';
    
    protected
        $_params,
        $_resp,
        $_lastInfo;
    
    static public function minify($code, array $params = [])
    {
        $obj = new self($params);
        $obj->makeRequest($code, ['output_info' => 'errors']);
        
        if (trim($obj->resp())) {
            if (self::$save_file_on_error)
                file_put_contents(self::errorFile(), $code);
            throw new Exception\ErrorsOnCodeException($obj->resp());
        }
        
        $obj->makeRequest($code);
        $resp = $obj->resp();
        if (!trim($resp))
            throw new Exception\BlankResponseException("Closure returned an empty string (file too large? size => ".strlen($code).")");
        return $resp;
    }
    
    static public function errorFile()
    {
        if (!self::$save_path)
            self::$save_path = Rails::root() . '/tmp';
        
        return self::$save_path . '/' . self::$errorFile_name;
    }
    
    public function __construct($params)
    {
        $this->_params = array_merge($this->defaultParams(), $params);
    }
    
    public function makeRequest($code, array $extra_params = [])
    {
        $params = array_merge($this->_params, $extra_params);
        $params['js_code'] = $code;
        
        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => http_build_query($params),
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HTTPHEADER      => [
                "Content-type: application/x-www-form-urlencoded"
            ]
        ]);
        $this->_resp = curl_exec($ch);
        $this->_lastInfo = curl_getinfo($ch);
        curl_close($ch);
    }
    
    public function resp()
    {
        return $this->_resp;
    }
    
    public function lastInfo()
    {
        return $this->_lastInfo;
    }
    
    protected function defaultParams()
    {
        return [
            'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
            'output_info'       => 'compiled_code',
            'output_format'     => 'text',
            'language'          => 'ECMASCRIPT5'
        ];
    }
}