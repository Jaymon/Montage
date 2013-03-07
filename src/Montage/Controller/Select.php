<?php
/**
 *  handles deciding which controller::method to forward to
 *  
 *  this class should be renamed to something like Finder or Matcher, though
 *  Matcher::find() sounds strange, what about Resolve?  6-17-11 - I went with
 *  Select. I'm not extremely happy with these Select classes, but I'm not sure
 *  I want all this functionality to sit in Framework since it does so much already
 *  
 *  @version 0.6
 *  @author Jay Marcyes
 *  @since 4-6-10
 *  @package montage
 ******************************************************************************/       
namespace Montage\Controller;

use Montage\Reflection\ReflectionFramework;

class Select {

  /**
   *  holds the information about what classes exist in the system
   *
   *  @var  \Montage\Reflection\ReflectionFramework
   */
  public $reflection = null;

  /**
   *  this is appended to the class name
   *  
   *  so, class name "foo" would become "FooController"
   *  
   *  @see  normalizeClass
   *  @var  string
   */
  //protected $class_postfix = '';
  
  /**
   *  if no controller can be found using passed in params, use this controller name
   *
   *  @var  string   
   */        
  protected $default_class_name = 'Default';

  /**
   * the default method name if another one can't be found
   *
   * @var string
   */
  protected $default_method = 'default';
  
  /**
   *  this prefix will be used to decide what a vanilla method name coming in will be
   *  prefixed with
   *  
   *  so, if you have method "bar" it would become: "handleBar"
   *  
   *  @see  normalizeMethod()   
   *  @var  string      
   */
  protected $method_prefix = 'handle';

  /**
   *  this prefix is used to decide what an error class will use
   *  
   *  
   *  so, if you have method "bar" it would become: "errorBar"
   *  
   *  @var  string
   */
  protected $error_method_prefix = 'error';

  /**
   *  turns the info provided by the $host, $path and $params into a controller::method
   *  
   *  @param  string  $type the namespace for this controller (eg, Web, Controller)
   *  @param  string  $method the method of the request (eg GET, POST, CLI)
   *  @param  string  $host the host that is making the request
   *  @param  string  $path the path of the request
   *  @param  array $params currently not used
   *  @return array array($controller,$method,$method_params)
   */
  public function find($method, $host, $path, array $params = array()){
  
    $path_list = array_values(array_filter(explode('/',$path))); // ignore empty values
    $class_name = '';
    $method_name = '';
    $method_params = array();
    $env = $this->getEnv($method, $host, $params);

    list($class_name,$path_list) = $this->findClassName(
      $path_list,
      $env['class_postfix'],
      $env['class_interface']
    );
  
    list($method_names, $method_params) = $this->findMethod(
      $class_name,
      $path_list,
      $env['method_prefixes']
    );

    return array($class_name, $method_names, $method_params);
  
  }//method
  
  /**
   * find all first postition endpoint controller names
   *
   * if you had a controller: Namespace\UserEndpoint, this would return array('user')
   *
   * @since 2013-3-7
   * @return  array an array of endpoint names
   */
  public function findEndpoints(){
    $ret_list = array();
    $map = $this->getClassInfo('GET', '', array());
    $reflection = $this->reflection;
    $class_list = $reflection->findClassNames($map['class_interface']);
    foreach($class_list as $class_name){
      $class_start = mb_strrpos($class_name, '\\');
      $short_name = mb_substr($class_name, $class_start);
      $short_name = str_replace(array('\\', $map['class_postfix']), array('', ''), $short_name);
      $ret_list[] = mb_strtolower($short_name);
    }//foreach

    // TODO: go through default endpoint and also add handle* methods

    return $ret_list;

  }//method

