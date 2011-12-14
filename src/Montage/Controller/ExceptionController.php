<?php
/**
 *  handle exceptions 
 *  
 *  Fatal errors are common if this controller is called before the AutoLoader has been
 *  created.
 *  
 *  @version 0.3
 *  @author Jay Marcyes
 *  @since 2-19-10
 *  @package montage 
 *  @subpackage Controller 
 ******************************************************************************/
namespace Montage\Controller;

use Montage\Controller\Controller;

class ExceptionController extends Controller {

  /**
   *  handle http exceptions by setting the status code and printing a basic error
   *  to the user
   *  
   *  @return string  the message to print to the user
   */
  public function handleHttpException(\Exception $e){
  
    if(!empty($this->response)){
    
      $this->response->setStatusCode($e->getCode(),$e->getStatusMessage());
    
    }//if
    
    return sprintf('%s - %s',$e->getCode(),$e->getStatusMessage());
  
  }//method

  /**
   *  catch-all stray errors
   *  
   *  if the thrown exception doesn't have its own handler method then it will be
   *  sent here                 
   *
   *  @param  \Exception  $e  the thrown exception   
   */
  public function handleIndex(\Exception $e,array $e_list = array()){
  
    $title = sprintf('Exception handled by %s',__METHOD__);
    $plain_text = (strncasecmp(PHP_SAPI, 'cli', 3) === 0);
    array_unshift($e_list,$e);
    
    if($plain_text){
    
      echo $title,PHP_EOL,PHP_EOL;
  
    }else{
      
      echo $title,'<br><br>';
      
    }//if/else
  
    foreach($e_list as $e){
    
      if($plain_text){
      
        echo $e; // CLI
        echo PHP_EOL,'------------------------------------------------',PHP_EOL;
      
      }else{
      
        echo nl2br($e); // html
        echo '<hr>';
      
      }//if/else
      
    }//if
     
    return false;
  
  }//method

}//class
