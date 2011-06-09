<?php
/**
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-20-11
 *  @package montage
 *  @subpackage interface  
 ******************************************************************************/
namespace Montage\Interfaces;

interface Request {

  /**
   *  get the input that was used to make this request
   *  
   *  the returned array should always have 2 keys: 'path' and 'args'
   *      
   *  if the request was HTTP, then the returned array would be something like:
   *  
   *  array(
   *    'path' => 'http://domain.com/path/used'
   *    'args' => array_merge($_GET,$_POST)
   *  )
   *  
   *  and if the request was CLI (command line) then the returned array would be
   *  something like:                            
   *
   *  array(
   *    'path' => 'path/used/after/php'
   *    'args' => $_SERVER['argv']
   *  )
   *
   *  @return array
   */
  public function getInput();

}//method