  /**
   * get the class postfix that will be appended to the controller class name
   *
   * TODO the info should probably be gotten in getEndpointInfo() and getCommandInfo() methods
   * to make it easier to extend, and also to make findEndpoints() not have to pass in 'GET' and stuff
   *
   * @param string  $method
   * @return string
   */
  protected function getClassInfo($method, $host, array $params){

    $ret_map = array();
    if(empty($method) || ($method != 'cli')){
      $ret_map['class_postfix'] = 'Endpoint';
      $ret_map['class_interface'] = '\\Montage\\Controller\\Endpoint';

    }else{
      $ret_map['class_postfix'] = 'Command';
      $ret_map['class_interface'] = '\\Montage\\Controller\\Command';
    }//if/else

    return $ret_map;

  }//method

  protected function getMethodInfo($method, $host, array $params){

    $ret_map = array();
    $method = ucfirst($method);
    $ret_map['method_prefixes'] = array(
      sprintf('%s%s', $this->method_prefix, $method),
      $this->method_prefix
    );

    $ret_map['method_error_prefixes'] = array(
      sprintf('%s%s', $this->error_method_prefix, $method),
      $this->error_method_prefix
    );

    return $ret_map;

  }//method

  /**
   * get some environment info that will be used to find the controller and method to use
   *
   * @param mixed $method
   * @param mixed $host
   * @param array $params
   * @return array
   */
  protected function getEnv($method, $host, array $params){
  
    $method = strtolower($method);
    $ret_map = $this->getClassInfo($method, $host, $params);
    $ret_map = array_merge($ret_map, $this->getMethodInfo($method, $host, $params));
    return $ret_map;
  
  }//method

  /**
   *  find the controller class
   *  
   *  how this works, it finds all the classes with the same shortname (eg namespace\shortname)
   *  and then finds which is the right most child and uses that, if it can't resolve, an exception
   *  is thrown, if no shortname classes are found, it will then use the fallback class name
   *         
   *  @since  6-16-11
   *  @param  array $path_list  the path broken up by /
   *  @param  string $class_postfix part of the $path_list will be combined with this to get the full class name
   *  @param  string  $class_interface  the found class will need to extend this interface to be valid
   *  @return array array($class_name,$path_list)
   */
  protected function findClassName(array $path_list, $class_postfix, $class_interface){

    $ret_str = '';
    $path_bit = reset($path_list);
    $fallback_class_name = $this->default_class_name;
    
    // first test the first item in the path list
    if(!empty($path_bit)){
    
      $ret_str = $this->getClassName($path_bit, $class_postfix, $class_interface);
      
    }//if
    
    // check for the default class name if the path list failed to find something...
    if(empty($ret_str)){
      
      $ret_str = $this->getClassName($fallback_class_name, $class_postfix, $class_interface);
      
    }else{
    
      if(!empty($path_list)){
    
        $path_list = array_slice($path_list,1);
        
      }//if
    
    }//if/else
    
    // it's an error if no class was found
    if(empty($ret_str)){
      throw new \UnexpectedValueException(
        sprintf(
          'A suitable Controller class could not be found to handle the request [/%s] with fallback class [%s]',
          join('/',$path_list),
          $fallback_class_name
        )
      );
    }//if
  
    return array($ret_str,$path_list);
    
  }//method
  
  /**
   *  returns a full class name if it is a child of {@link $class_interface}
   *  
   *  this allows any other method to make sure any shortname has a controller class
   *  with the same shortname, and to get the absolute child of the controller class   
   *
   *  @since  6-20-11
   *  @param  string  $class_shortname a partial class name that will be turned into a full class name, this
   *                              value would be equivalent to {@link ReflectionClass::getShortName()} and
   *                              is the name of the class without the namespace         
   *  @param  string  $class_postfix
   *  @param  string  $class_interface
   *  @return string
   */
  protected function getClassName($class_shortname, $class_postfix, $class_interface){
  
    // canary...
    if(empty($class_postfix)){
      throw new \InvalidArgumentException('$class_postfix was empty');
    }//if
  
    $ret_str = '';
    $regex = sprintf('#%s$#i',preg_quote($this->normalizeClassName('', $class_shortname, $class_postfix)));
    $reflection = $this->reflection;
    $class_list = $reflection->findClassNames($class_interface);
    
    foreach($class_list as $class_name){
    
      if(preg_match($regex,$class_name)){
      
        $rclass = new \ReflectionClass($class_name);
        if($rclass->isInstantiable()){
        
          $ret_str = $class_name;
          break;
          
        }//if
      
      }//if
    
    }//foreach
  
    return $ret_str;
  
  }//method
  
