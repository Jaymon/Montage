<?php
/**
 *  I almost named this class Polymorphism
 *  http://en.wikipedia.org/wiki/Polymorphism_in_object-oriented_programming
 *  http://en.wikipedia.org/wiki/Polymorphism_%28computer_science%29  
 *
 *
 *  this class handles all the discovering and auto-loading of classes, it also has
 *  methods to let the developer easily get class information and class relationships  
 *  
 *  this class can be mostly left alone unless you want to set more class paths 
 *  (use {@link setPath()}) than what are used by default, or if you want to add
 *  a custom autoloader (use {@link appendClassLoader()}) 
 *
 *  class paths checked by default:
 *    [MONTAGE_PATH]/model
 *    [MONTAGE_APP_PATH]/settings
 *    [MONTAGE_PATH]/plugins
 *    [MONTAGE_APP_PATH]/plugins  
 *    [MONTAGE_APP_PATH]/model
 *    [MONTAGE_APP_PATH]/controller/$controller
 *   
 *  @version 0.6
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
namespace Montage\Dependency;

use \ReflectionClass;
use Montage\Path;
use out;

class Reflection implements \Reflector {

  /**
   *  hold all the classes that could possibly be loadable
   *  
   *  the structure is: each key is a the class_key name, with path and name key/vals for
   *  each class_key
   *  
   *  @var  array
   */
  protected $class_map = array();
  
  /**
   *  map all the child classes to their parents
   *  
   *  this is handy for making sure a given class inherits what it should
   *  
   *  @var  array
   */
  protected $parent_class_map = array();

  public function __construct(){
  
    spl_autoload_register(array($this,'loadClass'));
  
  }//method
  
  public function __destruct(){
  
    spl_autoload_unregister(array($this,'loadClass'));
  
  }//method
  
  public static function export(){ return ''; }//method
  public function __toString(){ return spl_object_hash($this); }//method
  
  /**
   *  load a class
   *  
   *  this should never be called by the user, the only reason it is public is so
   *  {@link appendClassLoader()} will work right   
   *      
   *  @return boolean true if the class was found, false if not (so other autoloaders can have a chance)
   */
  public function loadClass($class_name){
  
    // if you just get blank pages: http://www.php.net/manual/en/function.error-reporting.php#28181
    //  http://www.php.net/manual/en/function.include-once.php#53239

    $ret_bool = false;

    $key = $this->normalizeClassName($class_name);
    if(isset($this->class_map[$key])){
    
      require($this->class_map[$key]['path']);
      $ret_bool = true;
    
    }else{
    
      // we used to throw an exception here, but that didn't account for user appended
      // autoloaders (ie, if this autoloader failed, then it failed even if the user
      // had appended another autoloader...
      $ret_bool = false;
      
    }//if/else

    return $ret_bool;
  
  }//method

  /**
   *  format the class key
   *  
   *  the class key is basically the class name standardized, this is handy to make
   *  classes case-insensitive (because they aren't in php)           
   *  
   *  @return string      
   */
  public function normalizeClassName($class_name){
  
    // canary...
    if(empty($class_name)){ throw new \InvalidArgumentException('$class_name was empty'); }//method
  
    // make sure that namespace names are normalized completely and all have the same (be it \\ or \)...
    ///$class_name = preg_replace('#\\+#','\\',$class_name);
  
    // make the namespace fully qualified...
    if($class_name[0] !== '\\'){ $class_name = sprintf('\\%s',$class_name); }//if
  
    return mb_strtoupper($class_name);
    
  }//method
  
  /**
   *  given a class name, find the best child class (eg, absolute descendant) for the class
   *
   *  @param  string  $class_name
   *  @return string  the class name of the absolute descendant of the $class_name
   */
  public function findClassName($class_name){

    $found_key = '';
    $key = $this->normalizeClassName($class_name);
  
    if(isset($this->parent_class_map[$key])){
    
      $child_class_list = $this->parent_class_map[$key];
      foreach($child_class_list as $child_key){
      
        // we're looking for the descendant most class...
        if(!isset($this->parent_class_map[$child_key])){
        
          if(empty($found_key)){
          
            $found_key = $child_key;
          
          }else{
            
            throw new LogicException(
              sprintf(
                'the given $class_name (%s) has divergent children %s and %s (those 2 classes extend ' 
                .'%s but are not related to each other) so a best class cannot be found.',
                $class_name,
                $found_key,
                $child_key,
                $key
              )
            );
            
          }//if/else
        
        }//if
      
      }//foreach

    }else{
    
      if(isset($this->class_map[$key])){
    
        $found_key = $key;
        
      }else{
      
        throw new \UnexpectedValueException(sprintf('no class %s was found',$class_name));
      
      }//if/else
      
    }//if/else
    
    $ret_class_name = empty($found_key) ? '' : $this->class_map[$found_key]['class'];
    return $ret_class_name;
    
  }//method
  
  public function addPath($path){
  
    if(!($path instanceof Path)){
      $path = new Path($path);
    }//if
  
    $ret_count = 0;
  
    $descendant_list = $path->getDescendants('#(?:php(?:\d+)?|inc|phtml)$#i');
    foreach($descendant_list['files'] as $file){
    
      $class_list = $this->findClasses(file_get_contents($file));
      foreach($class_list as $class_map){
      
        if($this->setClass($class_map['class'],$file,$class_map['extends'],$class_map['implements'])){
          $ret_count++;
        }//if
      
      }//foreach
    
    }//foreach
    
    return $ret_count;
  
  }//method
  
  /**
   *  if the class was defined outside of any paths then use this method so this class
   *  will know about it    
   *
   *  @since  6-7-11
   *  @param  string  $class_name the class that this instance should know about
   *  @return boolean         
   */
  public function addClass($class_name){
  
    $rclass = new ReflectionClass($class_name);
    $extend_list = array();
    if($rextend = $rclass->getParentClass()){
      $extend_list[] = $rextend->getName();
    }//if
    
    return $this->setClass($class_name,$rclass->getFileName(),$extend_list,$rclass->getInterfaceNames());
  
  }//method
  
  public function findClasses($code){
  
    // canary...
    if(empty($code)){ throw new \InvalidArgumentException('$code was empty'); }//method
  
    $ret_list = array();
    $tokens = token_get_all($code);
    
    /*
    foreach($tokens as $key => $token){
      if(is_array($tokens[$key])){ $tokens[$key][0] = token_name($tokens[$key][0]); }//if
    }//foreach
    out::e($tokens); // */

    $namespace = '';
    $use_map = array();
    
    for($i = 0, $total_tokens = count($tokens); $i < $total_tokens ;$i++){
    
      if(is_array($tokens[$i])){
      
        switch($tokens[$i][0]){
      
          case T_NAMESPACE:

            $namespace = '';
            $use_map = array();

            list($i,$namespace) = $this->getNamespace($i,$tokens);
            break;
          
          case T_USE:
          
            list($i,$map) = $this->getUseNamespace($i,$tokens);
            $use_map = array_merge($use_map,$map);
            
            break;
          
          case T_CLASS:
          case T_INTERFACE:

            list($i,$map) = $this->getClass($i,$tokens,$namespace,$use_map);
            $ret_list[] = $map;

            break;
          
        }//switch
      
      }//if
    
    }//foreach
  
    ///out::e($namespace,$use_map);
    
    return $ret_list;
  
  }//method
  
  protected function getClassName($class_name,$namespace,$use_map){
  
    // canary...
    if(empty($class_name)){ return ''; }//if
  
    $ret_str = '';
  
    if($class_name[0] === '\\'){
    
      // it's fully qualified, so don't try to discover the namespace...
      $ret_str = $class_name;
    
    }else{
    
      // see if it is an alias...
      if(isset($use_map[$class_name])){
      
        $ret_str = $use_map[$class_name];
      
      }else{
      
        // does it have a namespace...
        $bits = preg_split('#\\\S+$#',$class_name);
        if(isset($bits[1])){
        
          foreach($use_map as $qualified_name){
          
            if(mb_stripos($qualified_name,$bits[0]) !== false){
              $ret_str = sprintf('%s\\%s',$qualified_name,$bits[1]);
              break;
            }//if
          
          }//foreach
        
        }//if
        
        if(empty($ret_str)){
        
          $ret_str = sprintf('%s\\%s',$namespace,$class_name);
        
        }//if/else
      
      }//if/else
    
    }//if/else
  
    return $ret_str;
  
  }//method
  
  protected function getParentClassNames($i,$tokens,$str_tokens,$arr_tokens,$namespace,$use_map){
  
    $parent_class_list = array();
    $parent_class = '';
        
    for($i = $i + 1; !in_array($tokens[$i],$str_tokens,true) && (!is_array($tokens[$i]) || !in_array($tokens[$i][0],$arr_tokens,true)) ;$i++){
    
      if(is_string($tokens[$i])){
      
        if($tokens[$i] === ','){

          out::e($parent_class);

          $parent_class_list[] = $this->getClassName($parent_class,$namespace,$use_map);
          $parent_class = '';
        
        }else{
        
          $parent_class .= $tokens[$i];
          
        }//if/else
      
      }else{
      
        if($tokens[$i][0] !== T_WHITESPACE){
          $parent_class .= $tokens[$i][1];
        }//if
          
      }//if/else
    
    }//for
    
    if(!empty($parent_class)){
      $parent_class_list[] = $this->getClassName($parent_class,$namespace,$use_map);
    }//if
  
    return array($i,$parent_class_list);

  
  }//method
  
  protected function getClass($i,$tokens,$namespace,$use_map){
  
    $class = '';
    $extends_list = $implements_list = array();
  
    for($i = $i + 1; ($tokens[$i] !== '{') ;$i++){

      if(is_string($tokens[$i])){
      
        $class .= $tokens[$i];
        
      }else{
      
        if($tokens[$i][0] === T_EXTENDS){
        
          list($i,$extends_list) = $this->getParentClassNames(
            $i,
            $tokens,
            array('{'),
            array(T_IMPLEMENTS),
            $namespace,
            $use_map
          );
          
          $i--;
        
        }else if($tokens[$i][0] === T_IMPLEMENTS){
        
          list($i,$implements_list) = $this->getParentClassNames(
            $i,
            $tokens,
            array('{'),
            array(),
            $namespace,
            $use_map
          );
          
          $i--;
        
        }else{
        
          if($tokens[$i][0] !== T_WHITESPACE){
            $class .= $tokens[$i][1];
          }//if
          
        }//if/else
        
      }//if/else
    
    }//for
  
    $ret_map = array(
      'class' => $this->getClassName($class,$namespace,$use_map),
      'extends' => $extends_list,
      'implements' => $implements_list
    );
  
    return array($i,$ret_map);
  
  }//method
  
  protected function getUseNamespace($i,$tokens){
  
    $ret_map = array();
    $namespace = '';
    $alias = '';
  
    // go until we hit the end of the line
    for($i = $i + 1; ($tokens[$i] !== ';') ;$i++){

      if(is_string($tokens[$i])){
        
        if($tokens[$i] === ','){
        
          $namespace = trim($namespace);
          if(empty($alias)){ $alias = $namespace; }//if
          $ret_map[$alias] = $namespace;
          $alias = $namespace = '';
          
          list($i,$map) = $this->getUseNamespace($i,$tokens);
          $i--; // i will increment at the end of the loop
          $ret_map = array_merge($ret_map,$map);
        
        }else{
        
          $namespace .= $tokens[$i];
        
        }//if/else
        
      }else{
      
        if($tokens[$i][0] === T_AS){
        
          list($i,$alias) = $this->getUseAlias($i,$tokens);
          $i--;
          $ret_map[$alias] = $namespace;
          
        }else{
        
          $namespace .= $tokens[$i][1];
        
        }//if/else
      
      }//if/else

    }//for
  
    $namespace = trim($namespace);
    if(!empty($namespace)){
      if(empty($alias)){ $alias = $namespace; }//if
      $ret_map[$alias] = $namespace;
    }//if
  
    return array($i,$ret_map);
  
  }//method
  
  protected function getUseAlias($i,$tokens){
  
    $ret_str = '';
  
    // go until we hit the end of the line
    for($i = $i + 1; (($tokens[$i] !== ';') && $tokens[$i] !== ',') ;$i++){
      $ret_str .= is_string($tokens[$i]) ? $tokens[$i] : $tokens[$i][1];
    }//for
  
    return array($i,trim($ret_str));
  
  }//method
  
  protected function getNamespace($i,$tokens){
  
    $namespace = '';
          
    // go until we hit the end of the line
    for($i = $i + 1; ($tokens[$i] !== ';') && $tokens[$i] !== '{' ;$i++){
      $namespace .= is_string($tokens[$i]) ? $tokens[$i] : $tokens[$i][1];
    }//for
  
    $namespace = sprintf('\\%s',trim($namespace));
    return array($i,$namespace);
  
  }//method
  
  /**
   *
   *  @since  6-7-11
   */
  protected function setClass($class_name,$class_file,array $extend_list = array(),array $implement_list = array()){
  
    $class_map = array();
    $key = $this->normalizeClassName($class_name);
    $class_map['last_modified'] = filemtime($class_file);
    $class_map['path'] = $class_file;
    $this->class_map[$key] = $class_map;
    
    // add class as child to all its parent classes...
    foreach(array_merge($extend_list,$implements_list) as $parent_class){
      $parent_key = $this->normalizeClassName($parent_class);
      if(!isset($this->parent_class_map[$parent_key])){
        $this->parent_class_map[$parent_key] = array();
      }//if
    
      $this->parent_class_map[$parent_key][] = $key;
    
    }//if
    
    return true;
  
  }//method

}//method
