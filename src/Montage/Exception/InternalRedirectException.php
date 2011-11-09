<?php
/**
 *  thrown to internally change the requested controller from the one defined by the
 *  passed in url/request path to the one passed in via the constructor of this class  
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-14-11
 *  @package montage
 *  @subpackage exceptions  
 ******************************************************************************/      
namespace Montage\Exception;

use Exception;

class InternalRedirectException extends FrameworkException {

  protected $path = '';

  /**
   *  create this object
   *  
   *  @param  string  $path a request path, something like: controller/method/arg1/arg2/...
   */
  public function __construct($path){
  
    $this->path = $path;
    
    parent::__construct($path);
  
  }//method
         
  public function getPath(){ return $this->path; }//method
  
}//class
