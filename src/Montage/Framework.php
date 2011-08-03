<?php
/**
 *  the kernal/core that translates the request to the response
 *  
 *  other names: handler, sequence, assembler, dispatcher, scheduler
 *  http://en.wikipedia.org/wiki/Montage_%28filmmaking%29  
 *  
 *  
 *  This class creates a lot of new instances
 *  we shouldn't have "new" inside the class, but sometimes you just have to break the rules 
 *  to make things easier, I didn't want to have to create a Cache and Reflection 
 *  instance to pass in here to resolve all the dependencies...
 *  see: http://misko.hevery.com/2008/07/08/how-to-think-about-the-new-operator/ for how
 *  I'm wrong about this, but convenience trumps rightness in this instance for me  
 *   
 *  @version 0.7
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
namespace Montage;

use Montage\Cache\PHPCache; ///use Montage\Cache\Cache;
use Montage\Path;
use Montage\Field\Field;

use Montage\Exception\NotFoundException;
use Montage\Exception\InternalRedirectException;
use Montage\Exception\RedirectException;
use Montage\Exception\StopException;
use Exception;

use out;

use Montage\Dependency\Reflection;
use Montage\Dependency\Container;
use Montage\Dependency\Dependable;

use Montage\AutoLoad\ReflectionAutoloader;
use Montage\AutoLoad\FrameworkAutoloader;

use Montage\Response\Response;
use Montage\Response\Template;

// load the Framework autoloader, this will handle all other dependencies to load this class...
require_once(__DIR__.'/AutoLoad/AutoLoadable.php');
require_once(__DIR__.'/AutoLoad/AutoLoader.php');
require_once(__DIR__.'/AutoLoad/FrameworkAutoloader.php');
$fal = new FrameworkAutoloader('Montage',realpath(__DIR__.'/..'));
$fal->register();

class Framework extends Field implements Dependable {


  /**
   *  holds any important internal instances this class is going to use
   *
   *  @since  7-6-11  changed from individual protected instance vars to this array
   *  @var  array
   */
  protected $instance_map = array();

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
    
    // collect all the paths we're going to use...
    $this->compilePaths($app_path);
    
    $this->setField('env',$env);
    $this->setField('debug_level',$debug_level);
    
  }//method
  
  /**
   *  this handles all the initial configuration of the framework
   *  
   *  handy for when you want to activate the framework but don't want it to handle the request   
   *
   *  old name: handleConfig()
   *
   *  @since  7-28-11
   */
  public function activate(){
  
    // canary...
    ///if($this->getField('Framework.is_activated',false)){ return true; }//if
  
    // first handle any files the rest of the framework might depend on...
    $this->handleDependencies();

    // start the autoloaders...
    $this->handleAutoload();

    // start the START classes...
    $this->handleStart();
    
    ///$this->setField('Framework.is_activated',true);
  
    return true;
  
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
    
      $this->activate();
      
      $request = $this->getRequest();
  
      // decide where the request should be forwarded to...
      list($controller_class,$controller_method,$controller_method_params) = $this->getControllerSelect()->find(
        $request->getHost(),
        $request->getPath()
      );

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
    $template = null;
    $response = $this->getResponse();
  
    if(is_string($controller_response)){
    
      $response->setContent($controller_response);
    
    }else if(is_array($controller_response)){
    
      $response->setContentType(Response::CONTENT_JSON);
      $response->setContent(json_encode($controller_response));
    
    }else if(is_object($controller_response)){
    
      if(method_exists($controller_response,'__toString')){
      
        $response->setContent((string)$controller_response);
      
      }else{
      
        throw new \RuntimeException(
          sprintf(
            'Controller returned an "%s" instance, which has no __toString()',
            get_class($controller_response)
          )
        );
      
      }//if/else
    
    }else{
    
      // wipe the rendering if the controller returned false... 
      if(is_bool($controller_response) && empty($controller_response)){
      
        $response->killTemplate();
        $response->setContent('');
      
      }else{
      
        if(!$response->hasContent()){
      
          if($response->hasTemplate()){
          
            $template = $this->getTemplate($response);
          
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
    
    if(!empty($template)){
    
      // output the template response to the screen...
      $template->handle(Template::OUT_STD);
      
    }//if
  
  }//method

  /**
   *  start all the known \Montage\Start\Startable classes
   *  
   *  a Start class is a class that will do configuration stuff
   */
  protected function handleAutoload(){
  
    $container = $this->getContainer();
    
    // create the reflection autoloader...
    $ral = $container->getInstance('\Montage\AutoLoad\ReflectionAutoloader');
    $ral->register(true);
    
    // create the standard autoloader...
    // we can't use find here because people might extend the StandardAutoloader (like I did)...
    $sal = $container->getInstance('\Montage\Autoload\StdAutoloader');
    $sal->addPaths($this->getField('vendor_paths',array()));
    $sal->register();
    
    // create any other autoloader classes...
    $select = $container->getInstance('\Montage\Autoload\Select');
    $class_list = $select->find();
    
    foreach($class_list as $class_name){
    
      $instance = $container->getInstance($class_name);
      $instance->register();
      
    }//foreach
    
    ///out::e(spl_autoload_functions());
     
  }//method

  /**
   *  start all the known \Montage\Start\Startable classes
   *  
   *  a Start class is a class that will do configuration stuff
   */
  protected function handleStart(){
  
    $instance_list = array();
    $env = $this->getField('env');
    $container = $this->getContainer();
    $select = $container->getInstance('\Montage\Start\Select');
    
    $start_class_list = $select->find($env);
    $method_name = $select->getMethod();

    foreach($start_class_list as $i => $class_name){
    
      $instance_list[$i] = $container->getInstance($class_name);
      $container->callMethod($instance_list[$i],$method_name);
      
    }//foreach
     
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
        
          out::e($rparam);
        
          $rfunc_params[$index] = array();
        
          if($count < count($params)){
        
            $rfunc_params[$index] = array_slice($params,$count);
            $params = array();
            
          }//if
          
          // set any default values if they are available, if they were not previously set then
          // pull from the request variables like $_GET and $_POST to populate the array values...
          if($rparam->isDefaultValueAvailable()){
          
            $request = $this->getRequest();
          
            foreach($rparam->getDefaultValue() as $default_key => $default_val){
            
              if(!isset($rfunc_params[$index][$default_key])){
              
                $rfunc_params[$index][$default_key] = $request->getField($default_key,$default_val);
              
              }//if
            
            }//foreach

          }//if
        
        }else{
    
          $rfunc_params[$index] = $container->normalizeParam($rparam,$params);
          
        }//if/else
        
      }catch(\Exception $e){
      
        throw new NotFoundException(
          sprintf('wrapped %s exception: %s',get_class($e),$e->getMessage()),
          $e->getCode()
        );
      
      }//try/catch
    
      if(is_object($rfunc_params[$index])){
      
        // populate a form object if there are passed in values...
        if($rfunc_params[$index] instanceof \Montage\Form\Form){
      
          $request = $this->getRequest();
          $form_name = $rfunc_params[$index]->getName();

          if($form_field_map = $request->getField($form_name)){
          
            $rfunc_params[$index]->set($form_field_map);
          
          }//if
          
          // set the current url...
          if(!$rfunc_params[$index]->hasUrl()){
          
            $url = $container->getInstance('Montage\Url');
            $rfunc_params[$index]->setUrl($url->getCurrent());
          
          }//if
        
        }//if
      
      }//if
    
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
  
    $ret_mixed = null;
  
    try{

      if($e instanceof InternalRedirectException){
      
        list($controller_class,$controller_method,$controller_method_params) = $this->getControllerSelect()->find(
          $request->getHost(),
          $e->getPath()
        );
      
        $controller_response = $this->handleController($controller_class,$controller_method,$controller_method_params);
        $ret_mixed = $this->handleResponse($controller_response);
      
      }else if($e instanceof RedirectException){
      
        $response = $this->getResponse();
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
      
      }else if($e instanceof StopException){
        
        // don't do anything, we're done
        $ret_mixed = $this->handleResponse(true);
        
      }else if($e instanceof \ReflectionException){
        
        if($old_e = $this->getField('ReflectionException')){
        
          throw new \RuntimeException(
            sprintf(
              '%s Exception: "%s" already triggered a framework reload and the problem was not fixed',
              get_class($old_e),
              $old_e->getMessage()
            )
          );
        
        }//if
        
        // this should restart the framework...
        
        // clear all the app cache...
        $cache = $this->getCache();
        $cache->clear();
        
        // clear all the autoloaders but the framework autoloader...
        foreach(spl_autoload_functions() as $callback){
          spl_autoload_unregister($callback);
        }//foreach
        
        // re-register the framework autoloader...
        $fal = new FrameworkAutoloader('Montage',realpath(__DIR__.'/..'));
        $fal->register();
        
        // start all the objects over again...
        $this->instance_map = array();
        
        $this->setField('ReflectionException',$e);
          
        // re-handle the request...
        $ret_mixed = $this->handle();
        
        
      }else{
        
        list($controller_class,$controller_method,$controller_method_params) = $this->getControllerSelect()->findException($e);
        
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
  
    $max_ir_count = $this->getField('Handler.recursion_max_count',3);
    $ir_field = 'Handler.recursion_count'; 
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
  }//method
  
  /**
   *  return the dependancy injection container 
   *
   *  @return Montage\Dependency\Container   
   */
  public function getContainer(){
  
    // canary...
    if(isset($this->instance_map['container'])){ return $this->instance_map['container']; }//if
  
    $reflection = $this->getReflection();
    $container_class_name = $reflection->findClassName('Montage\Dependency\ReflectionContainer');
    $container = new $container_class_name($reflection);
    
    // just in case, container should know about this instance for circular-dependency goodness...
    $container->setInstance('',$this);
    $container->setInstance('',$this->getCache());
    
    $this->setContainer($container);
  
    return $container;
    
  }//method

  /**
   *  create the controller selector
   *  
   *  @return Montage\Controller\Select
   */
  protected function getControllerSelect(){
  
    // canary...
    if(isset($this->instance_map['controller_select'])){ return $this->instance_map['controller_select']; }//if
    
    $container = $this->getContainer();
    $this->instance_map['controller_select'] = $container->getInstance('Montage\Controller\Select');
    
    return $this->instance_map['controller_select'];
  
  }//method
  
  /**
   *  get the request instance
   *  
   *  @since  6-29-11
   *  @return Montage\Request\Requestable
   */
  protected function getRequest(){
  
    // canary...
    if(isset($this->instance_map['request'])){ return $this->instance_map['request']; }//if
  
    $container = $this->getContainer();
    $this->instance_map['request'] = $container->getInstance('Montage\Request\Requestable');
    
    return $this->instance_map['request'];
  
  }//method
  
  /**
   *  get the response instance
   *  
   *  @since  6-29-11
   *  @return Montage\Response\Response
   */
  protected function getResponse(){
  
    $container = $this->getContainer();
    return $container->getInstance('\Montage\Response\Response');
  
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
    $cache = new PHPCache(); ///$cache = new Cache();
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
   *  get the template object that corresponds to the template file found in $response
   *
   *  @since  7-7-11
   *  @param  Montage\Response\Response $response
   *  @return Montage\Response\Template         
   */
  protected function getTemplate(Response $response){
  
    // canary...
    if(!$response->hasTemplate()){ return null; }//if
    
    $container = $this->getContainer();
    $template = $container->getInstance('\Montage\Response\Template');
    
    // update template with response values...
    $template->setTemplate($response->getTemplate());
    $template->setFields($response->getFields());
    
    return $template;
    
  }//method
  
  /**
   *  compile all the important framework paths
   *
   *  @since  6-27-11
   *  @param  string  $app_path the application path   
   */
  protected function compilePaths($app_path){
  
    $this->setField('app_path',$app_path);
    
    $framework_path = new Path(__DIR__,'..','..');
    $this->setField('framework_path',$framework_path);
    
    $path = new Path($app_path,'cache');
    $path->assure();
    $this->setField('cache_path',$path);
    
    $reflection_path_list = array();
    $view_path_list = array();
    $vendor_path_list = array();
    $assets_path_list = array();
    $plugin_base_path_list = array();
    $plugins_path_list = array();
    
    $path = new Path($framework_path,'src');
    $reflection_path_list[] = $path;
    
    $reflection_path_list[] = new Path($app_path,'src');
    
    $path = new Path($app_path,'config');
    if($path->exists()){ $reflection_path_list[] = $path; }//if
    
    $path = new Path($app_path,'view');
    if($path->exists()){ $view_path_list[] = $path; }//if
    
    $path = new Path($app_path,'vendor');
    if($path->exists()){ $vendor_path_list[] = $path; }//if
    
    $path = new Path($app_path,'assets');
    if($path->exists()){ $assets_path_list[] = $path; }//if
    
    // add the plugin paths...
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
  
    $this->setField('reflection_paths',$reflection_path_list);
    $this->setField('view_paths',$view_path_list);
    $this->setField('vendor_paths',$vendor_path_list);
    $this->setField('assets_paths',$assets_path_list);
    $this->setField('plugin_paths',$plugins_path_list);
  
  }//method
  
  /**
   *  return a list of files that need to be included
   *  
   *  sometimes, things need to be inlcuded before all the autoloaders have been loaded, these
   *  files will be loaded before the autoloaders
   *  
   *  @since  7-19-11
   *  @see  handleDependencies()
   *  @return array
   */
  protected function getIncludes(){
  
    $path_list = array();
    /* $path_list[] = new Path(
      $this->getField('framework_path'),'plugins','Symfony','vendor','ClassLoader','UniversalClassLoader.php'
    ); */
  
    return $path_list;
  
  }//method

}//method
