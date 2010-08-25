<?php
/**
 *  fetch response php class
 *  
 *  this will hold the response of a completed fetch 
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-14-10
 *  @project fetch
 ******************************************************************************/      
class fetch_response extends fetch_base {

  /**#@+
   *  some http codes to keep in mind.
   *  
   *  @see  http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
   *  @var  integer
   */
  const CODE_OK = 200;
  const CODE_NOT_FOUND = 404;
  const CODE_SERVER_ERROR = 500;
  const CODE_CACHED = 304;
  const CODE_NO_CONTENT = 204;
  const CODE_ERROR = 0;
  /**#@-*/

  /**
   *  initialize the instance
   *  
   *  @param  string  $body the url's body (ie, response text)
   *  @param  array $response_info  all the curl info that was accumaleted in a key/val map
   *  @param  array $headers  the parsed headers (in key/val format) of the collected response headers   
   */
  final public function __construct($body,$response_info,$headers){
  
    $header_size = $response_info['header_size'];
    
    $this->field_map['headers'] = $headers;
    
    // the first header is the response...
    $this->field_map['status'] = $this->field_map['headers']['http'];
    
    $response_list = explode(' ',$this->field_map['headers']['http']);
    $this->field_map['version'] = empty($response_list[0]) ? '' : $response_list[0];
    $this->field_map['msg'] = empty($response_list[2]) ? '' : $response_list[2];
    
    $this->field_map['body'] = $body;
    
    // set the http code...
    $this->field_map['code'] = (int)$response_info['http_code'];
    
    // set the final url that was fetched, this takes into accoutnt redirects...
    $this->field_map['url'] = $response_info['url'];
    
    $this->field_map['info'] = $response_info;
  
  }//method
  
  /**
   *  will be true if the request failed, ie, the http status code was usually >= 400
   *
   *  @return boolean   
   */
  public function failed(){
    $code = $this->getCode();
    ///return empty($code) || !((($code >= self::CODE_OK) && ($code < 300)) || ($code == self::CODE_CACHED));
    return empty($code) || ($code >= 400);
  }//method
  
  /**
   *  the first header that contains the http version used and the status code and message
   *
   *  @return string
   */
  public function getStatus(){
    return empty($this->field_map['status']) ? '' : $this->field_map['status'];
  }//method
  
  /**
   *  the http version that was used, usually something like HTTP/1.1
   *
   *  @return string
   */
  public function getVersion(){
    return empty($this->field_map['version']) ? '' : $this->field_map['version'];
  }//method
  
  /**
   *  the http status code
   *  
   *  @see  http://en.wikipedia.org/wiki/List_of_HTTP_status_codes      
   *
   *  @return integer
   */
  public function getCode(){
    return empty($this->field_map['code']) ? 0 : $this->field_map['code'];
  }//method
  
  /**
   *  the message that was sent in the header, eg the OK part of "200 OK"
   *
   *  @return string
   */
  public function getMsg(){
    return empty($this->field_map['msg']) ? '' : $this->field_map['msg'];
  }//method
  
  /**
   *  the body of the requested url
   *
   *  @return string
   */
  public function getBody(){
    return empty($this->field_map['body']) ? '' : $this->field_map['body'];
  }//method
  
  /**
   *  the absolute final requested url, this might be different than the original
   *  requested url because of redirects, etc.   
   *
   *  @return string
   */
  public function getUrl(){
    return empty($this->field_map['url']) ? '' : $this->field_map['url'];
  }//method
  
  /**
   *  get the raw info map returned   
   *
   *  @return array key/value curl info map
   */
  public function getInfo(){
    return empty($this->field_map['info']) ? array() : $this->field_map['info'];
  }//method
  
}//class
