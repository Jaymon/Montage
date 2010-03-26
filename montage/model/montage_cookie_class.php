<?php

/**
 *  handle cookie stuff 
 *
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 3-26-10
 *  @package montage
 ******************************************************************************/

class montage_cookie {
  
  /**
   *  holds the default domain
   *  
   *  @var  string      
   */
  protected $domain = '';
  
  /**
   *  holds the default expire time, this time is added onto current to decide when
   *  the cookie should expire   
   *
   *  @var  integer   
   */
  protected $expire = 15552000; // 6 months
  
  /**
   *  what path the cookie should be good for, honestly, this should almost always
   *  be root (ie, /)
   *  
   *  @var  string
   */
  protected $path = '/';

  final function __construct($default_domain = ''){
  
    $this->setDomain($this->getBroadestDomain($default_domain));
    
    $this->start();
  
  }//method
  
  protected function start(){}//method
  
  public function setDomain($val){ $this->domain = $val; }//method
  public function getDomain(){ return $this->domain; }//method
  
  public function setExpire($val){ $this->expire = (int)$val; }//method
  public function getExpire(){ return $this->expire; }//method
  
  public function setPath($val){ $this->path = $val; }//method
  public function getPath(){ return $this->path; }//method
  
  /**
   *  save a field into the cookie
   *  
   *  @param  string  $key
   *  @param  mixed $val
   *  @param  integer $expire when the $key/$val should expire
   *  @param  string  $domain what domain should be used
   *  @param  string  $path what path should be used   
   *  @return mixed $val  on success return $val, on failure return false
   */
  function setField($key,$val,$expire = 0,$domain = '',$path = ''){
  
    // canary...
    if(headers_sent()){ return false; }//if
  
    // set defaults...
    if(empty($expire)){ $expire = $this->getExpire(); }//if
    if(empty($domain)){ $domain = $this->getDomain(); }//if
    if(empty($path)){ $path = $this->getPath(); }//if
  
    // cookies don't like booleans...
    if(is_bool($val)){ $val = empty($val) ? 0 : 1; }//if/else
    
    $timeout = time() + $expire;
    
    // using the secure switch for IE: http://us2.php.net/manual/en/function.setcookie.php#71743
    if(setcookie($key,$val,$timeout,$path,$domain,0)){ $val = false; }//if
    
    return $val;
  
  }//method
  
  /**
   *  get a field from the cookie
   *  
   *  @param  string  $key
   *  @param  mixed $default_val  if $key wasn't found, return this
   *  @return mixed
   */
  function getField($key,$default_val = null){
    return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default_val;
  }//method
  
  /**
   *  remove a field from the cookie
   *  
   *  @param  string  $key
   *  @param  string  $domain what domain should be used
   *  @param  string  $path what path should be used   
   *  @return boolean true on success, false on failure
   */
  function killField($key,$domain = '',$path = ''){
  
    // canary...
    if(headers_sent()){ return false; }//if
  
    // get defaults...
    if(empty($domain)){ $domain = $this->getDomain(); }//if
    if(empty($path)){ $path = $this->getPath(); }//if
  
    $val = false;
    $timeout = time() - 15552000;
    
    return setcookie($key,$val,$timeout,$path,$domain,0);
  
  }//method
  
  /**
   *  does a field exist in the cookie and is non-empty
   *  
   *  @param  string  $key
   *  @return boolean   
   */
  function hasField($key){
    return !empty($_COOKIE[$key]);
  }//method
  
  /**
   *  does a field exist in the cookie
   *  
   *  @param  string  $key
   *  @return boolean   
   */
  function existsField($key){
    return array_key_exists($key,$_COOKIE);
  }//method
  
  /**
   *  convert a domain to its broadest form, eg: sub.domain.com would become .domain.com so
   *  that the cookies will be good for all of domain.com, including all its subdomains   
   *
   *  WARNING 6-17-07: this function will fail on any subdomain with a period in it like: sub.domain.example.com
   *  because I can't think of a good way to differentiate that from: subdomain.example.co.uk   
   *
   *  @param  string  $domain the domain to check and make broad
   *  @return string|boolean  it will return false if localhost   
   */              
  public function getBroadestDomain($domain){
  
    // canary...
    if(empty($domain)){ return ''; }//if
  
    $ret_str = ''; // domain should be set to false if it is localhost.
    $matched = array();
    $period_count = mb_substr_count($domain,'.');
    
    if($period_count > 0){
    
      if($period_count > 1){
    
        $domain_bits = explode('.',$domain,2);
        $ret_str = $domain_bits[1];
      
      }else{
      
        // assume example.com...
        $ret_str = $domain;
      
      }//if/else
      
      $ret_str = sprintf('.%s',$ret_str);
    
    }else{
    
      // localhost problems, should set domain to false if localhost...
      // http://us2.php.net/manual/en/function.setcookie.php#74231
      // http://us2.php.net/manual/en/function.setcookie.php#73107
      $ret_str = false;
    
    }//if/else
    
    return $ret_str;
  
  }//method

}//class
