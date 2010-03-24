<?php

/**
 *  cookie class
 *  
 *  basically a wrapper class for setting and getting cookie values.
 *  
 *  functions can be called from this class by cookie::function_name() so that I don't
 *  have to instantiate the class at all.
 *
 *  6-14-07 - initial writing of the class
 *  6-18-07 - fixed the set method so it worked. Added get, delete methods
 *  8-5-07 - cleaned this class up a bit, got rid of a few extraneous functions, etc..
 *    Added the has function
 *  2-7-08 -  fixed some bugs that had been there for forever evidently, like not setting .example.com in
 *    the domain function but just setting example.com. 
 *
 */         

class montage_cookie {

  // class constants...
  const SIX_MONTHS = 15552000; // 6 months in seconds
  const DEFAULT_PATH = '/';

  final function __construct($domain){
  
    $
    
    $this->start();
  
  }//method
  
  protected function start(){}//method

  //****************************************************************************
  public static function set($name,$val,$expire_time = self::SIX_MONTHS,$domain = false){
  
    // error checking...
    if(empty($name)){ return false; }//if
    if(empty($domain)){ $domain = self::domain(); }//if
    if(empty($expire_time)){ $expire_time = self::SIX_MONTHS; }//if
    if(is_bool($val)){
      // cookies don't like booleans...
      if($val){ $val = 1; }else{ $val = 0; }//if/else
    }//if/else
    
    $ret_bool = false;
    $timeout = time() + $expire_time;
    
    if(!headers_sent()){
    
      if(setcookie($name,$val,$timeout,self::DEFAULT_PATH,$domain)){ $ret_bool = true; }//if
      
    }//if
    
    return $ret_bool;
  
  }//method
  
  //****************************************************************************
  static function get($name,$default_val = ''){
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default_val;
  }//method
  
  //****************************************************************************
  public static function has($name){
  
    $ret_bool = false;
    
    if($result = self::get($name)){ $ret_bool = true; }//if
  
    return $ret_bool;
  
  }//method
  
  //****************************************************************************
  public static function delete($name,$domain = false){
  
    // error checking...
    if(empty($domain)){ $domain = self::domain(); }//if
  
    $ret_bool = false;
    $val = false;
    $timeout = time() - self::SIX_MONTHS;
    
    if(!headers_sent()){
    
      if(setcookie($name,$val,$timeout,self::DEFAULT_PATH,$domain)){ $ret_bool = true; }//if
      
    }//if
  
    return $ret_bool;
  
  }//method
  
  //****************************************************************************
  public static function getCookieDomain(){ return self::domain(); }//method alias
  public static function domain(){
  
    // WARNING 6-17-07: this function will fail on any subdomain with a period in it like: sub.domain.example.com
    //  because I can't think of a good way to differentiate that from: subdomain.example.co.uk
  
    $ret_str = false; // domain should be set to false if it is localhost.
    $full_domain = '';
    $host = false;
    $matched = array();
  
    // get the full domain...
    if(isset($_SERVER['SERVER_NAME'])){
    
      $full_domain = $_SERVER['SERVER_NAME'];
    
    }else if(isset($_ENV['SERVER_NAME'])){
    
      $full_domain = $_ENV['SERVER_NAME'];
    
    }else if(isset($_SERVER['HTTP_HOST'])){
      
      $full_domain = $_SERVER['HTTP_HOST'];
    
    }else if(isset($_ENV['HTTP_HOST'])){
    
      $full_domain = $_ENV['HTTP_HOST'];
      
    }//if/else if
    
    // you could use substr_count here instead but would then have to use strpos to get the substr start position...
    if(preg_match_all("/\.{1}/u",$full_domain,$matched,PREG_OFFSET_CAPTURE) > 1){
    
      ///out::e($matched,get_defined_vars());
      
      if(isset($matched[0][0][1])){
      
        if($host = mb_substr($full_domain,($matched[0][0][1]+1))){
        
          $ret_str = '.'.$host;
        
        }//if
      
      }//if
    
    }else{
    
      // assume example.com...
      $ret_str = '.'.$full_domain;
    
    }//if
    
    return $ret_str;
  
  }//method

}//class
