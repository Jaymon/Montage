<?php
/**
 *  thrown for http response exceptions
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 11-7-11
 *  @package montage
 *  @subpackage exception
 ******************************************************************************/      
namespace Montage\Exception;

class HttpException extends FrameworkException {

  /**
   *  hold the status message
   *  
   *  @var  string
   */
  protected $status_msg = '';

  /**
   *  the different redirect codes
   *     
   *  http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
   *
   *  @var  array   
   */
  protected $code_map = array(
    100 => 'Continue',
    101 => 'Switching Protocols',
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    307 => 'Temporary Redirect',
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    414 => 'Request-URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Requested Range Not Satisfiable',
    417 => 'Expectation Failed',
    418 => 'I\'m a teapot',
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported'
  );
  
  /**
   *  create instance
   *     
   *  @param  integer $code the status code
   *  @param  string  $msg  the actual exception message
   *  @param  string  $status_msg if you want to override the defualt status message   
   */
  public function __construct($code,$msg = '',$status_msg = ''){
  
    parent::__construct($msg,$code);
    
    if(empty($status_msg)){
    
      if(isset($this->code_map[$code])){
    
        $this->status_msg = $this->code_map[$code];  
      
      }else{
      
        $this->status_msg = 'Unknown Status Code';
      
      }//if/else
    
    }else{
    
      $this->status_msg = $status_msg;
    
    }//if/else
  
  }//method
  
  public function getStatusMessage(){ return $this->status_msg; }//method
  
}//class
