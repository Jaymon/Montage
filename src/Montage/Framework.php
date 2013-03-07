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
 *  The classes this class creates, that means these classes are harder to override and make
 *  the child class be automatically picked up, this is because 3 of those classes are used
 *  to make the Dependency Injection Container work:
 *    \Montage\Reflection\ReflectionFramework
 *    \Montage\Event\Dispatch
 *    \Montage\Cache\Cache
 *    \Montage\Dependency\Container
 *    \Path
 *    \Profile
 *    \Montage\Config\FrameworkConfig   
 *   
 *  @version 0.8
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
namespace Montage;

use Montage\Cache\PHPCache;
use Path;
use Profile;
use Montage\Field\Field;
use Montage\Config\FrameworkConfig;

use Montage\Reflection\ReflectionFramework;
use Montage\Dependency\Container;
use Montage\Dependency\Dependable;

use Montage\Autoload\FrameworkAutoloader;

use Montage\Request\Requestable;
use Montage\Response\Response;
use Montage\Response\Template;

use Montage\Event\Event;
use Montage\Event\InfoEvent;
use Montage\Event\FilterEvent;
use Montage\Event\Eventable;
use Montage\Event\Dispatch as EventDispatch;

// the files that needed to be included outside the montage specific autoloader...
require_once(__DIR__.'/../../plugins/Utilities/src/CallbackFilterIterator.php');
require_once(__DIR__.'/../../plugins/Utilities/src/FlattenArrayIterator.php');
require_once(__DIR__.'/../../plugins/Utilities/src/Path.php');
require_once(__DIR__.'/../../plugins/Utilities/src/Profile.php');

// load the Framework autoloader, this will handle all other dependencies to load this class
// so I don't have to have a ton of includes() right here...
require_once(__DIR__.'/Autoload/Autoloadable.php');
require_once(__DIR__.'/Autoload/Autoloader.php');
require_once(__DIR__.'/Autoload/FrameworkAutoloader.php');

$fal = new FrameworkAutoloader('Montage',realpath(__DIR__.'/..'));
$fal->register();

/// require_once('/vagrant/public/out_class.php'); \out::h();

class Framework extends Field implements Dependable,Eventable {

  const DEBUG_OFF = 0;
  const DEBUG = 1;
  const DEBUG_PROFILE = 2;
  
  /**
   *  turn on all debugging
   *  
   *  this should be the sum of all the DEBUG_* constants
   *  
   *  @see  __construct()   
   *  @var  integer
   */
  const DEBUG_ALL = 3;

  /**
   *  @since  12-20-11  
   *  @see  preHandle()
   *  @var  integer
   */
  const HANDLE_PRE = 1;
  
  /**
   *  @since  12-20-11  
   *  @see  handlePaths()
   *  @var  integer
   */
  const HANDLE_PATHS = 2;

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
   *  @var  integer
   */
  protected $is_ready = 0;
  
  protected $config_class_name = 'Montage\\Config\\FrameworkConfig';
  
  protected $reflection_class_name = 'Montage\\Reflection\\ReflectionFramework';
  
  protected $cache_class_name = 'Montage\\Cache\\PhpCache';
  
  protected $event_dispatch_class_name = 'Montage\\Event\\Dispatch';
  
  protected $profile_class_name = 'Profile';

