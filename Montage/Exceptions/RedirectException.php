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
namespace Montage\Exceptions;

use Exception;

class RedirectException extends Exception {

  protected $url = '';
  
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
  
  public function __construct($url,$code = 302){
  
    $this->url = $url;
    
    parent::__construct($url,$code);
  
  }//method
  
  public function getUrl(){ return $this->url; }//method
  
}//class
