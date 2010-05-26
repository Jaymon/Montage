<?php

/**
 *  this class actually handles the request
 *  
 *  while you can extend this class, don't do it unless you know what you are doing
 *  and absolutely need to as you can screw up the entire application in all kinds of ways      
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-26-10
 *  @package montage 
 ******************************************************************************/
class montage_handle extends montage_base {

  /**
   *  switched to true in the start() function
   *  @var  boolean
   */
  protected static $is_started = false;

  final function __construct(){
  
    // canary...
    if(self::$is_started){
      throw new RuntimeException(
        sprintf(
          '%s has alread started, no point in starting it again',
          get_class($this)
        )
      );
    }//if
  
    self::$is_started = true;
  
    $this->start();
    
  }//method

  /**
   *  this is what will actually handle the request, 
   *  
   *  called at the end of the start.php file, this is really the only thing that needs 
   *  to be called, everything else will take care of itself      
   */
  protected function start(){
  
    $debug = montage::getSettings()->getDebug();
    
    if($debug){ montage_profile::start(__METHOD__); }//if
    
    // get all the filters and start them...
    $filter_list = montage_core::getFilterClassNames();
    $use_template = $this->get($filter_list);

    if(!is_bool($use_template)){
      
      throw new UnexpectedValueException(
        sprintf(
          'the controller method (%s::%s) returned a non-boolean value, it was a %s',
          $request->getControllerClass(),
          $request->getControllerMethod(),
          gettype($use_template)
        )
      );
      
    }//if

    if($debug){ montage_profile::start('response'); }//if
    
    $response = montage::getResponse();
    $response->handle($use_template);
    
    // profile, response...
    if($debug){ montage_profile::stop(); }//if
    
    // profile, method...
    if($debug){ montage_profile::stop(); }//if
    
  }//method
  
  /**
   *  handle a request, warts and all
   *  
   *  the reason this is separate from {@link handle()} so that it can call it again
   *  to try and handle (in case of error or the like)
   *  
   *  @param  array $filter_list  a list of string names of classes that extend montage_filter
   *  @return boolean $use_template to pass into the response handler
   */
  protected function get($filter_list = array()){
  
    // canary, avoid infinite internal redirects...
    $ir_field = 'montage_handle::infinite_recursion_count'; 
    $ir_count = $this->getField($ir_field,0);
    $ir_max_count = 15; // is there a reason to go more than 15?
    if($ir_count > $ir_max_count){
      throw new RuntimeException(
        sprintf(
          'The application has internally redirected more than %s times, something seems to '
          .'be wrong and the app is bailing to avoid infinite recursion!',
          $ir_max_count
        )
      );
    }else{
      $this->bumpField($ir_field,1);
    }//if/else
  
    $debug = montage::getSettings()->getDebug();
    
    // profile...
    if($debug){ montage_profile::start(__METHOD__); }//if

    $use_template = false;
    $request = montage::getRequest();
    $response = montage::getResponse();
    $event = montage::getEvent();
    
    try{
      
      if(!empty($filter_list)){
        
        // profile, filters start...
        if($debug){ montage_profile::start('filters start'); }//if

        foreach($filter_list as $key => $filter_class_name){
          
          if(is_string($filter_class_name)){
            
            $event->broadcast(
              montage_event::KEY_INFO,
              array('msg' => sprintf('starting filter %s',$filter_class_name))
            );
            
            $filter_list[$key] = montage_factory::getInstance($filter_class_name);
            
          }//if
            
        }//foreach
        
      }//if
      
      // profile, filters start...
      if($debug){ montage_profile::stop(); }//if
      
      // profile...
      if($debug){ montage_profile::start('controller'); }//if
      
      $use_template = $request->handle();
      
      // profile, stop controller...
      if($debug){ montage_profile::stop(); }//if
      
      // profile...
      if($debug){ montage_profile::start('filters stop'); }//if
      
      if(!empty($filter_list)){
        
        // run all the filters again to stop them...
        foreach($filter_list as $filter_instance){
        
          $event->broadcast(
            montage_event::KEY_INFO,
            array('msg' => sprintf('stopping filter %s',get_class($filter_instance)))
          );
          
          $filter_instance->stop();
          
        }//foreach
        
      }//if
      
      // profile...
      if($debug){ montage_profile::stop(); }//if
    
    }catch(montage_forward_exception $e){
    
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => 
          sprintf(
            'forwarding to controller %s::%s via forward exception at %s:%s',
            $request->getControllerClass(),
            $request->getControllerMethod(),
            $e->getFile(),
            $e->getLine()
          )
        )
      );
    
      // we forwarded to another controller so we're going another round...
      $use_template = $this->get();
    
    }catch(montage_redirect_exception $e){
    
      // we don't really need to do anything since the redirect header should have been called
      $use_template = false;
      $response->set('');
      
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => 
          sprintf(
            'redirect to %s',
            $e->getMessage()
          )
        )
      );
    
    }catch(montage_stop_exception $e){
      
      $use_template = false; // since a stop signal was caught we'll want to use $response->get()
      
      // do nothing, we've stopped execution so we'll go ahead and let the response take over
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => 
          sprintf(
            'execution stopped via stop exception at %s:%s',
            $e->getFile(),
            $e->getLine()
          )
        )
      );
      
    }catch(Exception $e){
      
      $request->setErrorHandler($e);
      
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => 
          sprintf(
            'forwarding to controller %s::%s to handle exception at %s:%s',
            $request->getControllerClass(),
            $request->getControllerMethod(),
            $e->getFile(),
            $e->getLine()
          )
        )
      );
      
      // send it back through for another round...
      $use_template = $this->get();
      
    }//try/catch
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
  
    return $use_template;
  
  }//method

}//class     
