<?php
/**
 *  thrown for 3xx redirects
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-15-11
 *  @package montage
 *  @subpackage exceptions  
 ******************************************************************************/      
namespace Montage\Exception;

use Exception;

class RedirectException extends Exception {

  protected $url = '';
  
  protected $wait = 0;
  
  /**
   *  the different redirect codes
   *     
   *  http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
   *
   *  @var  array   
   */
  protected $code_map = array(
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    307 => 'Temporary Redirect'
  );
  
  /**
   *  create instance
   *  
   *  @param  string  $url  the url you want to redirect to
   *  @param  integer $wait how many seconds you want to wait before redirecting
   *  @param  integer $code the redirect code
   */
  public function __construct($url,$wait = 0,$code = 302){
  
    $this->url = $url;
    
    parent::__construct($url,$code);
  
  }//method
  
  public function getUrl(){ return $this->url; }//method
  
  public function getWait(){ return $this->wait; }//method
  
}//class
