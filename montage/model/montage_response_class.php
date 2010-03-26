<?php

/**
 *  all the montage response stuff
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-20-10
 *  @package montage 
 ******************************************************************************/
class montage_response extends montage_base {

  const CONTENT_HTML = 'text/html';
  const CONTENT_TXT = 'text/plain';
  const CONTENT_JS = 'text/javascript'; // application/javascript, application/x-javascript
  const CONTENT_CSS = 'text/css';
  const CONTENT_JSON = 'application/json';
  const CONTENT_JSON_HEADER = 'application/x-json';
  const CONTENT_XML = 'text/xml'; // application/xml, application/x-xml
  const CONTENT_RDF = 'application/rdf+xml';
  const CONTENT_ATOM = 'application/atom+xml';
  
  /**
   *  hold the status code mapped to the default message
   *  
   *  @link http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
   *      
   *  @var  array
   */
  protected $status_map = array(
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
    306 => '(Unused)',
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
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported'
  );
  
  /**
   *  holds the template instance that will be used to render the response
   *
   *  @var  montage_template   
   */
  private $template_instance = null;

  final function __construct($template_path){
    
    $class_name = montage_core::getBestClassName('montage_template');
    $this->template_instance = new $class_name();
    $this->template_instance->setPath($template_path);
  
    $this->start();
    
  }//method
  
  /**
   *  the content type header
   */        
  function setContentType($val){ return $this->setField('mn_response_content_type',$val); }//method
  function getContentType(){ return $this->getField('mn_response_content_type',self::CONTENT_HTML); }//method
  function hasContentType(){ return $this->hasField('mn_response_content_type'); }//method
  
  /**
   *  the status code and message
   *  
   *  @param  integer $code one of the $status_map keys
   *  @param  string  $msg  if you want something different than the default message, set this            
   */
  function setStatus($code,$msg = ''){
    
    // canary...
    if(empty($code)){ throw new InvalidArgumentException('$code cannot be empty'); }//if
    if(!isset($this->status_map[$code])){
      throw new UnexpectedValueException(
        sprintf(
          '$code: %s does not match any key in $status_map: %s',
          $code,
          print_r($this->status_map,1)
        )
      );
    }//if
    
    if(empty($msg)){
      $msg = $this->status_map[$code];
    }//if
    
    $this->setField('mn_response_status_code',$code);
    $this->setField('mn_response_status_msg',$msg);
    return $code;
    
  }//method
  
  function getStatus(){
    return sprintf(
      '%s %s',
      $this->getStatusCode(),
      $this->getField('mn_response_status_msg',$this->status_map[200])
    );
  }//method
  
  function getStatusCode(){ return $this->getField('mn_response_status_code',200); }//method
  function isStatusCode($val){ return $this->isField('mn_response_status_code',$val); }//method
  
  /**
   *  hold the template the response will use to render the response
   */
  function setTemplate($val){ return $this->setField('mn_response_template',$val); }//method
  function getTemplate(){ return $this->getField('mn_response_template',''); }//method
  function hasTemplate(){ return $this->hasField('mn_response_template'); }//method
  
  /**
   *  hold the title
   */
  function setTitle($val){ return $this->setField('title',$val); }//method
  function getTitle(){ return $this->getField('title',''); }//method
  function hasTitle(){ return $this->hasField('title'); }//method

  /**
   *  hold the description
   */
  function setDesc($val){ return $this->setField('desc',$val); }//method
  function getDesc(){ return $this->getField('desc',''); }//method
  function hasDesc(){ return $this->hasField('desc'); }//method

  /**
   *  return a template instance ready to output the response
   *
   *  @return montage_template   
   */
  function getTemplateInstance(){
    
    // canary...
    if(!$this->hasTemplate()){
      throw UnexpectedValueException(
        sprintf('%s has no template set and it is trying to instantiate the template class',__CLASS__)
      );
    }//if
    
    $this->template_instance->setTemplate($this->getTemplate());
    $this->template_instance->setFields($this->getFields());
    return $this->template_instance;

  }//method
  
  /**
   *  redirect to another url
   *  
   *  @param  string  $url  the url to redirect to
   *  @param  integer $wait_time  how long to wait before redirecting
   *  @throws montage_stop_exception
   */
  function redirect($url,$wait_time = 0){
  
    if(empty($url)){ return; }//if
  
    $session = montage::getSession();
    $session->setRequest();
    $session->resetFlash();
    $this->setStatus(302); // would 303 or 307 be better?
  
    if(headers_sent()){
  
      // http://en.wikipedia.org/wiki/Meta_refresh
      echo sprintf('<meta http-equiv="refresh" content="%s;url=%s">',$wait_time,$url);
  
    }else{
    
      if($wait_time > 0){ sleep($wait_time); }//if
      header(sprintf('Location: %s',$url));
      
    }//if/else

    // I'm honestly not sure if this does anything...
    ///if(session_id() !== ''){ session_write_close(); }//if
    
    $exception_name = montage_core::getBestClassName('montage_redirect_exception');
    throw new $exception_name();
  
  }//method

}//class     
