<?php
/**
 *  the kernal/core that translates the request to the response
 *  
 *  other names: handler, sequence, assembler, dispatcher, scheduler
 *  http://en.wikipedia.org/wiki/Montage_%28filmmaking%29  
 *  
 *  This class creates a lot of dependencies instead of having them be passed in.
 *  This is for convenience to make things easier, (eg, I didn't want to have to create 
 *  a Cache and Reflection instance to pass in here to resolve all the dependencies).
 *  see: http://misko.hevery.com/2008/07/08/how-to-think-about-the-new-operator/ for how
 *  I'm wrong about this, but for me, convenience trumps rightness in this instance.
 *   
 *  @version 0.8
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
namespace Montage;

use Montage\Cache\PHPCache;
use Path;
use Montage\Field\Field;

use Montage\Dependency\Reflection;
use Montage\Dependency\Container;
use Montage\Dependency\Dependable;

use Montage\Autoload\ReflectionAutoloader;
use Montage\Autoload\FrameworkAutoloader;

use Montage\Request\Requestable;
use Montage\Response\Response;
use Montage\Response\Template;

use Montage\Event\Event;
use Montage\Event\InfoEvent;
use Montage\Event\FilterEvent;
use Montage\Event\Eventable;

require_once(__DIR__.'/../../plugins/Utilities/src/Path.php');

// load the Framework autoloader, this will handle all other dependencies to load this class
// so I don't have to have a ton of includes() right here...
require_once(__DIR__.'/Autoload/Autoloadable.php');
require_once(__DIR__.'/Autoload/Autoloader.php');
require_once(__DIR__.'/Autoload/FrameworkAutoloader.php');
$fal = new FrameworkAutoloader('Montage',realpath(__DIR__.'/..'));
$fal->register();

class Framework extends Field implements Dependable,Eventable {


  /**
   *  holds any important internal instances this class is going to use
   *
   *  @since  7-6-11  changed from individual protected instance vars to this array
   *  @var  array
   */
  protected $instance_map = array();
  
  /**
   *  true if instance is ready to {@link handle()} a request
   * 
   *  @since  8-15-11    
   *  @see  preHandle()
   *  @var  boolean
   */
  protected $is_ready = false;

  /**
   *  create this object
   *  
   *  @param  string  $env  the environment, usually something like "dev" or "prod"
   *  @param  integer $debug_level  what level of debug you want
   *  @param  string  $app_path the root path for your application
   */
  public function __construct($env,$debug_level,$app_path){
  
    // canary...
    if(empty($env)){
      throw new \InvalidArgumentException('$env was empty, please set $env to something like "dev" or "prod"');
    }//if
    if(empty($app_path)){
      throw new \InvalidArgumentException('$app_path was empty, please set it to the root path of your app');
    }//if
    
    $this->setField('app_path',$app_path);
    $this->setField('env',$env);
    $this->setField('debug_level',$debug_level);
    
  }//method
  
  /**
   *  this handles all the initial configuration of the framework
   *  
   *  handy for when you want to activate the framework but don't want to call
   *  {@link handle()} to actually handle the request (ie, you want stuff like configuration
   *  and the autoloaders to be loaded, but you don't want the response to be generated yet)      
   *
   *  old name: activate()
   *
   *  @since  7-28-11
   */
  public function preHandle(){
  
    // canary...
    if($this->is_ready){ return true; }//if
  
    $ret_mixed = true;
  
    // this needs to be the first thing done otherwise preHandle() will keep getting
    // called again and again as other methods call this method to make sure everything
    // is ready...
    $this->is_ready = true;
  
    try{
    
      // collect all the paths we're going to use...
      $this->handlePaths();
    
      // first handle any files the rest of the framework might depend on...
      $this->handleDependencies();
  
      // start the autoloaders...
      // this is the first thing done because it is needed to make sure all the classes
      // can be found
      $this->handleAutoload();
  
      // handle any preliminary event stuff like creating the Event Dispatcher
      $this->handleEvent();
  
      // start the START classes...
      // this comes about as early as I can make it since it is key to making sure
      // singletons can be created right
      $this->handleStart();
      
      // start the EVENT classes...
      // this comes after start so the event subscribe classes can get singletons 
      $this->handleEventSubcribe();
      
      $event = new Event('framework.preHandle');
      $this->broadcastEvent($event);
      
    }catch(\ReflectionException $e){
      
      $this->handleRecovery($e);
      
      // re-handle the request...
      $ret_mixed = $this->preHandle();
      
    }//try/catch
  
    return $ret_mixed;
  
  }//method
  
  /**
   *  restore the framework more or less to the state it was in right after being created
   *
   *  @since  8-3-11
   */
  public function reset(){
    
    $event = new InfoEvent('Framework reset');
    $this->broadcastEvent($event);
    
    $event = new Event('framework.reset');
    $this->broadcastEvent($event);
    
    // de-register all the autoloaders this instance started...
    foreach($this->getField('autoload.instances',array()) as $instance){
      $instance->unregister();
    }//foreach
    
    $this->is_ready = false;
    
    // start all the objects over again...
    $this->instance_map = array();
  
  }//method
  
  /**
   *  call this method to actually handle the request
   *  
   *  once this method is called, everything is taken care of for you
   *  
   *  @return mixed usually null if left alone, but if you override anything, it could return almost anything      
   */
  public function handle(){
  
    try{
    
      $this->preHandle();
      
      $request = $this->getContainer()->getRequest();
  
      // decide where the request should be forwarded to...
      list($controller_class,$controller_method,$controller_method_params) = $this->getContainer()->getControllerSelect()->find(
        $request->getHost(),
        $request->getPath()
      );
      
      $event = new InfoEvent(
        sprintf(
          'Controller: %s::%s from host: %s, and path: %s',
          $controller_class,$controller_method,
          $request->getHost(),
          $request->getPath()
        )
      );
      $this->broadcastEvent($event);
      
      ///\out::e($request->getHost(),$request->getPath());
      ///\out::e($controller_class,$controller_method,$controller_method_params);
      
      $event = new Event('framework.handle');
      $this->broadcastEvent($event);

      $controller_response = $this->handleController($controller_class,$controller_method,$controller_method_params);
      $ret_mixed = $this->handleResponse($controller_response);
    
    }catch(\Exception $e){
    
      $ret_mixed = $this->handleException($e);
    
    }//try/catch
    
    return $ret_mixed;
  
  }//method

  /**
   *  handle any dependencies that need to be resolved before the framework can officially "start"
   *  
   *  @since  7-19-11
   */
  protected function handleDependencies(){
  
    $file_list = $this->getIncludes();
  
    foreach($file_list as $file){
  
      require_once($file);
      
    }//foreach
  
  }//method

  /**
   *  decide how to respond depending on how the controller returned
   *  
   *  this will actually output the response to the user
   *      
   *  if the controller returned...
   *    string - then that string will be sent to the user
   *    array - the array will be json encoded and sent as json to the user
   *    object - if the object has a __toString method then that will be sent to the user
   *    null - response object will be checked for content, if the response object
   *           has content, that will be sent, otherwise any fields set in the response
   *           object will be handed over to the template and the template will be rendered
   *           and sent to the user
   *    boolean - if false then don't output anything
   *
   *  @param  mixed $controller_response  what the controller returned
   */
  protected function handleResponse($controller_response = null){
  
    $container = $this->getContainer();
    $use_template = false;
    $response = $this->getContainer()->getResponse();
  
    $event = new FilterEvent(
      'framework.filter.controller_response',
      $controller_response,
      array('response' => $response)
    );
    
    $event = $this->broadcastEvent($event);
    $controller_response = $event->getParam();
  
    if(is_string($controller_response)){
    
      $event = new InfoEvent('Controller Response was a string, so returning that raw');
      $this->broadcastEvent($event);
    
      $response->setContent($controller_response);
    
    }else if(is_array($controller_response)){
    
      $event = new InfoEvent('Controller Response was an array, returning json');
      $this->broadcastEvent($event);
    
      $response->setContentType(Response::CONTENT_JSON);
      $response->setContent(json_encode($controller_response));
    
    }else if(is_object($controller_response)){
    
      if(method_exists($controller_response,'__toString')){
      
        $event = new InfoEvent('Controller Response was an object, returning __toString()');
        $this->broadcastEvent($event);
      
        $response->setContent((string)$controller_response);
      
      }else{
      
        throw new \RuntimeException(
          sprintf(
            'Controller returned an "%s" instance which has no __toString()',
            get_class($controller_response)
          )
        );
      
      }//if/else
    
    }else{
    
      // wipe the rendering if the controller returned false... 
      if($controller_response === false){
      
        $event = new InfoEvent('Controller Response was false, not using template');
        $this->broadcastEvent($event);
      
        $response->killTemplate();
        $response->setContent('');
        $use_template = false;
      
      }else{
      
        if(!$response->hasContent()){
      
          if($response->hasTemplate()){
          
            $use_template = true;
          
          }else{
          
            if(!$response->isRedirect()){
            
              $response->setContentType(Response::CONTENT_JSON);
              $response->setContent(json_encode($response->getFields()));
              
            }//if
          
          }//if/else
          
        }//if
        
      }//if/else
    
    }//if/else
    
    $response->send(); // send headers and content
    
    // handle outputting using the template...
    if($use_template){
    
      $ret_mix = $this->handleTemplate();
      
      // if a string was returned, set that into the response object...
      if(is_string($ret_mix)){ $response->setContent($ret_mix); }//if
      
    }//if
    
    return $response;
  
  }//method
  
  /**
   *  handle the template portion of the response
   *  
   *  @note this method will echo to the user
   *  
   *  @since  9-23-11
   */
  protected function handleTemplate(){
  
    $response = $this->getContainer()->getResponse();
    $template = $this->getContainer()->getTemplate();
  
    // update template with response values...
    $template->setTemplate($response->getTemplate());
    $template->addFields($response->getFields());
    
    $this->handleAssets();
  
    $event = new FilterEvent('framework.filter.template',$template);
    $event = $this->broadcastEvent($event);
    $template = $event->getParam();
    
    $event = new InfoEvent(sprintf('Using template: %s',$template->getTemplate()));
    $this->broadcastEvent($event);
    
    // output the template response to the screen...
    return $template->handle();

  }//method
  
  /**
   *  handle the automatic framework handling of the assets spread throughout the app
   *
   *  @since  9-27-11
   */
  protected function handleAssets(){

    // canary...
    if(!$this->hasField('asset_paths')){ return; }//if
  
    $container = $this->getContainer();
    $request = $this->getContainer()->getRequest();
    $config = $this->getContainer()->getConfig();
    $dest_path = new Path($config->getPublicPath(),'assets');
    
    // create the assets selector...
    $select = $container->getInstance('\Montage\Asset\Select');
    
    // create the global assets class that will handle everything...
    $assets = $container->getInstance($select->findCatchAll());
    
    $assets->setDestPath($dest_path);
    $assets->setPrefixPath(
      new Path(
        $request->getBasePath(),
        $config->getField('asset_prefix','assets')
      )
    );
    
    $assets->setSrcPaths($this->getField('asset_paths',array()));
    
    // create all the "other" asset classes...
    $class_name_list = $select->find();
    foreach($class_name_list as $class_name){
    
      $assets->add($container->createInstance($class_name));
    
    }//foreach
    
    $assets->handle();
    
    $event = new FilterEvent('framework.filter.assets',$assets);
    $event = $this->broadcastEvent($event);
    $assets = $event->getParam();
    
    $template = $this->getContainer()->getTemplate();
    $template->setField('assets',$assets);
  
  }//method

  /**
   *  start all the known \Montage\Autoload\Autoloadable classes
   *  
   *  a Start class is a class that will do configuration stuff
   *  
   *  @return array  a list of autoload instances      
   */
  protected function handleAutoload(){
  
    $container = $this->getContainer();
    $instances = new \SplObjectStorage();
    
    // create the standard autoloader...
    if($sal = $container->createInstance('\Montage\Autoload\StdAutoloader')){
    
      $sal->addPaths($this->getField('reflection_paths',array()));
      $sal->addPaths($this->getField('vendor_paths',array()));
      
      $sal->setCache($this->getCache());
      $sal->importCache();
      
      $sal->register();
      $instances->attach($sal);
      
    }//if
    
    // create any other autoloader classes...
    $select = $container->getInstance('\Montage\Autoload\Select');
    $class_list = $select->find();
    
    foreach($class_list as $class_name){
    
      $instance = $container->createInstance($class_name);
      
      $instance->register();
      $instances->attach($instance);
      
    }//foreach
    
    $this->setField('autoload.instances',$instances);
    
    return $instances;
     
  }//method
  
  /**
   *  handle event dispatch creation
   *   
   *  @since  8-25-11
   *  @return Dispatch
   */
  protected function handleEvent(){
  
    return $this->getEventDispatch();
  
  }//method
  
  /**
   *  start all the known \Montage\Event\Subscribeable classes
   *     
   *  an Event Subscribeable class is a class that automatically can subscribe to an event
   *  
   *  @since  10-15-11
   *  @return array a list of Event subscribe instances   
   */
  protected function handleEventSubcribe(){
  
    $container = $this->getContainer();
    
    // create the event sub selector...
    $select = $container->getInstance('\Montage\Event\Select');
    
    $class_list = $select->find();

    foreach($class_list as $i => $class_name){
    
      $class_list[$i] = $container->createInstance($class_name);
      $class_list[$i]->register();
      
    }//foreach
    
    return $class_list;
  
  }//method

  /**
   *  start all the known \Montage\Start\Startable classes
   *  
   *  a Start class is a class that will do configuration stuff
   *  
   *  @return array a list of Start instances      
   */
  protected function handleStart(){
  
    $instance_list = array();
    $env = $this->getField('env');
    $container = $this->getContainer();
    $select = $container->getInstance('\Montage\Start\Select');
    
    $event = new InfoEvent(sprintf('Using Start class selector: %s',get_class($select)));
    $this->broadcastEvent($event);
    
    $start_class_list = $select->find($env);

    foreach($start_class_list as $i => $class_name){
    
      $event = new InfoEvent(sprintf('Starting: %s',$class_name));
      $this->broadcastEvent($event);
    
      $instance_list[$i] = $container->createInstance($class_name);
      $instance_list[$i]->handle();
      
    }//foreach
    
    return $instance_list;
     
  }//method

  /**
   *  handle potential recovery of the framework
   *  
   *  basically, place the framework completely back into a virgin state, even more
   *  so than {@link reset()} because it will clear cache and stuff also
   *  
   *  @param  Exception $e  the exception that triggered the recovery
   *  @return boolean   
   */
  protected function handleRecovery(\Exception $e){
  
    $e_class_name = get_class($e);
    $e_key = sprintf('exception.%s',$e_class_name);
  
    if($old_e = $this->getField($e_key)){
        
      throw new \RuntimeException(
        sprintf(
          '%s Exception: "%s" already triggered a framework recovery and the problem was not fixed',
          $e_class_name,
          $old_e->getMessage()
        )
      );
    
    }else{
    
      $this->setField($e_key,$e);
    
    }//if/else
    
    // clear all the app cache...
    if($cache = $this->getCache()){ $cache->clear(); }//if
    
    // this should restart the framework...
    $this->reset();
    
    return true;
    
  }//method

  /**
   *  create a controller instance and call that instance's $method to handle the request
   *  
   *  @param  string  $class_name the controller class name
   *  @param  string  $method the method that will be called
   *  @param  array $params the arguments that will be passed to the $class_name->$method() call
   */
  protected function handleController($class_name,$method,array $params = array()){
  
    $container = $this->getContainer();
    
    // allow filtering of the controller info...
    $filter_map = array(
      'controller' => $class_name,
      'method' => $method,
      'params' => $params
    );
    $event = new FilterEvent('framework.filter.controller_info',$filter_map);
    $event = $this->broadcastEvent($event);
    
    $filter_map = $event->getParam();
    $class_name = $filter_map['controller'];
    $method = $filter_map['method'];
    $params = $filter_map['params'];
    
    $controller = $container->getInstance($class_name);
    
    $rmethod = new \ReflectionMethod($controller,$method);
    $rmethod_params = $this->normalizeControllerParams($rmethod,$params);
    
    $controller->preHandle();
    $ret_mixed = $rmethod->invokeArgs($controller,$rmethod_params);
    $controller->postHandle();
    
    return $ret_mixed;
  
  }//method
  
  /**
   *  get the controller params ready to be passed into the controller
   *  
   *  @since  7-19-11
   *  @see  handleController()
   *  @param  \ReflectionFunctionAbstract $rfunc  reflection of the controller method/function that is going to be called
   *  @param  array $params the params that were found that are being normalized
   *  @return array the $params normalized
   */
  protected function normalizeControllerParams(\ReflectionFunctionAbstract $rfunc,array $params){
  
    $container = $this->getContainer();
    $rparams = $rfunc->getParameters();
    $rfunc_params = array();
    $count = 0;
  
    // check for Forms and populate them if there are matching passed in vars...
    foreach($rparams as $index => $rparam){
    
      try{
    
        // if any param is an array, then it will take all the remainder passed in $params...
        // quick/nice way to do a catch-all...
        if($rparam->isArray()){
        
          $rfunc_params[$index] = array();
        
          if($count < count($params)){
        
            $rfunc_params[$index] = array_slice($params,$count);
            $params = array();
            
          }//if
        
        }else{
        
          $raw_param = isset($params[$index]) ? $params[$index] : null;
        
          // filter the creation of the object...
          if($rclass = $rparam->getClass()){
          
            // broadcast an event to give a chance to create the object instance...
            $event = new FilterEvent(
              'framework.filter.controller_param_create',
              $raw_param,
              array(
                'reflection_param' => $rparam,
                'container' => $container
              )
            );
            $event = $this->broadcastEvent($event);
            $filtered_param = $event->getParam();
          
            if($filtered_param !== null){
            
              // set the filtered param...
              $params[$index] = $filtered_param;
              
            }//if
          
          }//if
    
          $rfunc_params[$index] = $container->normalizeParam($rparam,$params);
          
          // filter the post-creation of the object...
          if(is_object($rfunc_params[$index])){
            
            $event = new FilterEvent(
              'framework.filter.controller_param_created',
              $rfunc_params[$index],
              array(
                'param' => $raw_param,
                'reflection_param' => $rparam,
                'container' => $container
              )
            );
            $event = $this->broadcastEvent($event);
            $rfunc_params[$index] = $event->getParam();
            
          }//if
          
        }//if/else
        
      }catch(\Exception $e){
      
        throw new \Montage\Exception\NotFoundException(
          sprintf(
            'wrapped %s exception: "%s" from %s:%s',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
          ),
          $e->getCode()
        );
      
      }//try/catch
    
      $count++;
    
    }//foreach
  
    return $rfunc_params;
  
  }//method
  
  /**
   *  handle a thrown exception
   *  
   *  @return boolean $use_template
   */
  protected function handleException(\Exception $e){

    $this->handleRecursion($e);
    
    $event = new InfoEvent('Handling Exception',array('e' => $e));
    $this->broadcastEvent($event);
  
    $ret_mixed = null;
  
    try{

      if($e instanceof \Montage\Exception\InternalRedirectException){
      
        list($controller_class,$controller_method,$controller_method_params) = $this->getContainer()->getControllerSelect()->find(
          $request->getHost(),
          $e->getPath()
        );
      
        $controller_response = $this->handleController($controller_class,$controller_method,$controller_method_params);
        $ret_mixed = $this->handleResponse($controller_response);
      
      }else if($e instanceof \Montage\Exception\RedirectException){
      
        $response = $this->getContainer()->getResponse();
        $redirect_url = $e->getUrl();
        $wait_time = $e->getWait();
        
        $response->killTemplate();
        $response->setContent('');
        $response->setStatusCode($e->getCode());
        $response->setHeader('Location',$redirect_url);
      
        if(headers_sent()){
  
          // http://en.wikipedia.org/wiki/Meta_refresh
          $response->setContent(
            sprintf('<meta http-equiv="refresh" content="%s;url=%s">',$wait_time,$redirect_url)
          );
  
        }else{
        
          if($wait_time > 0){ sleep($wait_time); }//if
          
        }//if/else
      
        $ret_mixed = $this->handleResponse(null);
      
      }else if($e instanceof Montage\Exception\StopException){
        
        // don't do anything, we're done
        $ret_mixed = $this->handleResponse(true);
        
      }else if($e instanceof \ReflectionException){
      
        $this->handleRecovery($e);
        
        // re-handle the request...
        $ret_mixed = $this->handle();
        
      }else{
        
        list($controller_class,$controller_method,$controller_method_params) = $this->getContainer()->getControllerSelect()->findException($e);
        
        $event = new InfoEvent(
          sprintf('Exception Controller: %s::%s',$controller_class,$controller_method)
        );
        $this->broadcastEvent($event);
        
        $controller_response = $this->handleController($controller_class,$controller_method,$controller_method_params);
        $ret_mixed = $this->handleResponse($controller_response);
        
      }//try/catch
      
    }catch(\Exception $e){
    
      $ret_mixed = $this->handleException($e);
    
    }//try/catch
  
    return $ret_mixed;
  
  }//method
  
  /**
   *  check for infinite recursion, throw an exception if found
   *  
   *  this is done by keeping an internal count of how many times this method has been called, 
   *  if that count reaches the max count then an exception is thrown
   *  
   *  @return integer the current count
   */
  protected function handleRecursion(\Exception $e){
  
    $max_ir_count = $this->getField('Framework.recursion_max_count',3);
    $ir_field = 'Framework.recursion_count'; 
    $ir_count = $this->getField($ir_field,0);
    if($ir_count > $max_ir_count){

      $e_msg = sprintf(
        'Infinite recursion suspected! The error handler has been called more than %s times, last exception (%s): %s',
        $max_ir_count,
        get_class($e),
        $e->getMessage()
      );
      
      ///trigger_error($e_msg,E_USER_ERROR);
      throw new \RuntimeException($e_msg,$e->getCode());
      
    }else{
    
      $ir_count = $this->bumpField($ir_field,1);
      
    }//if/else
    
    return $ir_count;
  
  }//method

  /**
   *  set the dependency injection container
   *  
   *  this is required for the Dependable interface and is best left alone for this
   *  particular class since this class will try to flush the cache and container and
   *  re do the request if it fails            
   *
   *  @param  Montage\Dependency\Container  $container
   */
  public function setContainer(\Montage\Dependency\Containable $container){
  
    $this->instance_map['container'] = $container;
    
    // just in case, container should know about this instance for circular-dependency goodness...
    $container->setInstance('framework',$this);
    $container->setInstance('cache',$this->getCache());
    
  }//method
  
  /**
   *  return the dependancy injection container 
   *
   *  @return Montage\Dependency\Container   
   */
  public function getContainer(){
  
    // canary...
    if(isset($this->instance_map['container'])){ return $this->instance_map['container']; }//if
  
    $this->preHandle();
    $reflection = $this->getReflection();
    $container_class_name = $reflection->findClassName('Montage\Dependency\FrameworkContainer');
    $container = new $container_class_name($reflection);
    
    $this->setContainer($container);
  
    return $container;
    
  }//method
  
  /**
   *  get the event dispatcher
   *
   *  @Param  Dispatch  $dispatch   
   */
  public function setEventDispatch(\Montage\Event\Dispatch $dispatch){
  
    $container = $this->getContainer();
    $container->setInstance('event_dispatch',$dispatch);
  
  }//method
  
  /**
   *  get the event dispatcher
   *  
   *  @since  8-25-11
   *  @return \Montage\Event\Dispatch
   */
  public function getEventDispatch(){
  
    $container = $this->getContainer();
    return $container->getEventDispatch();
     
  }//method
  
  /**
   *  just to make it a little easier to broadcast the event, and to also be able to 
   *  easily override event broadcast for this entire class
   *  
   *  @since  8-25-11            
   *  @return Event
   */
  public function broadcastEvent(Event $event){
  
    $dispatch = $this->getEventDispatch();
    return empty($dispatch) ? $event : $dispatch->broadcast($event);
  
  }//method
  
  /**
   *  create or return the caching object
   *  
   *  @since  7-6-11
   *  @return Montage\Cache\Cacheable instance
   */
  protected function getCache(){
  
    // canary...
    if(isset($this->instance_map['cache'])){ return $this->instance_map['cache']; }//if
  
    // create the caching object...
    $cache = new PHPCache();
    $cache->setPath($this->getField('cache_path'));
    $cache->setNamespace($this->getField('env'));
    $this->instance_map['cache'] = $cache;
  
    return $cache;
  
  }//method
  
  /**
   *  create or return the reflection object
   *  
   *  @since  7-6-11
   *  @return Montage\Cache\Cacheable instance
   */
  protected function getReflection(){
  
    // canary...
    if(isset($this->instance_map['reflection'])){ return $this->instance_map['reflection']; }//if
  
    // create reflection, load the cache...
    $reflection = new Reflection();
    $reflection->setCache($this->getCache());
    $reflection->importCache();
    
    $reflection->addPaths($this->getField('reflection_paths'));
    
    $this->instance_map['reflection'] = $reflection;

    return $reflection;
  
  }//method
  
  /**
   *  compile all the important framework paths
   *
   *  @todo this could be cached by saving the lists and then pulling them in and
   *  just seeing if the created path matches one in the list, that would save the is_file
   *  checks      
   *      
   *  @since  6-27-11
   */
  protected function handlePaths(){
  
    // canary...
    $app_path = $this->getField('app_path');
    if(empty($app_path)){
      throw new \UnexpectedValueException('->getField("app_path") failed');
    }//if

    $framework_path = new Path(__DIR__,'..','..');
    $this->setField('framework_path',$framework_path);
    
    if(!$this->hasField('cache_path')){
      
      $path = new Path($app_path,'cache');
      $path->assure();
      $this->setField('cache_path',$path);
      
    }//if
    
    $reflection_path_list = array();
    $view_path_list = array();
    $vendor_path_list = array();
    $assets_path_list = array();
    $plugins_path_list = array();
    
    $path = new Path($framework_path,'src');
    $reflection_path_list[] = $path;
    
    $path = new Path($app_path,'src');
    if($path->exists()){ $reflection_path_list[] = $path; }//if
    
    $path = new Path($app_path,'config');
    if($path->exists()){ $reflection_path_list[] = $path; }//if
    
    $path = new Path($app_path,'view');
    if($path->exists()){ $view_path_list[] = $path; }//if
    
    $path = new Path($app_path,'vendor');
    if($path->exists()){ $vendor_path_list[] = $path; }//if
    
    // add the plugin paths...
    $plugin_base_path_list = array();
    $plugin_base_path_list[] = new Path($framework_path,'plugins');
    $plugin_base_path_list[] = new Path($app_path,'plugins');
    foreach($plugin_base_path_list as $plugin_base_path){
      
      if($plugin_base_path->isDir()){
        
        foreach($plugin_base_path->createIterator('',1) as $plugin_path => $plugin_dir){
        
          if($plugin_dir->isDir()){
          
            $plugin_name = $plugin_dir->getBasename();
            $plugins_path_list[] = $plugin_dir;
          
            $path = new Path($plugin_path,'config');
            if($path->exists()){
              
              $reflection_path_list[] = $path;
              
            }//if
            
            $path = new Path($plugin_path,'src');
            if($path->exists()){
              
              $reflection_path_list[] = $path;
              
            }//if 
          
            $path = new Path($plugin_path,'view');
            if($path->exists()){ $view_path_list[] = $path; }//if
            
            $path = new Path($plugin_path,'vendor');
            if($path->exists()){
              
              $vendor_path_list[] = $path;
            
            }//if
            
            $path = new Path($plugin_path,'assets');
            if($path->exists()){ $assets_path_list[] = $path; }//if
          
          }//if
        
        }//foreach
        
      }//if
      
    }//foreach
  
    $path = new Path($app_path,'assets');
    if($path->exists()){ $assets_path_list[] = $path; }//if
  
    $this->setField('reflection_paths',$reflection_path_list);
    $this->setField('view_paths',$view_path_list);
    $this->setField('vendor_paths',$vendor_path_list);
    $this->setField('asset_paths',$assets_path_list);
    $this->setField('plugin_paths',$plugins_path_list);

  }//method
  
  /**
   *  return a list of files that need to be included
   *  
   *  sometimes, things need to be included before all the autoloaders have been loaded 
   *  (eg, you have extended the autoloader with a custom class), these files will be 
   *  loaded before the autoloaders
   *  
   *  @since  7-19-11
   *  @see  handleDependencies()
   *  @return array
   */
  protected function getIncludes(){ return array(); }//method
   
}//method
