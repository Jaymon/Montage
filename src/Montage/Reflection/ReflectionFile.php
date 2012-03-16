<?php
/**
 *  uses token_get_all() method to reflect on a file and return information about
 *  what classes the file contains 
 *
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-14-11
 *  @package montage
 *  @subpackage dependency 
 ******************************************************************************/
namespace Montage\Reflection;

use Reflector;

class ReflectionFile implements Reflector {

  /**
   *  holds the source code filename
   *
   *  @var  string   
   */
  protected $filename = '';

  /**
   *  holds the actual source code contained in the {@link $filename}
   *  
   *  @var  string         
   */
  protected $body = '';

  /**
   *  create the instance
   *  
   *  @param  string  $filename the file that will be reflected
   */
  public function __construct($filename){
  
    // canary...
    if(empty($filename)){ throw new \InvalidArgumentException('$filename was empty'); }//if
    if(!is_file($filename)){
      throw new \InvalidArgumentException(
        sprintf('$filename (%s) was not a valid filepath',$filename)
      );
    }//if
    
    $this->filename = $filename;
    $this->body = file_get_contents($filename);
  
  }//method
  
  /**
   *  @see  getName()
   */
  public function getFileName(){ return $this->getName(); }//method
  
  /**
   *  return the filename this instance is reflecting
   *  
   *  @return string
   */
  public function getName(){ return $this->filename; }//method
  
  /**
   *  required for Reflector interface
   *  
   *  @return string      
   */
  public static function export(){ return ''; }//method
  
  /**
   *  required for Reflector interface
   *  
   *  @return string
   */
  public function __toString(){ return $this->body; }//method
  
  /**
   *  return true if this instance is the class file for $class_name
   * 
   *  @since  9-7-11    
   *  @param  string  $class_name
   *  @return boolean
   */
  public function hasClass($class_name){
  
    $ret_bool = false;
  
    $class_key = $this->normalizeClassName($class_name);
    $class_list = $this->getClasses();
    
    foreach($class_list as $class_map){
    
      $file_class_key = $this->normalizeClassName($class_map['class']);
      if($class_key === $file_class_key){
      
        $ret_bool = true;
        break;
      
      }//if
    
    }//foreach
  
    return $ret_bool;
  
  }//method
  
