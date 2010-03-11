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
   *  holds the template instance that will be used to render the response
   *
   *  @var  montage_template   
   */
  private $template_instance = null;

  final function __construct($template_path){
    
    $class_name = montage_core::getCoreClassName('MONTAGE_TEMPLATE');
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
  
    if(headers_sent()){
  
      // http://en.wikipedia.org/wiki/Meta_refresh
      echo sprintf('<meta http-equiv="refresh" content="%s;url=%s">',$wait_time,$url);
  
    }else{
    
      if($wait_time > 0){ sleep($wait_time); }//if
      header(sprintf('Location: %s',$url));
      
    }//if/else

    // I'm honestly not sure if this does anything...
    ///if(session_id() !== ''){ session_write_close(); }//if
    
    throw new montage_redirect_exception();
  
  }//method

}//class     
