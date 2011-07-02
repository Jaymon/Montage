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

  public function __construct(Request $request,Response $response,Url $url){
  
    $this->request = $request;
    $this->response = $response;
    $this->url = $url;
  
  }//method

  /**
   *  override to allow your controller to do stuff right before any handle method is called
   */
  public function preHandle(){}//method
  
  /**
   *  override to allow your controller to do stuff right after the handle method is called
   */
  public function postHandle(){}//method
  
}//class