  /**
   *  get any php classes that are found in the file 
   *
   *  normally, a Reflection Class will return other Reflection instances but this
   *  doesn't do that because in order to do that you have to include the file so
   *  ReflectionClass can do its thing.      
   *      
   *  @return array a list of maps with keys like class, extends, and implements
   */
  public function getClasses(){
  
    // canary...
    if(empty($this->body)){ return array(); }//if
  
    $ret_list = array();
    $tokens = token_get_all($this->body);
    
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
          
          case T_ABSTRACT:
          
            // only try and find a class if it is actually a class (could be a function)
            // another way to do this would be to look behind from T_CLASS to see if there
            // is a T_ABSTRACT token
          
            $ai = $i;
            
            // move from the abstract to the class token...
            while($tokens[++$ai][0] !== T_CLASS){
            
              if($tokens[$ai][0] === T_FUNCTION){ break; }//if
            
            }//while
          
            if($tokens[$ai][0] === T_CLASS){
            
              list($i,$map) = $this->getClass($ai,$tokens,$namespace,$use_map,false);
              $ret_list[] = $map;
              
            }//if
          
            break;
          
          case T_INTERFACE:
          
            list($i,$map) = $this->getClass($i,$tokens,$namespace,$use_map,false);
            $ret_list[] = $map;
            
            break;
          
          case T_CLASS:

            list($i,$map) = $this->getClass($i,$tokens,$namespace,$use_map,true);
            $ret_list[] = $map;

            break;
          
        }//switch
      
      }//if
    
    }//foreach
  
    ///out::e($namespace,$use_map);
    
    return $ret_list;
  
  }//method
  
  /**
   *  since classes can be namespaced or be named something different if a USE was
   *  used, this class will normalize all that to get the actual class name   
   *
   *  @param  string  $class_name the found class name
   *  @param  string  $namespace  the namespace that this class belongs to
   *  @param  array $use_map  all the USE statements this namespace has
   *  @return string  the class name      
   */
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
      
      if($ret_str[0] !== '\\'){
    
        // it's fully qualified, so don't try to discover the namespace...
        $ret_str = sprintf('\\%s',$ret_str);
        
      }//if
    
    }//if/else
  
    return $ret_str;
  
  }//method
  
  /**
   *  parse out the parent class names (including interfaces)
   *  
   *  @param  integer $i  where the parser is in the tokens
   *  @param  array $tokens all the tokens
   *  @param  array $str_tokens
   *  @param  array $arr_tokens
   *  $param  string  $namespace  the namespace in current use
   *  @param  array $use_map  all the USE statements this namespace has
   *  @return array the current $i and the found info   
   */
  protected function getParentClassNames($i,$tokens,$str_tokens,$arr_tokens,$namespace,$use_map){
  
    $parent_class_list = array();
    $parent_class = '';
        
    for($i = $i + 1; !in_array($tokens[$i],$str_tokens,true) && (!is_array($tokens[$i]) || !in_array($tokens[$i][0],$arr_tokens,true)) ;$i++){
    
      if(is_string($tokens[$i])){
      
        if($tokens[$i] === ','){

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
  
  /**
   *  parse out the class name
   *  
   *  @param  integer $i  where the parser is in the tokens
   *  @param  array $tokens all the tokens
   *  $param  string  $namespace  the namespace in current use
   *  @param  array $use_map  all the USE statements this namespace has
   *  @param  boolean $is_callable  true if the class can be created, false otherwise   
   *  @return array the current $i and the found info
   */
  protected function getClass($i,$tokens,$namespace,$use_map,$is_callable = true){
  
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
      'implements' => $implements_list,
      'callable' => $is_callable
    );
  
    return array($i,$ret_map);
  
  }//method
  
  /**
   *  parse out the USE statements
   *  
   *  @link http://us.php.net/manual/en/language.namespaces.importing.php
   *      
   *  @param  integer $i  where the parser is in the tokens
   *  @param  array $tokens all the tokens
   *  @return array the current $i and the found info   
   */
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
      
      if(empty($alias)){
      
        if(($pos = mb_strrpos($namespace,'\\')) !== false){
        
          $alias = mb_substr($namespace,$pos + 1);
        
        }else{
        
          $alias = $namespace;
        
        }//if/else
      
      }//if
      
      $ret_map[$alias] = $namespace;
      
    }//if
  
    return array($i,$ret_map);
  
  }//method
  
  /**
   *  the USE statements can have AS aliases
   *      
   *  @param  integer $i  where the parser is in the tokens
   *  @param  array $tokens all the tokens
   *  @return array the current $i and the found info   
   */
  protected function getUseAlias($i,$tokens){
  
    $ret_str = '';
  
    // go until we hit the end of the line
    for($i = $i + 1; (($tokens[$i] !== ';') && $tokens[$i] !== ',') ;$i++){
      $ret_str .= is_string($tokens[$i]) ? $tokens[$i] : $tokens[$i][1];
    }//for
  
    return array($i,trim($ret_str));
  
  }//method
  
  /**
   *  parse out the current namespace
   *  
   *  @param  integer $i  where the parser is in the tokens
   *  @param  array $tokens all the tokens
   *  @return array the current $i and the found info   
   */
  protected function getNamespace($i,$tokens){
  
    $namespace = '';
          
    // go until we hit the end of the line
    for($i = $i + 1; ($tokens[$i] !== ';') && $tokens[$i] !== '{' ;$i++){
      if($tokens[$i][0] !== T_WHITESPACE){
        $namespace .= is_string($tokens[$i]) ? $tokens[$i] : $tokens[$i][1];
      }//if
    }//for
  
    if(!empty($namespace)){
      $namespace = sprintf('\\%s',$namespace);
    }//if
    
    return array($i,$namespace);
  
  }//method
  
  /**
   *  in order to do compares for classes, we need to make sure the classes are roughly the
   *  same structurally, this method does that
   *  
   *  @since  9-7-11
   *  @param  string  $class_name
   *  @return string
   */
  protected function normalizeClassName($class_name){
  
    if($class_name[0] !== '\\'){
      $class_name = '\\'.$class_name;
    }//if
    
    return mb_strtoupper($class_name);
  
  }//method

}//method
