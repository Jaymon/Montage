<?php
/**
 *  this class keeps class relationship of classes found in any path  
 *
 *  I almost named this class Polymorphism
 *  http://en.wikipedia.org/wiki/Polymorphism_in_object-oriented_programming
 *  http://en.wikipedia.org/wiki/Polymorphism_%28computer_science%29  
 *
 *  this class keeps class relationship info for any path (see {@link addPath()}) or
 *  class (see {@link addClass()}) that have been added to this instance, this allows
 *  you to get detailed info about whether a class is related to another class and to
 *  be able to find things like absolute descnendents of classes   
 *  
 *  this class handles all the discovering of relationships between classes that it knows
 *  about, it also has methods to let the developer easily get class information 
 *  and class relationships of its internal class structure
 *  
 *  this class can be mostly left alone unless you want to set more class paths 
 *  (use {@link addPath()}) than what are used by default
 *  
 *  @todo this can be renamed ReflectionRelationship     
 *
 *  @version 0.6
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
namespace Montage\Dependency;

use ReflectionClass;
use Montage\Dependency\ReflectionFile;
use Montage\Path;
use Montage\Cache\ObjectCache;
use out;

use Montage\Cache;

class Reflection extends ObjectCache implements \Reflector {

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
  
  /**
   *  holds the cache for all the known absolute children of classes
   * 
   *  @see  findClassNames()
   *  @since  7-6-11
   *  @var  array
   */
  protected $children_class_map = array();
  
  protected $path_map = array('files' => array(),'folders' => array());
  
  protected $reloaded = false;
  
  public static function export(){ return ''; }//method
  public function __toString(){ return spl_object_hash($this); }//method

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
    ///if($class_name[0] !== '\\'){ $class_name = sprintf('\\%s',$class_name); }//if
    
    $class_name = $this->qualifyClassName($class_name);
    return mb_strtoupper($class_name);
    
  }//method
  
  /**
   *  get all the absolute child classes of $class_name
   *  
   *  @since  6-20-11
   *  @param  string  $class_name the class whose children you want
   *  @param  array $ignore_list  put the child classes you don't want returned here
   *  @return array a list of classes that match
   */
  public function findClassNames($class_name,array $ignore_list = array()){
  
    $key = $this->normalizeClassName($class_name);
    $ret_list = array();
  
    // canary, return cached if available...
    if(isset($this->children_class_map[$key])){
      
      $ret_list = $this->children_class_map[$key];
    
    }else{
    
      if(isset($this->parent_class_map[$key])){
      
        foreach($this->parent_class_map[$key] as $child_class_name){
  
          $list = $this->findClassNames($child_class_name);
          $ret_list = array_merge($ret_list,$list);
           
        }//foreach
      
      }else{
      
        // this class is not a parent of anything (ie, an absolute descendent)...
        if(isset($this->class_map[$key])){
        
          $cn = $this->class_map[$key]['class'];
          if(!in_array($cn,$ignore_list,true)){ $ret_list[] = $cn; }//if
        
        }//if
      
      }//if/else
      
      // cache result, this slows down the first request but converts other requests
      // from around ~7ms to .1ms
      $this->children_class_map[$key] = $ret_list;
      $this->exportCache();
      
    }//if/else
    
    // normalize the ignore list...  
    if(!empty($ignore_list)){

      foreach($ignore_list as $i => $icn){
      
        $icn_key = $this->normalizeClassName($icn);
        if(isset($this->class_map[$icn_key])){
          $ignore_list[$i] = $this->class_map[$icn_key]['class'];
        }else{
          unset($ignore_list[$i]);
        }//if/else
      
      }//foreach
      
      $ret_list = array_diff($ret_list,$ignore_list);
      
    }//if
    
    return $ret_list;
  
  }//method
  
  /**
   *  given a class name, find the best child class (eg, absolute descendant) for the class
   *
   *  @param  string  $class_name
   *  @return string  the class name of the absolute descendant of the $class_name
   */
  public function findClassName($class_name){

    $ret_str = '';
  
    // we wrap in a try/catch so we can try to reload the class if an error state is found...
    try{
    
      // first check cache...
      $class_map = $this->getClass($class_name);
      $key = $class_map['key'];
      
      if(isset($class_map['class_found'])){
      
        $ret_str = $class_map['class_found'];
      
      }else{
      
        if(isset($this->parent_class_map[$key])){
        
          $child_count = count($this->parent_class_map[$key]);
          if($child_count > 1){
            
            $e_child_list = array();
            foreach($this->findClassNames($class_name) as $child_class_name){
            
              $child_key = $this->normalizeClassName($child_class_name);
              $e_child_list[] = sprintf(
                '%s located at "%s"',
                $child_class_name,
                $this->class_map[$child_key]['path']
              );
            
            }//foreach
    
            throw new \LogicException(
              sprintf(
                'the given $class_name (%s) is extended by %s children [%s] so a best class cannot be found, definitions were %s',
                $class_name,
                $child_count,
                join(',',$e_child_list),
                $this->reloaded ? 'RELOADED' : 'NOT RELOADED'
              )
            );
              
          }else{
          
            $child_class_name = reset($this->parent_class_map[$key]); // get first row
            $ret_str = $this->findClassName($child_class_name); // recurse through the list
            
            // write out cache...
            $this->class_map[$key]['class_found'] = $ret_str;
            $this->exportCache(); 
          
          }//if/else
        
        }else{
        
          $ret_str = $this->class_map[$key]['class'];
          
        }//if/else
        
      }//if/else
      
    }catch(\Exception $e){
    
      if($this->reload() > 0){
          
        $ret_str = $this->findClassName($class_name);
      
      }else{
      
        throw $e;
      
      }//if/else
    
    }//try/catch

    return $ret_str;
    
  }//method
  
  /**
   *  adds a file to be reflected (ie, get all class information from the file)
   *
   *  @since  6-14-11
   *  @param  string  $file a source file
   *  @return integer how many classes were added from the file
   */
  public function addFile($file){
  
    $ret_count = $this->setFile($file);
    $this->path_map['files'][$file] = $ret_count;
  
    if($ret_count > 0){
    
      $this->exportCache();
      
    }//if
      
    return $ret_count;
  
  }//method
  
  /**
   *
   *  @since  6-27-11
   */
  public function addPaths(array $path_list){
  
    $ret_count = 0;
    foreach($path_list as $path){
      $ret_count += $this->addPath($path);
    }//foreach
  
    return $ret_count;
  
  }//method
  
  public function addPath($path){
  
    $regex = '#(?:php(?:\d+)?|inc|phtml)$#i';
  
    // canary...
    if(!($path instanceof Path)){
    
      $path = new Path($path);
      if(!$path->exists()){ return 0; }//if

    }//if
    if($this->hasPath($path)){
      
      $ret_count = 0;
      $subpath_count = $path->countChildren($regex);
      if($subpath_count !== $this->path_map['folders'][(string)$path]){
        $ret_count = $this->reload();
      }//if
      
      return $ret_count;
      
    }//if
  
    $ret_count = 0;
    $subpath_count = $path->countChildren($regex);
  
    $subpath_list = $path->getChildren($regex);
    foreach($subpath_list['files'] as $file){
    
      $ret_count += $this->setFile($file);
    
    }//foreach

    $this->path_map['folders'][(string)$path] = $subpath_count;
    $this->exportCache();
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
    
    return $this->setClass($class_name,$path,$extend_list,$rclass->getInterfaceNames());
  
  }//method
  
  /**
   *  true if the class is known to this instance
   *  
   *  @since  6-8-11
   *  @param  string  $class_name
   *  @param  string  $parent_class_name  if passed in then the $class_name must also be a child of this class   
   *  @return boolean
   */
  public function hasClass($class_name){
  
    $ret_bool = false;
    if(!empty($class_name)){
    
      $class_key = $this->normalizeClassName($class_name);
      $ret_bool = isset($this->class_map[$class_key]);
      
    }//if
  
    return $ret_bool;
  
  }//method
  
  /**
   *  get the class info
   *
   *  @since  6-27-11
   *  @param  string  $class_name
   *  @return array         
   */
  public function getClass($class_name){
  
    $class_key = $this->normalizeClassName($class_name);
    // canary...
    if(!isset($this->class_map[$class_key])){
      // @todo  I think an exception might be too dramatic, just return array()?
      throw new \InvalidArgumentException(sprintf('$class_name (%s) is not known',$class_name));
    }//if
    if($this->isChangedClass($class_key)){
      $this->reload();
      return $this->getClass($class_name);
    }//if
    
    return $this->class_map[$class_key];
  
  }//method
  
  /**
   *  return true if the $child_class_name is a descendent or the same as $parent_class_name
   *
   *  @since  6-17-11
   *  @param  string  $child_class_name
   *  @param  string  $parent_class_name     
   *  @return boolean   
   */        
  public function isRelatedClass($child_class_name,$parent_class_name){
  
    // canary...
    if(empty($child_class_name)){ return false; }//if
    if(empty($parent_class_name)){ return false; }//if
    if(!$this->hasClass($child_class_name)){ return false; }//if
  
    $child_key = $this->normalizeClassName($child_class_name);
    $parent_key = $this->normalizeClassName($parent_class_name);
    $ret_bool = ($child_key === $parent_key);
    
    if($ret_bool === false){
    
      $ret_bool = $this->isChildClass($child_key,$parent_key);
    
    }//if
  
    return $ret_bool;
  
  }//method
  
  /**
   *  return true if $child_class_name is a child of $parent_class_name
   *  
   *  child being defined in this context as descendant of the parent class
   * 
   *  @since  6-8-11        
   *  @param  string  $child_class_name
   *  @param  string  $parent_class_name      
   *  @return boolean
   */
  public function isChildClass($child_class_name,$parent_class_name){
  
    // canary...
    if(empty($child_class_name)){ return false; }//if
    if(empty($parent_class_name)){ return false; }//if
    if(!$this->hasClass($child_class_name)){ return false; }//if
  
    $ret_bool = false;
  
    $parent_key = $this->normalizeClassName($parent_class_name);
    $child_class_name = $this->qualifyClassName($child_class_name);
    
    // first check the parents...
    $parent_list = class_parents($child_class_name,true);
    foreach($parent_list as $class_name){
      if($parent_key === $this->normalizeClassName($class_name)){
        $ret_bool = true;
        break;
      }//if
    }//foreach
    
    // only if parents fail check interfaces...
    if(empty($ret_bool)){
    
      $implement_list = class_implements($child_class_name,true);
      foreach($implement_list as $class_name){
        if($parent_key === $this->normalizeClassName($class_name)){
          $ret_bool = true;
          break;
        }//if
      }//foreach
    
    }//if
  
    return $ret_bool;
  
  }//method
  
  /**
   *  reload all known paths
   *  
   *  @since  6-20-11
   *  @return integer how many classes were reloaded
   */
  protected function reload(){
  
    // canary...
    if($this->reloaded){ return 0; }//if

    $ret_count = 0;
  
    $folder_list = isset($this->path_map['folders']) ? array_keys($this->path_map['folders']) : array();
    $file_list = isset($this->path_map['files']) ? array_keys($this->path_map['files']) : array();
  
    $this->class_map = array();
    $this->parent_class_map = array();
    $this->children_class_map = array();
    $this->path_map = array('files' => array(),'folders' => array());
    
    foreach($folder_list as $folder){
    
      $ret_count += $this->addPath($folder);
    
    }//foreach
    
    foreach($file_list as $file){
    
      $ret_count += $this->addFile($file);
    
    }//foreach
  
    $this->reloaded = true;
    return $ret_count;
    
  }//method
  
  /**
   *  return true if this class has changed
   *
   *  @param  string  $class_name
   *  @return boolean true if the class has changed   
   */
  protected function isChangedClass($class_key){
  
    // canary...
    if(!file_exists($this->class_map[$class_key]['path'])){
      ///throw new \UnexpectedValueException(
      throw new \ReflectionException(
        sprintf('%s does not exist anymore',$this->class_map[$class_key]['path'])
      );
    }//if
  
    $old = $this->class_map[$class_key]['hash'];
    $new = md5_file($this->class_map[$class_key]['path']);

    return ((string)$old !== (string)$new);
  
  }//method
  
  /**
   *  add a file and all the classes contained in that file
   *  
   *  @since  6-20-11
   *  @param  string  $file
   *  @return count how many classes from the file were added
   */
  protected function setFile($file){
  
    // canary...
    if($this->hasPath(new Path($file))){ return 0; }//if
  
    $ret_count = 0;
    $rfile = new ReflectionFile($file);
    
    $class_list = $rfile->getClasses();
    
    foreach($class_list as $class_map){
    
      if($this->setClass($class_map['class'],$file,$class_map['extends'],$class_map['implements'])){
        $ret_count++;
      }//if
    
    }//foreach
      
    return $ret_count;
  
  }//method
  
  /**
   *
   *  @since  6-7-11
   */
  protected function setClass($class_name,$class_file,array $extend_list = array(),array $implement_list = array()){
  
    $class_map = array();
    $key = $this->normalizeClassName($class_name);
    $class_map['class'] = $this->qualifyClassName($class_name);
    $class_map['key'] = $key;
    
    ///$class_map['last_modified'] = filemtime($class_file); // use MD5 instead?
    $class_map['hash'] = md5_file($class_file);
    
    $class_map['path'] = $class_file;
    
    $parent_list = array_merge($extend_list,$implement_list);
    
    $dependency_list = $parent_list;
    
    // add class as child to all its parent classes...
    foreach($parent_list as $parent_class){
      
      $parent_key = $this->normalizeClassName($parent_class);
      
      if(!isset($this->parent_class_map[$parent_key])){
        
        $this->parent_class_map[$parent_key] = array();
        
      }//if
    
      if(!empty($this->class_map[$parent_key]['dependencies'])){
      
        $dependency_list = array_merge($dependency_list,$this->class_map[$parent_key]['dependencies']);
        $dependency_list[] = $parent_class;
        
      }//if
    
      $this->parent_class_map[$parent_key][] = $key;
    
    }//if
    
    $class_map['dependencies'] = $dependency_list;
    
    $this->class_map[$key] = $class_map;
    
    // this gets cleared anytime something is added, because it is easier that way
    $this->children_class_map = array();
    
    return true;
  
  }//method
  
  /**
   *  get the name of the params that should be cached
   *
   *  @return array an array of the param names that should be cached    
   */
  public function cacheParams(){
    return array('class_map','parent_class_map','path_map','children_class_map');
  }//method
  
  /**
   *  return whether the $path has been looked at before
   *  
   *  @param  Path  $path the path to check against the internal paths
   *  @return boolean
   */
  protected function hasPath(Path $path){
    
    $ret_bool = true;
    $path_list = array_keys(
      array_merge(
        isset($this->path_map['folders']) ? $this->path_map['folders'] : array(),
        isset($this->path_map['files']) ? $this->path_map['files'] : array()
      )
    );
    
    // @todo  this is only one way, checking if the path is a Subpath of all the other
    // paths, but what if a child path has already been included (eg, foo/bar was 
    // added and then foo was added)? I solved this by putthing a check in setFile(),
    // though that will slow the addPath() method quite a bit as every file needs to
    // be checked if it has a parent in the path list
    
    if(!in_array((string)$path,$path_list,true)){
    
      $ret_bool = $path->inParents($path_list);
    
    }//if
    
    return $ret_bool;
    
  }//method
  
  /**
   *  get the class name that will be used in {@link setClass()}
   *
   *  @since  6-5-11
   *  @param  string  $class_name
   *  @return string  the class name         
   */
  protected function qualifyClassName($class_name){
  
    // do to a bug in php <5.3.6 we turn fully qualified class names to non-qualified ones...
    if($class_name[0] === '\\'){ $class_name = mb_substr($class_name,1); }//if
    
    return $class_name;
  
  }//method

}//method
