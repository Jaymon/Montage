<?php

/**
 *  handle session stuff 
 *
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-28-10
 *  @package montage
 ******************************************************************************/
class montage_session {

  /**
   *  for saveRequest, getRequest function
   */        
  const FIELD_REQUEST = 'montage_session_request_saved';
  
  /**
   *  the name of the session started with this class
   */     
  const SESSION_NAME = 'mns';

  /**
   *  holds the path where the session will be saved, set with {@link setPath()}
   *  @var  string
   */
  protected $path = '';

  final function __construct($path = ''){
    
    $ret_bool = false;
    $session_id = session_id();
    
    if(empty($session_id)){
    
      $file = $line = '';
      if(headers_sent($file,$line)){
      
        throw new RuntimeException(
          sprintf(
            'cannot start session because headers were sent at %s:%s',
            $file,
            $line
          )
        );
      
      }else{
        
        //NOTE 8-6-07: I could use session_id(), and session_name() here to try and get the session_id from the url, this
        //  might be useful if the person doesn't have cookies enabled
        
        // set the required session vars...
        ///if(!empty($session_cookie_domain)){ ini_set('session.cookie_domain',$session_cookie_domain); }//if
        
        ini_set('session.name',self::SESSION_NAME);
        register_shutdown_function('session_write_close');
        session_save_path($this->setPath($path));
        
        session_start();
        
        // pull out the flash map...
        $flash_map = $this->getField('montage_session_flash',array());
        
        // save killed...
        $flash_map['dead'] = empty($flash_map['kill']) ? array() : $flash_map['kill'];
        
        // move get to kill...
        $flash_map['kill'] = empty($flash_map['get']) ? array() : $flash_map['get'];
        
        // move set to get...
        $flash_map['get'] = empty($flash_map['set']) ? array() : $flash_map['set'];
        
        // clear set...
        $flash_map['set'] = array();
        
        // save it back into the session...
        $this->setField('montage_session_flash',$flash_map);
        
        // load any request vars back in...
        $this->loadRequest();
        
      }//if/else
      
    }//if
    
    $this->start();
    
  }//method
  
  function start(){}//method
  
  /**
   *  save a field into the session
   *  
   *  @param  string  $key
   *  @param  mixed $val
   *  @return mixed $val
   */
  function setField($key,$val){
  
    $_SESSION[$key] = $val;
    return $val;
  
  }//method
  
  /**
   *  get a field from the session
   *  
   *  @param  string  $key
   *  @param  mixed $default_val  if $key wasn't found, return this
   *  @return mixed
   */
  function getField($key,$default_val = null){
  
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default_val;
  
  }//method
  
  /**
   *  remove a field from the session
   *  
   *  @param  string  $key
   */
  function killField($key){
    unset($_SESSION[$key]);
  }//method
  
  /**
   *  does a field exist in the session and is non-empty
   *  
   *  @param  string  $key
   *  @return boolean   
   */
  function hasField($key){
    return !empty($_SESSION[$key]);
  }//method
  
  /**
   *  does a field exist in the session
   *  
   *  @param  string  $key
   *  @return boolean   
   */
  function existsField($key){
    return array_key_exists($key,$_SESSION);
  }//method
  
  
  /**
   *  get a flash field (field that is good for about one page load)
   *  
   *  @param  string  $key
   *  @param  mixed $default_val
   *  @return mixed
   */
  function getFlash($key,$default_val = null){
  
    $flash_map = $this->getField('montage_session_flash',array());
    return isset($flash_map['get'][$key]) ? $flash_map['get'][$key] : $default_val;
  
  }//method
  
  /**
   *  save a field into the session that will be good for only one page load
   *  
   *  @param  string  $key
   *  @param  mixed $val
   *  @return mixed $val
   */
  function setFlash($key,$val){
  
    $flash_map = $this->getField('montage_session_flash',array());
    $flash_map['set'][$key] = $val;
    $this->setField('montage_session_flash',$flash_map);
    return $val;
  
  }//method
  
  /**
   *  basically moves all the flashed fields back into the born position
   *  
   *  the fields are moved back into born so that things like redirects won't wipe
   *  them before the visitor has a chance to see them
   *  
   *  I tried to do this automatically in a __destruct but it never would save it to
   *  the $_SESSION array so I now have to do this manually by having {@link montage_response::redirect()}
   *  call this method         
   */
  function resetFlash(){
  
    // pull out the flash map...
    $flash_map = $this->getField('montage_session_flash',array());
    
    // move get back to set...
    $flash_map_set = empty($flash_map['set']) ? array() : $flash_map['set'];
    $flash_map['set'] = array_merge(
      $flash_map_set,
      empty($flash_map['get']) ? array() : $flash_map['get']
    );
    
    // move kill to get...
    $flash_map['get'] = empty($flash_map['kill']) ? array() : $flash_map['kill'];
    
    // move dead to kill...
    $flash_map['kill'] = empty($flash_map['dead']) ? array() : $flash_map['dead'];
    
    // clear dead...
    $flash_map['dead'] = array();
    
    // save it back into the session...
    $this->setField('montage_session_flash',$flash_map);
  
  }//method
  
  /**
   *  save the $_GET and $_POST arrays into a session field
   *  
   *  set might be better moved to response when redirect() is called while load might
   *  be better off in request, the key is figuring out when to load. I'm thinking urls
   *  to see if something needs to be loaded
   */
  function setRequest(){
  
    $field_map = array();
    if(!empty($_GET)){ $field_map['_GET'] = $_GET; }//if
    if(!empty($_POST)){ $field_map['_POST'] = $_POST; }//if
    if(!empty($field_map)){ $this->setFlash(self::FIELD_REQUEST,$field_map); }//if
  
  }//method
  
  /**
   *  restore get and post vars that could've been set with {@link setRequest()}
   */
  protected function loadRequest(){
    
    $field_map = $this->getFlash(self::FIELD_REQUEST,array());
    
    if(!empty($field_map)){
      
      if(!empty($field_map['_GET'])){
        foreach($field_map['_GET'] as $key => $val){
          // only reset the value if isn't set...
          if(!isset($_GET[$key])){ $_GET[$key] = $val; }//if
        }//foreach
      }//if
      
      if(!empty($field_map['_POST'])){
        foreach($field_map['_POST'] as $key => $val){
          // only reset the value if isn't set...
          if(!isset($_POST[$key])){ $_POST[$key] = $val; }//if
        }//foreach
      }//if
      
    }//if
    
  }//method
  
  /**
   *  set the cache path, make sure it's valid
   *  
   *  @param  string  $path
   */
  protected function setPath($path){
  
    $path = montage_path::assure($path);
    $this->path = $path;
    return $path;
  
  }//method
  
}//class
