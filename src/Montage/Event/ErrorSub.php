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
    $request = $container->getRequest();
    
    // canary, we pass the exception on if it is command line request
    if($request->isCli()){ return; }//if

    $e = $event->getParam();
    $response = $container->getResponse();

    if($e instanceof HttpException){
      $response->setStatusCode($e->getCode(), $e->getStatusMessage());
      // $event->setParam(false);
      ///$event->setParam(sprintf('%s - %s', $e->getCode(), $e->getStatusMessage()));
      $response->setTitle(sprintf('%s - %s', $e->getCode(), $e->getStatusMessage()));

    }else{
      $response->setTitle($e->getMessage());
    }//if/else

    $response->setTemplate('page'); // we reset to the default page in case it was changed
    $response->setField('content_template', 'error.php');
    $response->setField('e', $e);
    $event->setParam(null);

  }//method

}//class

