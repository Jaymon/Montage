<?php
/**
 *  handle Form's being created in controller handle method params 
 *  
 *  allow form objects in the controller method to be populated with submitted values
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 12-13-11
 *  @package montage
 *  @subpackage form
 ******************************************************************************/
Namespace Montage\Form\Event;

use Montage\Event\Event;
use Montage\Event\SingleSub;

class ControllerParamSub extends SingleSub {
  
  public function getEventName(){
    return 'framework.filter.controller_param_created:\\Montage\\Form\\Form';
  }//method
  
  /**
   *  adds Form magic to the controller
   *  
   *  basically, this will allow you to pass in:
   *  
   *  handleMethod(FormName $form); 
   *
   *  and have the FormName form be created and populated from the request variables.
   *  
   *  @example
   *    // magically getting a form
   *    // request: foo/bar/?FormName[q]=this+is+the+search+string
   *    
   *    // method in FooController...   
   *    public function handleBar(\FormName $form){
   *      echo $form->q->getVal(); // 'this is the search string' 
   *    }
   */
  public function handle(Event $event){
  
    $instance = $event->getParam();
    $container = $this->getContainer();
    $request = $container->getRequest();
    
    $form_name = $instance->getName();

    if($form_field_map = $request->getField($form_name)){
    
      $instance->set($form_field_map);
    
    }//if
    
    $event->setParam($instance);
  
  }//method

}//class
