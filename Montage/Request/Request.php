<?php
/**
 *  thin wrapper around Symfony's Request object (no sense in reinventing the wheel)
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 4-6-10
 *  @package montage
 ******************************************************************************/
namespace Montage\Request;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Montage\Request\Requestable as MontageRequest;

class Request extends SymfonyRequest implements MontageRequest {

  public function __construct(){
  
    parent::__construct($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
  
  }//method

  /**
   *  gets just the request path
   *  
   *  @example
   *    http://example.com/var/web/foo/bar return foo/bar         
   *    http://example.com/foo/bar return foo/bar
   *       
   *  @return string  just the request path without the root path
   */
  public function getPath(){ return $this->getPathInfo(); }//method

}//class
