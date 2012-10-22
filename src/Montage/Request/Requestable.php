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

  /*
   * get the request type
   *
   * usually this is something like web or command, this is handy for the controller
   * to pick which type of controller should be used
   *
   * @since 10-19-12
   * @return  string  'Web' if a web request, 'Controller' if cli request
   */
  public function getType();

}//method
