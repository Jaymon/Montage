<?php
/**
 *  This interface exists so you can easily make a custom Request object (by default,
 *  Montage is currently using Symfony's request object) but still make sure it works
 *  how Montage expects it to work  
 *  
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-20-11
 *  @package montage
 *  @subpackage request 
 ******************************************************************************/
namespace Montage\Request;

interface Requestable {

  /**
   *  return the base requested url
   *  
   *  the base url is the requested url minus the requested path
   *  
   *  @example
   *    // app directory is in /foo/web   
   *    // host is localhost:8080   
   *    $this->getUrl(); // http://localhost:8080/foo/web/bar/che?baz=foo
   *    $this->getBase(); // http://localhost:8080/foo/web
   *    $this->getHost(); // localhost
   *    $this->getPath(); // /bar/che    
   *      
   *  @since  6-29-11         
   *  @return string
   */
  public function getBase();

  /**
   *  allow external setting of the http host, super handy for CLI scripts that won't know what
   *  host they are to run on
   *  
   *  @since  8-24-11   
   *  @param  string  $base_url the host:port/path where the :port and /path are optional
   */
  public function setBase($base_url);

  /**
   *  get the host that this request was made from
   *  
   *  @return string      
   */
  public function getHost();

  /**
   *  gets just the request path
   *  
   *  @example
   *    http://example.com/var/web/foo/bar return foo/bar         
   *    http://example.com/foo/bar return foo/bar
   *       
   *  @return string  just the request path without the root path
   */
  public function getPath();

}//method
