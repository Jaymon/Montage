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
