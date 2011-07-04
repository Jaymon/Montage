<?php
/**
 *  the kernal/core that translates the request to the response
 *  
 *  other names: handler, sequence, assembler, dispatcher, scheduler
 *  http://en.wikipedia.org/wiki/Montage_%28filmmaking%29  
 *  
 *  the best name might be Server or Framework (I like Framework, it will most likely
 *  be changed to Framework at some point)
 *   
 *  @version 0.6
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
namespace Montage;

// these are the files needed to use this class, after this class is loaded, the
// autoloader should handle everything else...
require_once(__DIR__.'/Cache/Cacheable.php');
require_once(__DIR__.'/Cache/Cache.php');
require_once(__DIR__.'/Cache/ObjectCache.php');

require_once(__DIR__.'/Dependency/Dependable.php');
require_once(__DIR__.'/Dependency/ReflectionFile.php');
require_once(__DIR__.'/Dependency/Reflection.php');
require_once(__DIR__.'/Path.php');

require_once(__DIR__.'/Field/SetFieldable.php');
require_once(__DIR__.'/Field/GetFieldable.php');
require_once(__DIR__.'/Field/Fieldable.php');
require_once(__DIR__.'/Field/Field.php');

require_once(__DIR__.'/Autoload/AutoLoadable.php');
require_once(__DIR__.'/Autoload/AutoLoader.php');
require_once(__DIR__.'/Autoload/ReflectionAutoloader.php');

use Montage\Cache\Cache;
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

use Montage\Response\Response;
use Montage\Response\Template;

class Framework extends Field implements Dependable {
  
  /**
   *  @var  Montage\Request\Requestable
   */
  protected $request = null;
  
  /**
   *  @var  Montage\Controller\Select
   */
  protected $controller_select = null;
  
  /**
   *  #var  Montage\Config\FrameworkConfig
   */
  protected $framework_config = null;
  
  /**
   *  holds the dependancy injection container instance
   *
   *  @var  \Montage\Dependancy\Container   
   */
  protected $container = null;

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
    
    // we shouldn't have "new" here, but sometimes you just have to break the rules
    // to make things easier, I didn't want to have to create a Cache and Reflection
    // instance to pass in here to resolve all the dependencies...
    // see: http://misko.hevery.com/2008/07/08/how-to-think-about-the-new-operator/ for how
    // I'm wrong about this, but convenience trumps rightness in this instance

    // create the caching object that Reflection will use...
    $cache = new Cache();
    $cache->setPath($this->getField('cache_path'));
    $cache->setNamespace($env);
  
    // create reflection, load the cache...
    $reflection = new Reflection();
    $reflection->setCache($cache);
    $reflection->importCache();
    $reflection->addPaths($this->getField('reflection_paths'));
    
    // create the reflection autoloader...
    $autoloader_class_name = $reflection->findClassName('Montage\AutoLoad\ReflectionAutoloader');
    $autoloader = new $autoloader_class_name($reflection);
    $autoloader->register();

    $container_class_name = $reflection->findClassName('Montage\Dependency\Container');
    $container = new $container_class_name($reflection);
    // just in case, container should know about this instance for circular-dependency goodness...
    $container->setInstance($this);
    $container->setInstance($cache);
    
    $this->setContainer($container);
    
    // set all the default configuration stuff...
    $this->framework_config = $container->findInstance('\Montage\Config\FrameworkConfig');
    $this->framework_config->setField('env',$env);
    $this->framework_config->setField('debug_level',$debug_level);
    $this->framework_config->setField('app_path',$app_path);
    $this->framework_config->setField('framework_path',$this->getField('framework_path'));
    
  }//method
  
  /**
   *  call this method to actually handle the request
   *  
   *  once this method is called, everything is taken care of for you
   */
  public function handle(){
  
    $container = $this->getContainer();
  
    try{

      // start the autoloaders...
      $this->handleAutoload();

      // start the START classes...
      $this->handleStart();
      
      $request = $this->getRequest();
  
      // decide where the request should be forwarded to...
      list($controller_class,$controller_method,$controller_method_params) = $this->getControllerSelect()->find(
        $request->getHost(),
        $request->getPath()
      );

      $controller_response = $this->handleController($controller_class,$controller_method,$controller_method_params);
    
      $this->handleResponse($controller_response);
    
    }catch(\Exception $e){
    
      $ret_mixed = $this->handleException($e);
    
    }//try/catch
  
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
    
      if(!$response->hasContent()){
    
        if($response->hasTemplate()){
        
          $template = $container->findInstance('\Montage\Response\Template');
          
          // update template with response values...
          $template->setTemplate($response->getTemplate());
          $template->setFields($response->getFields());
        
        }else{
        
          if(!$response->isRedirect()){
          
            $response->setContentType(Response::CONTENT_JSON);
            $response->setContent(json_encode($response->getFields()));
            
          }//if
        
        }//if/else
        
      }//if
    
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
  
    $instance_list = array();
    $container = $this->getContainer();
    
    // create the standard autoloader...
    // we can't use find here because people might extend the StandardAutoloader (like I did)...
    $standard_autoloader = $container->getInstance('\Montage\Autoload\StandardAutoloader');
    $standard_autoloader->addPaths($this->getField('vendor_paths'));
    $standard_autoloader->register();
    
    // create any other autoloader classes...
    $select = $container->findInstance('\Montage\Autoload\Select');
    $class_list = $select->find();
    
    foreach($class_list as $i => $class_name){
    
      $instance_list[$i] = $container->getInstance($class_name);
      $instance_list[$i]->register();
      
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
    $env = $this->framework_config->getEnv();
    $container = $this->getContainer();
    $select = $container->findInstance('\Montage\Start\Select');
    
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
    $reflection = $container->getReflection();
    
    $controller = $container->findInstance($class_name);
    $rmethod = new \ReflectionMethod($controller,$method);
    
    // if the first param is an array, then it will take all the passed in $params...
    // quick/nice way to do a catch-all...
    $rmethod_params = $rmethod->getParameters();
    if(isset($rmethod_params[0])){
    
      if($rmethod_params[0]->isArray()){
        $params = array($params);
      }//if
    
    }//if
    
    $rmethod_params = $container->normalizeParams($rmethod,$params);
    
    // make sure there are enough required params...
    $required_param_count = $rmethod->getNumberOfParameters();
    if($required_param_count !== count($rmethod_params)){
      throw new NotFoundException(
        sprintf(
          '%s::%s expects %s arguments to be passed to it, but %s args were passed',
          $class_name,
          $method,
          $required_param_count,
          count($rmethod_params)
        )
      );
    }//if
    
    // check for Forms and populate them if there are matching passed in vars...
    foreach($rmethod_params as $rmethod_param){
    
      if(is_object($rmethod_param)){
      
        $robject = new \ReflectionObject($rmethod_param);
      
        if($reflection->isChildClass($robject->getName(),'\Montage\Form\Form')){
        
          $form_name = $robject->getShortName();
          $request = $this->getRequest();

          if($form_field_map = $request->getField($form_name)){
          
            $rmethod_param->set($form_field_map);
          
          }//if
          
          if(!$rmethod_param->hasUrl()){
          
            $url = $container->findInstance('Montage\Url');
            $rmethod_param->setUrl($url->getCurrent());
          
          }//if
        
        }//if
      
      }//if
    
    }//foreach
    
    $controller->preHandle();
    $ret_mixed = $rmethod->invokeArgs($controller,$rmethod_params);
    $controller->postHandle();
    
    return $ret_mixed;
  
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
    
      // needs: Request, Forward
    
      if($e instanceof InternalRedirectException){
      
        list($controller_class,$controller_method,$controller_method_params) = $this->getControllerSelect()->find(
          $request->getHost(),
          $e->getPath()
        );
      
        $ret_mixed = $this->handleController($controller_class,$controller_method,$controller_method_params);
      
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
      
        $this->handleResponse(null);
      
      }else if($e instanceof StopException){
        
        // don't do anything, we're done
        
      }else{
        
        list($controller_class,$controller_method,$controller_method_params) = $this->getControllerSelect()->findException($e);
        
        $ret_mixed = $this->handleController($controller_class,$controller_method,$controller_method_params);
        
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
  
    $max_ir_count = $this->getField('Handler.recursion_max_count',10);
    $ir_field = 'Handler.recursion_count'; 
    $ir_count = $this->getField($ir_field,0);
    if($ir_count > 10){
      throw new \RuntimeException(
        sprintf(
          'The application has internally redirected more than %s times, something seems to '
          .'be wrong and the app is bailing to avoid infinite recursion!',
          $max_ir_count
        ),
        $e->getCode(),
        $e
      );
    }else{
    
      $ir_count = $this->bumpField($ir_field,1);
      
    }//if/else
    
    return $ir_count;
  
  }//method

  public function setContainer(\Montage\Dependency\Container $container){
    $this->container = $container;
  }//method
  
  public function getContainer(){ return $this->container; }//method

  protected function getControllerSelect(){
  
    // canary...
    if(!empty($this->controller_select)){ return $this->controller_select; }//if
    
    $container = $this->getContainer();
    $this->controller_select = $container->findInstance('Montage\Controller\Select');
    return $this->controller_select;
  
  }//method
  
  /**
   *  get the request instance
   *  
   *  @since  6-29-11
   *  @return Montage\Request\Requestable
   */
  protected function getRequest(){
  
    // canary...
    if(!empty($this->request)){ return $this->request; }//if
  
    $container = $this->getContainer();
    $this->request = $container->findInstance('Montage\Request\Requestable');
    
    return $this->request;
  
  }//method
  
  /**
   *  get the response instance
   *  
   *  @since  6-29-11
   *  @return Montage\Response\Response
   */
  protected function getResponse(){
  
    $container = $this->getContainer();
    return $container->findInstance('\Montage\Response\Response');
  
  }//method
  
  /**
   *
   *  @since  6-27-11
   */
  protected function compilePaths($app_path){
  
    $this->setField('app_path',$app_path);
    
    $framework_path = __DIR__;
    $this->setField('framework_path',$framework_path);
    
    $path = new Path($app_path,'cache');
    $path->assure();
    $this->setField('cache_path',$path);
    
    $reflection_path_list = array();
    $reflection_path_list[] = $framework_path;
    $reflection_path_list[] = new Path($app_path,'src');
    $reflection_path_list[] = new Path($app_path,'config');
    
    $view_path_list = array();
    $path = new Path($app_path,'view');
    if($path->exists()){ $view_path_list[] = $path; }//if
    
    $vendor_path_list = array();
    $path = new Path($app_path,'vendor');
    if($path->exists()){ $vendor_path_list[] = $path; }//if
    
    $assets_path_list = array();
    $path = new Path($app_path,'assets');
    if($path->exists()){ $assets_path_list[] = $path; }//if
    
    // add the plugin paths...
    $plugin_base_path = new Path($app_path,'plugins');
    foreach($plugin_base_path->createIterator('',1) as $plugin_path => $plugin_dir){
    
      if($plugin_dir->isDir()){
      
        $path = new Path($plugin_path,'config');
        if($path->exists()){ $reflection_path_list[] = $path; }//if
        
        $path = new Path($plugin_path,'src');
        if($path->exists()){ $reflection_path_list[] = $path; }//if 
      
        $path = new Path($plugin_path,'view');
        if($path->exists()){ $view_path_list[] = $path; }//if
        
        $path = new Path($plugin_path,'vendor');
        if($path->exists()){ $vendor_path_list[] = $path; }//if
        
        $path = new Path($plugin_path,'assets');
        if($path->exists()){ $assets_path_list[] = $path; }//if
      
      }//if
    
    }//foreach
  
    $this->setField('reflection_paths',$reflection_path_list);
    $this->setField('view_paths',$view_path_list);
    $this->setField('vendor_paths',$vendor_path_list);
    $this->setField('assets_paths',$assets_path_list);
  
  }//method

}//method
