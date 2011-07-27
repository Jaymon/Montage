<?php
/**
 *  all controller classes should extend this class 
 *  
 *  @abstract 
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-19-10
 *  @package montage 
 ******************************************************************************/      
namespace Montage\Controller;

use Montage\Field\Field;
use Montage\Controller\Controllable;

use Montage\Request\Request;
use Montage\Response\Response;
use Montage\Url;

abstract class Controller extends Field implements Controllable {

  protected $request = null;
  
  protected $response = null;
  
  protected $url = null;

  /**
   *  override to allow your controller to do stuff right before any handle method is called
   */
  public function preHandle(){}//method
  
  /**
   *  override to allow your controller to do stuff right after the handle method is called
   */
  public function postHandle(){}//method
  
  /**
   *  set the request object if it is available
   *
   *  @since  7-26-11
   *  @param  Request $request      
   */
  public function setRequest(Request $request){ $this->request = $request; }//method
  
  /**
   *  set the response object if it is available
   *
   *  @since  7-26-11
   *  @param  Response  $response      
   */
  public function setResponse(Response $response){ $this->response = $response; }//method
  
  /**
   *  set the Url object if it is available
   *
   *  @since  7-26-11
   *  @param  Url $url      
   */
  public function setUrl(Url $url){ $this->url = $url; }//method
  
}//class
