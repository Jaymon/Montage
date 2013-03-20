<?php
/**
 *  Handles code generation for a couple things I find myself doing over and over
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 2013-3-11
 *  @package montage 
 ******************************************************************************/
namespace Montage\Controller;

use Montage\Dependency\Containable;
use Montage\Dependency\Dependable;

use Montage\Event\Eventable;
use Montage\Event\Dispatch;

use Path;

class NewCommand extends Command implements Eventable, Dependable {


  /**
   *  the event dispatcher
   *
   *  @see  setDispatch(), getDispatch()
   *  @var  Dispatch
   */
  protected $event_dispatch = null;

  /**
   *  the dependency injection container
   *
   *  @see  setContainer(), getContainer()
   *  @var  \Montage\Dependency\Containable
   */
  protected $container = null;

  /**
   * handles creation of a new Command controller
   *
   * @param array $names  the names of Command controllers you want (so if you wanted FooCommand, pass in Foo)
   * @param string  --dir the directory you want to write the files to (defaults to /app_path/src/Controller)
   */
  public function handleCommand(array $names = array()){

    return $this->writeControllers($names, 'Command', '\\Montage\\Controller\\Command');
  
  }//method

  /**
   * handles creation of a new Endpoint controller
   *
   * @param array $names  the names of Endpoint controllers you want (so if you wanted FooEndpoint, pass in Foo)
   * @param string  --dir the directory you want to write the files to (defaults to /app_path/src/Controller)
   */
  public function handleEndpoint(array $names = array()){

    return $this->writeControllers($names, 'Endpoint', '\\Montage\\Controller\\Endpoint');
  
  }//method

  /**
   * write out a controller
   *
   * @param array $names  an array of controller names without a class postfix
   * @param string  $class_postfix  the postfix to use (eg, Ednpoint)
   * @param string  $paren_class_name the parent class to have the controller extend
   */
  protected function writeControllers(array $names, $class_postfix, $parent_class_name){

    // canary
    if(empty($names)){ throw new \UnexpectedValueException('no controller names were passed in'); }//if
  
    // get the path where the new file will be written...
    $dir_path = null;
    if($this->request->hasField('dir')){
      $dir_path = new Path($this->request->getField('dir'));
      if(!$dir_path->isAbsolute()){
        $dir_path = new Path($this->config->getAppPath(), $dir_path);
      }//if

    }else{
      $dir_path = new Path($this->config->getAppPath(), 'src', 'Controller');
    }//if/else

    $dir_path->assure();
    $template = $this->getContainer()->getTemplate();
    $template->setField('class_postfix', $class_postfix);
    $template->setField('parent_class_name', $parent_class_name);
    $template_file = 'new/controller.php';

    foreach($names as $name){

      $template->setField('class_name', $name);
      $file_path = new Path($dir_path, sprintf('%s%s.php', ucfirst($name), $class_postfix));
      if($file_path->exists()){

        $this->screen->err('* Skipping %s because a file exists at %s', $name, $file_path);

      }else{

        $this->screen->out('Writing Controller %s to %s', $name, $file_path);
        $ret_int = file_put_contents($file_path, $template->render($template_file), LOCK_EX);
        if($ret_int === false){
        
          throw new \UnexpectedValueException(
            sprintf('writing to path "%s" failed with an unknown error', $file_path)
          );

        }//if

      }//if/else
      
    }//foreach 
  
    return true;
  
  }//method
  
  public function setContainer(Containable $container){ $this->container = $container; }//method
  public function getContainer(){ return $this->container; }//method

  public function setEventDispatch(\Montage\Event\Dispatch $dispatch){ $this->event_dispatch = $dispatch; }//method
  public function getEventDispatch(){ return $this->event_dispatch; }//method
  public function broadcastEvent(\Montage\Event\Event $event){
    $dispatch = $this->getEventDispatch();
    return empty($dispatch) ? $event : $dispatch->broadcast($event);
  }//method
  
}//class