  /**
   *  create this object
   *  
   *  @param  string  $env  the environment, usually something like "dev" or "prod"
   *  @param  string  $app_path the root path for your application
   *  @param  integer $debug_level  what level of debug you want   
   */
  public function __construct($env,$app_path,$debug_level = self::DEBUG_ALL){
  
    // canary...
    if(empty($env)){
      throw new \InvalidArgumentException('$env was empty, please set $env to something like "dev" or "prod"');
    }//if
    if(empty($app_path)){
      throw new \InvalidArgumentException('$app_path was empty, please set it to the root path of your app');
    }//if
    
    // first handle any files the rest of the framework might depend on...
    $this->handleDependencies();
    
    // if anything changes right here, remember it needs to be changed in reset() also...
    $config = $this->getConfig();
    $config->setField('app_path',$app_path);
    $config->setField('env',$env);
    $config->setField('debug_level',(int)$debug_level);
    
    // set framework specific things...
    $this->setField('framework.debug_level',$debug_level);
    $this->setField('framework.recursion_max_count',3);
    
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
    if($this->isHandled(self::HANDLE_PRE)){ return true; }//if
  
    $this->profileStart(__FUNCTION__);
    
    $ret_mixed = true;
    
    // this needs to be the first thing done otherwise preHandle() will keep getting
    // called again and again as other methods call this method to make sure everything
    // is ready...
    $this->setHandled(self::HANDLE_PRE);
  
    try{
    
      // collect all the paths we're going to use...
      $this->handlePaths();
  
      // handle loading the config files...
      $this->handleConfig();
      
      // start the autoloaders...
      // this is the first thing done because it is needed to make sure all the classes
      // can be found
      $this->handleAutoload();
      
      // container should now exist
      
      // start the event SUBSCRIBE classes...
      $this->handleEvent();

      // it should be safe to create any singleton now

      $event = new Event('framework.preHandle');
      $this->broadcastEvent($event);
      
    }catch(\ReflectionException $e){
      
      $this->handleRecovery($e);
      
      // re-handle the request...
      $ret_mixed = $this->preHandle();
      
    }catch(\Exception $e){
    
      // the framework never made it to being ready, so we can't reliably do any error handling
      $this->is_ready = 0;
      throw $e;
    
    }//try/catch
    
    $this->profileStop();
  
    return $ret_mixed;
  
  }//method
  
  /**
   *  restore the framework more or less to the state it was in right after being created
   *
   *  @since  8-3-11
   */
  public function reset(){

    // we do all the events before reseting because at the end of this method nothing
    // will be listening to events anymore
    
    $event = new Event('framework.reset');
    $this->broadcastEvent($event);
    
    $event = new InfoEvent('Framework reset');
    $this->broadcastEvent($event);
    
    $this->is_ready = 0;
    
    // save config values we are going to need on any rerun
    $config = $this->getConfig();
    $config_field_map = array(
      'app_path' => $config->getField('app_path'),
      'env' => $config->getField('env'),
      'debug_level' => $config->getField('debug_level')
    );
    
    // start all the objects over again...
    $this->instance_map = array();
  
    // de-register all the autoloaders this instance started...
    if($instances = $this->getField('autoload.instances')){
      
      foreach($instances as $instance){ $instance->unregister(); }//foreach
      
    }//if
  
    // now reset the config with the saved values...
    $config = $this->getConfig();
    $config->addFields($config_field_map);
  
  }//method
  
  /**
   *  call this method to actually handle the request
   *  
   *  once this method is called, everything is taken care of for you
   *  
   *  @return mixed usually the Response object if left alone, but if you override {@link handleResponse()}, 
   *                it could return almost anything      
   */
  public function handle(){
  
    $this->profileStart(__FUNCTION__);
  
    try{
    
      $this->preHandle();

      $container = $this->getContainer();
      $request = $this->getContainer()->getRequest();
      $controller_response = $this->handleRequest($request->getPath());
      $ret_mixed = $this->handleResponse($controller_response);
      
      $event = new Event('framework.handle.stop');
      $this->broadcastEvent($event);
    
    }catch(\Exception $e){

      $ret_mixed = $this->handleError($e);
    
    }//try/catch
    
    $this->profileStop();
    
    return $ret_mixed;
  
  }//method

