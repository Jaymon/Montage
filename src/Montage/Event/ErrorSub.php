<?php
/**
 *  this listens to thrown errors and does some basic handling of them
 *
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 2-26-13
 *  @package montage
 *  @subpackage event
 ******************************************************************************/
Namespace Montage\Event;

use Montage\Exception\HttpException;

class ErrorSub extends SingleSub {

  public function getEventName(){ return 'framework.handleError'; }//method

  /**
   *  this is the callback that will be registered to the name returned from {@link getEventName()}
   *  
   *  @param  Event $event
   */
  public function handle(Event $event){
    $container = $this->getContainer();
    $e = $event->getParam();
    $response = $container->getResponse();
    
    if($e instanceof HttpException){
      $response->setStatusCode($e->getCode(), $e->getStatusMessage());
      // $event->setParam(false);
      $event->setParam(sprintf('%s - %s', $e->getCode(), $e->getStatusMessage()));

    }else{

      $response->setField('content_template', 'error.php');
      $response->setField('e', $e);
      $response->setField('e_list', $event->getField('e_list', array()));
      $event->setParam(null);
    
    }//if/else

  }//method

}//class