  /**
   *  find the matching method for $class_name using $path_list
   *  
   *  @since  6-16-11
   *  @param  string  $class_name the controller class
   *  @param  array $path_list
   *  @param  array $method_prefixes
   *  @return array($method,$method_params)
   */
  protected function findMethod($class_name, array $path_list, array $method_prefixes){
  
    $method_names = array();
    $method_params = array();
    
    // check in order:
    // 1 - $class_name/$path_list[0]
    // 2 - $class_name/$this->method_default
  
    $path_bit = reset($path_list);
    if(!empty($path_bit)){

      foreach($method_prefixes as $method_prefix){
      
        $method_name = $this->normalizeMethod($path_bit, $method_prefix);
        if(method_exists($class_name, $method_name)){
          $method_names[] = $method_name;
          
        }//if

      }//foreach

    }//if
    
    if(empty($method_names)){
    
      foreach($method_prefixes as $method_prefix){

        $method_name = $this->normalizeMethod($this->default_method, $method_prefix);
        if(method_exists($class_name, $method_name)){
          $method_names[] = $method_name;

        }//if

      }//foreach
      
      if(empty($method_names)){
      
        throw new \UnexpectedValueException(
          sprintf(
            'Could not find a suitable method in %s to handle the request',
            $class_name
          )
        );
      
      }else{
        $method_params = $path_list;

      }//if/else

    }else{
      $method_params = array_slice($path_list,1);

    }//if
  
    return array($method_names, array_values($method_params));
  
  }//method
  
  /**
   *  gets the "usable" controller class name
   *  
   *  basically, if you pass in something like "foo" then this will return "FooPostfix"
   *  which is the non-resolved classname before the namespace is added    
   *      
   *  @param  string  $namespace  the namespace you want to prepend to the $class_name   
   *  @param  string  $class_name  the potential controller class name
   *  @param  string  $class_postfix  the postfix this class will use in the normalized name
   *  @return string
   */
  protected function normalizeClassName($namespace, $class_name, $class_postfix = ''){
  
    // canary...
    if(empty($class_name)){
      throw new \InvalidArgumentException('$class_name was empty');
    }//if
    if(!empty($class_postfix) && (mb_stripos($class_name,$class_postfix) === false)){
      $class_name = ucfirst(strtolower($class_name)).$class_postfix;
    }//if
    
    // make sure the namespace starts with a \
    if(!empty($namespace)){
    
      if($namespace[0] != '\\'){ $namespace .= '\\'.$namespace; }//if
    
    }//if
  
    return $namespace.'\\'.$class_name;
  
  }//method
  
  /**
   *  get the controller name that should be used
   *  
   *  @param  string  $method_name  can be the full method name (eg, hanldeFoo) or a partial 
   *                                that will be made into the full name (eg, foo gets turned into handleFoo)      
   *  @param`string $method_prefix  the prefix to add to the method name
   *  @return string
   */
  protected function normalizeMethod($method_name, $method_prefix){
  
    // canary...
    if(empty($method_name)){
      throw new \InvalidArgumentException('$method_name was empty');
    }//if
    if(mb_stripos($method_name,$method_prefix) > 0){ return $method_name; }//if

    $method_name = $method_prefix.ucfirst(strtolower($method_name));
    return $method_name;
  
  }//method
  
}//class