  /**
   *  handle any dependencies that need to be resolved before the framework can officially "start"
   *  
   *  @since  7-19-11
   */
  protected function handleDependencies(){
  
    $file_list = $this->getIncludes();
  
    // we use require_once because this can be called more than once (framework resets, another
    // Framework instance being created)
    foreach($file_list as $file){ require_once($file); }//foreach
  
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
   *  @return \Montage\Response\Response   
   */
  protected function handleResponse($controller_response = null){
  
    $container = $this->getContainer();
    $use_template = false;
    $response = $this->getContainer()->getResponse();
    
    // set a default title if one hasn't been set...
    if(!$response->hasTitle()){
      $request = $this->getContainer()->getRequest();
      $response->setTitle($request->getPath());
    }//if
  
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

      if($controller_response instanceof \Exception){

        throw $controller_response;

      }else{

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
    
    $response->sendHeaders();
    ///$response->send(); // send headers and content
    
    // handle outputting using the template...
    if($use_template){
    
      $ret_mix = $this->handleTemplate();
      
      // if a string was returned, set that into the response object...
      if(is_string($ret_mix)){ $response->setContent($ret_mix); }//if
      
    }else{
    
      $response->sendContent();
    
    }//if/else
    
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

    $container = $this->getContainer();
    $config = $this->getConfig();

    // canary...
    if(!$config->hasField('assets_paths')){ return; }//if
  
    $request = $container->getRequest();
    
    $dest_path = new Path($config->getPublicPath(),'assets');
    
    // create the global assets class that will handle app asset management...
    $assets = $container->getAssets();
    
    // create the assets selector that will be used to compile the other assets...
    $select = $container->getInstance('\\Montage\\Asset\\Select');
    
    $assets->setDestPath($dest_path);
    
    $assets->setPrefixPath(
      new Path(
        $request->getBasePath(),
        $config->getField('asset_prefix','assets')
      )
    );
    
    $assets->setSrcPaths($config->getField('assets_paths',array()));
    
    // create all the "other" asset classes...
    $class_name_list = $select->find();
    foreach($class_name_list as $class_name){
    
      $assets->add($container->createInstance($class_name));
    
    }//foreach
    
    $assets->handle();
    
    $event = new FilterEvent('framework.filter.assets',$assets);
    $event = $this->broadcastEvent($event);
    $assets = $event->getParam();

    ///echo '<pre>',print_r($assets->get()),'</pre>';
    
    ///\out::i($assets);
    
    /* foreach($assets->get() as $k => $a){
    
      \out::e($k);
    
    }//foreach */
  
    /*
    $f = new \FlattenArrayIterator($assets->get());
    foreach($f as $k => $v){
    
      \out::e($k);
      \out::e($v);
    
    }*/
    
    ///\out::x();
    
    return $assets;
  
  }//method

  /**
   *  start all the known \Montage\Autoload\Autoloadable classes
   *  
   *  @return array  a list of autoload instances      
   */
  protected function handleAutoload(){
  
    $container = $this->getContainer();
    $config = $this->getConfig();
    $instances = new \SplObjectStorage();
    
    // create the standard autoloader...
    if($sal = $container->getAutoloader()){
    
      $sal->addPaths($config->getField('src_paths',array()));
      $sal->addPaths($config->getField('vendor_paths',array()));
      
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
   *  handle loading the config files
   *      
   *  @since  11-23-11
   *  @return \Montage\Config\FrameworkConfig
   */
  protected function handleConfig(){
  
    $config = $this->getConfig();
    $config->addPaths($config->getField('config_paths',array()));
  
    $config_path = new Path($config->getAppPath(),'config');
    
    if($config_path->isDir()){
      
      $regex_list = array(
        '#config\.\S+$#i', // load any global config.* config files
        sprintf('#%s\.\S+$#i',preg_quote($config->getEnv(),'#')) // load any <env>.* config files
      );
      
      foreach($regex_list as $regex){
      
        $config_files = $config_path->createFileIterator($regex,1);
        foreach($config_files as $config_file){

          $event = new InfoEvent(
            sprintf(
              'loading config path: %s',
              $config_file
            )
          );
          $this->broadcastEvent($event);
        
          $config->load($config_file,$config->getField('app_path'));
        
        }//foreach
        
      }//foreach
      
    }//if
    
    // @todo  check the reflection to decide if we should upgrade this object to a user
    // specified object
  
    return $config;
  
  }//method
  
  /**
   *  start all the known \Montage\Event\Subscribeable classes
   *     
   *  an Event Subscribeable class is a class that automatically can subscribe to an event
   *  
   *  @since  handleEvent: 8-25-11, handleEventSubscribe: 10-15-11, combined: 12-13-11
   *  @return array a list of Event subscribe instances   
   */
  protected function handleEvent(){
  
    $container = $this->getContainer();
    
    // create the event sub selector...
    $select = $container->getInstance('\\Montage\\Event\\Select');
    
    $class_list = $select->find();

    foreach($class_list as $i => $class_name){
    
      $class_list[$i] = $container->createInstance($class_name);
      $class_list[$i]->register();
      
    }//foreach
    
    return $class_list;
  
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
  
    if($e_instance = $this->getField($e_key)){
        
      throw new \RuntimeException(
        sprintf(
          '%s Exception: "%s" already triggered a framework recovery and the problem was not fixed',
          $e_class_name,
          $e_instance->getMessage()
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
   * given a path do all the request related stuff, including calling the controller
   * to handle the request
   *
   * @param string  $path the path that is being requested
   * @param array $params any request params (get parms, post params) that might be handy
   * @return \Montage\Request
   */
  protected function handleRequest($path, array $params = array()){

    $container = $this->getContainer();
    $request = $container->getRequest();

    // decide where the request should be forwarded to...
    list($controller_class, $controller_methods, $controller_params) = $container
      ->getControllerSelect()
        ->find(
          $request->getMethod(),
          $request->getHost(),
          $path,
          $params
        );
    
    ///\out::i($container->getInstance('\Montage\Reflection\ReflectionFramework'));
    ///\out::e($request->getMethod(),$request->getHost(),$request->getPath());
    ///\out::e($controller_class,$controller_methods,$controller_params);
    
    $event = new Event('framework.handle');
    $this->broadcastEvent($event);

    $controller_response = $this->handleController($controller_class, $controller_methods, $controller_params);

    // we don't want to drop into views on a command, so we'll default to false to turn off templates
    if($request->isCli()){
      if($controller_response === null){ $controller_response = false; }//if
    }//if

    return $controller_response;

  }//method

  /**
   *  create a controller instance and call that instance's $method to handle the request
   *  
   *  @param  string  $class_name the controller class name
   *  @param  string  $method the method that will be called
   *  @param  array $params the arguments that will be passed to the $class_name->$method() call
   */
  protected function handleController($class_name, $methods, array $params = array()){
  
    ///\out::e($class_name,$method,$params);
  
    $container = $this->getContainer();
    
    // allow filtering of the controller info...
    $filter_map = array(
      'controller' => $class_name,
      'methods' => $methods,
      'params' => $params
    );
    $event = new FilterEvent('framework.filter.controller_info', $filter_map);
    $event = $this->broadcastEvent($event);
    
    $filter_map = $event->getParam();
    $class_name = $filter_map['controller'];
    $methods = $filter_map['methods'];
    $params = $filter_map['params'];
    
    $controller = $container->getInstance($class_name);
    
    $controller->preHandle();
    foreach($methods as $method){
      $rmethod = new \ReflectionMethod($controller, $method);
      $rmethod_params = $this->normalizeControllerParams($rmethod, $params);
      $ret_mixed = $rmethod->invokeArgs($controller, $rmethod_params);
      if($ret_mixed != null){ break; }//if

    }//foreach
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
          $event_class_list = array();
        
          // filter the creation of the object...
          if($rclass = $rparam->getClass()){
          
            $reflection = $this->getReflection();
            $event_class_list = $reflection->getRelated($rclass->getName());
            $event_class_list[] = '';
          
            foreach($event_class_list as $event_class_name){
            
              $event_name = 'framework.filter.controller_param_create';
              if(!empty($event_class_name)){ $event_name .= ':'.$event_class_name; }//if

              $dispatch = $this->getEventDispatch();
            
              // broadcast an event to give a chance to create the object instance...
              $event = new FilterEvent(
                $event_name,
                $raw_param,
                array(
                  'reflection_param' => $rparam,
                  'container' => $container
                )
              );
              $event = $this->broadcastEvent($event);
              
              // if we've done something to the param then go ahead and end looking for a creation event
              if($event->changedParam()){
              
                $filtered_param = $event->getParam();
                $params[$index] = $filtered_param;
                break;
              
              }//if
              
            }//foreach
          
          }else{
          
            // broadcast an event to give a chance to modify the controller param...
            $event = new FilterEvent(
              'framework.filter.controller_param',
              $raw_param,
              array(
                'reflection_param' => $rparam,
                'container' => $container
              )
            );
            $event = $this->broadcastEvent($event);
            
            $filtered_param = $event->getParam();
            if($event->changedParam()){ $params[$index] = $filtered_param; }//if
          
          }//if/else
    
          $rfunc_params[$index] = $container->normalizeParam($rparam,$params);
          
          // filter the post-creation of the object...
          if(is_object($rfunc_params[$index])){
          
            foreach($event_class_list as $event_class_name){
            
              $event_name = 'framework.filter.controller_param_created';
              if(!empty($event_class_name)){ $event_name .= ':'.$event_class_name; }//if
            
              $event = new FilterEvent(
                $event_name,
                $rfunc_params[$index],
                array(
                  'param' => $raw_param,
                  'reflection_param' => $rparam,
                  'container' => $container
                )
              );
              $event = $this->broadcastEvent($event);
              $rfunc_params[$index] = $event->getParam();
              
            }//foreach
            
          }//if
          
        }//if/else
        
      }catch(\Montage\Exception\HttpException $e){
      
        // just rethrow http exceptions since a NotFoundException is just a fancy HttpException...
        throw $e;
      
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
  protected function handleError(\Exception $e){

    // canary...
    $this->handleRecursion($e);
    
    $event = new InfoEvent('Handling Exception',array('e' => $e));
    $this->broadcastEvent($event);
    
    $e_list = $this->getField('e_list',array());
    $e_list[] = $e;
    $this->setField('e_list',$e_list);
  
    $ret_mixed = null;
  
    try{

      if($e instanceof \Montage\Exception\InternalRedirectException){
      
        $controller_response = $this->handleRequest($e->getPath());
        $ret_mixed = $this->handleResponse($controller_response);
      
      }else if($e instanceof \Montage\Exception\RedirectException){
      
        $response = $this->getContainer()->getResponse();
        $redirect_url = $e->getUrl();
        $wait_time = $e->getWait();
        
        $response->killTemplate();
        $response->setContent('');
        $response->setStatusCode($e->getCode());
        $response->setHeader('Location',$redirect_url);
        $controller_response = null;
      
        if(headers_sent()){
  
          // http://en.wikipedia.org/wiki/Meta_refresh
          $response->setContent(
            sprintf('<meta http-equiv="refresh" content="%s;url=%s">',$wait_time,$redirect_url)
          );
  
          $controller_response = null;
  
        }else{
        
          if($wait_time > 0){ sleep($wait_time); }//if
          
          $controller_response = false;
          
        }//if/else
        
        $ret_mixed = $this->handleResponse($controller_response);
      
      }else if($e instanceof Montage\Exception\StopException){
        
        // don't do anything, we're done
        $ret_mixed = $this->handleResponse($e->getControllerResponse());
        
      }else if($e instanceof \ReflectionException){
      
        $this->handleRecovery($e);
        
        // re-handle the request...
        $ret_mixed = $this->handle();
        
      }else{
        
        // TODO: maybe clear cache?

        $event = new FilterEvent('framework.handleError', $e);
        $event->setField('e_list', $e_list);
        $this->broadcastEvent($event);
        $ret_mixed = $this->handleResponse($event->getParam()); // not sure this is best choice, 
        
      }//try/catch
      
    }catch(\Exception $e){

      // TODO: maybe clear cache?
    
      if($this->isHandled(self::HANDLE_PRE)){
    
        $ret_mixed = $this->handleError($e);
        
      }else{
      
        // we failed to handle the exception and the framework isn't ready, so just throw
        // the exception because there is a very high probability it will fail handling
        // the exception again...
        throw $e;
      
      }//if/else
    
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
  
    $max_ir_count = $this->getField('framework.recursion_max_count');
    $ir_field = 'framework.recursion_count';
    $ir_count = $this->getField($ir_field,0);
      
    if($ir_count > $max_ir_count){

      $e_msg = sprintf(
        'Infinite recursion suspected! The error handler has been called more than %s times, last exception (%s): %s',
        $max_ir_count,
        get_class($e),
        $e->getMessage()
      );
      
      \out::i($this);
      
      ///trigger_error($e_msg,E_USER_ERROR);
      throw new \RuntimeException($e_msg,$e->getCode());
      
    }else{
    
      $ir_count += 1;
      $this->setField($ir_field,$ir_count);
      
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
   *  @param  \Montage\Dependency\Container  $container
   */
  public function setContainer(\Montage\Dependency\Containable $container){
  
    // canary
    if(!$this->isHandled(self::HANDLE_PATHS)){ $this->handlePaths(); }//if
  
    $this->instance_map['container'] = $container;
    
    // just in case, container should know about this instance for circular-dependency goodness...
    $container->setInstance('framework',$this);
    
    // set the other singletons (none of these can be created by the container)...
    $container->setInstance('event_dispatch',$this->getEventDispatch());
    $container->setInstance('cache',$this->getCache());
    $container->setInstance('profile',$this->getProfile());
    $container->setInstance('config',$this->getConfig());
    
  }//method
  
  /**
   *  return the dependancy injection container 
   *
   *  @return Montage\Dependency\Container   
   */
  public function getContainer(){
  
    // canary...
    if(isset($this->instance_map['container'])){ return $this->instance_map['container']; }//if
    if(!$this->isHandled(self::HANDLE_PATHS)){ $this->handlePaths(); }//if
  
    $reflection = $this->getReflection();
    $container_class_name = $reflection->findClassName('\\Montage\\Dependency\\FrameworkContainer');
    $container = new $container_class_name($reflection);
    
    $this->setContainer($container);
  
    return $container;
    
  }//method
  
  /**
   *  get the event dispatcher
   *
   *  @Param  \Montage\Event\Dispatch $dispatch   
   */
  public function setEventDispatch(\Montage\Event\Dispatch $dispatch){
  
    $this->instance_map['event_dispatch'] = $dispatch;
    
  }//method
  
  /**
   *  get the event dispatcher
   *  
   *  @since  8-25-11
   *  @return \Montage\Event\Dispatch
   */
  public function getEventDispatch(){
  
    // canary...
    if(isset($this->instance_map['event_dispatch'])){ return $this->instance_map['event_dispatch']; }//if
  
    $event_dispatch = new $this->event_dispatch_class_name();
    $this->setEventDispatch($event_dispatch);
    
    return $event_dispatch;
  
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
   *  get the profile instance
   *  
   *  @see  profileStart(), profileStop()   
   *  @since  10-28-11   
   *  @return \Profile
   */
  public function getProfile(){
  
    if(isset($this->instance_map['profile'])){ return $this->instance_map['profile']; }//if
    
    $profile = new $this->profile_class_name();
    $this->instance_map['profile'] = $profile;
    
    return $profile;
  
  }//method
  
  /**
   *  set the config instance this framework will use
   *  
   *  @since  11-26-11
   *  @param  \Montage\Config\FrameworkConfig $config
   */
  public function setConfig(FrameworkConfig $config){ $this->instance_map['config'] = $config; }//method
  
  /**
   *  create or return the framework config object
   *  
   *  @see  http://teddziuba.com/2011/06/most-important-concept-systems-design.html
   *    the framework config should be our Single Point of Truth
   *      
   *  @since  11-23-11
   *  @return \Montage\Config\FrameworkConfig instance
   */
  protected function getConfig(){
  
    // canary...
    if(isset($this->instance_map['config'])){ return $this->instance_map['config']; }//if

    $config = new $this->config_class_name();
    $this->setConfig($config);
  
    return $config;
  
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
  
    $config = $this->getConfig();
    if(!$config->hasField('cache_path')){
      throw new \UnexpectedValueException('$config has no "cache_path" field');
    }//if
  
    // create the caching object...
    $cache = new $this->cache_class_name();
    $cache->setPath($config->getField('cache_path'));
    $cache->setNamespace($config->getField('env'));
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
  
    $config = $this->getConfig();
  
    // create reflection, load the cache...
    $reflection = new $this->reflection_class_name();
    $reflection->setCache($this->getCache());
    $reflection->importCache();
    
    $reflection->addPaths($config->getField('src_paths',array()));
    
    $this->instance_map['reflection'] = $reflection;

    return $reflection;
  
  }//method
  

  /**
   * recursively compile the paths of a base_path
   *
   * since plugins can have plugins, etc. this method will recursively compile
   * all the valid montage paths that are needed for the app
   *
   * @param mixed $base_path
   * @param array $paths
   * @return array  the compiled list of paths
   */
  protected function handlePathBase($base_path, array $paths){

    $path_bits = array('src', 'config', 'view', 'vendor', 'assets', 'test');
    foreach($path_bits as $path_bit){
      if(!isset($paths[$path_bit])){ $paths[$path_bit] = array(); }//if

      $path = new Path($base_path, $path_bit);
      if($path->exists()){ $paths[$path_bit][] = $path; }//if

    }//foreach

    // add the plugin paths...
    $path_bit = 'plugins';
    if(!isset($paths[$path_bit])){ $paths[$path_bit] = array(); }//if

    $plugin_base_path = new Path($base_path, $path_bit);
    if($plugin_base_path->isDir()){
      
      foreach($plugin_base_path->createIterator('', 1) as $plugin_path => $plugin_dir){
      
        if($plugin_dir->isDir()){
          ///$plugin_name = $plugin_dir->getBasename();
          $paths = $this->handlePathBase($plugin_dir, $paths);
          $paths[$path_bit][] = $plugin_dir;
        
        }//if
      
      }//foreach
      
    }//if

    return $paths;
    
  }//method

  /**
   *  compile all the important framework paths
   *
   *  before this is called, the config instance is not aware of all the paths in the app
   *
   *  @todo this could be cached by saving the lists and then pulling them in and
   *  just seeing if the created path matches one in the list, that would save 
   *  the is_file checks
   *
   *  @since  6-27-11
   */
  protected function handlePaths(){
  
    // canary
    if($this->isHandled(self::HANDLE_PATHS)){ return; }//if

    $this->profileStart(__FUNCTION__);
  
    $this->setHandled(self::HANDLE_PATHS);
    $config = $this->getConfig();
  
    // canary...
    $app_path = $config->getField('app_path');
    if(empty($app_path)){
      throw new \UnexpectedValueException('$config->getField("app_path") failed');
    }//if

    $framework_path = new Path(__DIR__, '..', '..');
    $config->setField('framework_path', $framework_path);
    
    if(!$config->hasField('cache_path')){
      $path = new Path($app_path, 'cache');
      $path->assure();
      $config->setField('cache_path', $path);
      
    }//if

    $paths = $this->handlePathBase($framework_path, array());
    $paths = $this->handlePathBase($app_path, $paths);

    // reverse some paths so framework will be the dominant path
    // TODO: it would probably be better that functionality like what template was used
    // isn't so dependant on order, or at least move this into the template handlers
    foreach(array('view', 'test') as $path_bit){
      $paths[$path_bit] = array_reverse($paths[$path_bit]);
    }//foreach

    // save all the paths into the config so the will be available app wide
    // \out::e($paths);
    foreach($paths as $path_bit => $path_list){
      $config->setField(sprintf('%s_paths', $path_bit), $path_list);
    }//foreach

    $this->profileStop();

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
  
  /**
   *  start profiling a block of code
   *  
   *  @see  profileStop()   
   *  @since  10-28-11
   *  @param  string  $title  the name of the profiled block of code   
   *  @return boolean
   */
  protected function profileStart($title){
  
    if(($this->getField('framework.debug_level',0) & self::DEBUG_PROFILE) === 0){ return false; }//if 
  
    $profiler = $this->getProfile();
    return $profiler->start($title);
  
  }//method
  
  /**
   *  stop profiling a block of code
   *  
   *  @see  profileStart()   
   *  @since  10-28-11   
   *  @return boolean
   */
  protected function profileStop(){
  
    if(($this->getField('framework.debug_level',0) & self::DEBUG_PROFILE) === 0){ return false; }//if
    
    $profiler = $this->getProfile();
    return $profiler->stop();
  
  }//method
  
  /**
   *  true if $val has been called
   *  
   *  @since  12-20-11
   *  @param  integer $val  the HANDLE_* constant to check
   *  @return boolean            
   */
  protected function isHandled($val){ return $this->is_ready & $val; }//method
  
  /**
   *  set $val as handled
   *  
   *  @since  12-20-11
   *  @param  integer $val  the HANDLE_* constant to check        
   */
  protected function setHandled($val){ $this->is_ready |= $val; }//method
   
}//method
