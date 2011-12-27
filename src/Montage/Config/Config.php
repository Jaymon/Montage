<?php
/**
 *  the base class for any config objects that are used to configure stuff
 *  
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-17-11
 *  @package montage
 *  @subpackage Config 
 ******************************************************************************/     
namespace Montage\Config;

use Montage\Field\Field;
use Montage\Config\Configurable;

use Path;
use FlattenArrayIterator;

use Montage\Config\Format\PhpFormat;

abstract class Config extends Field implements Configurable {

  /**
   *  holds the user submitted paths
   *  
   *  @since  11-16-11   
   *  @var  array
   */
  protected $path_list = array();
  
  /**
   *  get all the user submitted paths
   *   
   *  @since  11-16-11
   *  @return array a lit of paths   
   */
  public function getPaths(){ return $this->path_list; }//method

  /**
   *  add all paths in the list to the path list
   *  
   *  @since  11-16-11   
   *  @param  array $path_list  a list of paths
   */
  public function addPaths(array $path_list){
  
    foreach($path_list as $path){ $this->addPath($path); }//foreach
  
  }//method

  /**
   *  add one path to the list of paths
   *
   *  @since  11-16-11   
   *  @param  string  $path the path to add to the user defined paths
   */
  public function addPath($path){
  
    $path = new Path($path); // this normalizes the path
    $this->path_list[] = $path;
  
  }//method

  /**
   *  load a config file into this class
   *  
   *  a config file should consist of just simple key/val pairs, you can't do anything
   *  crazy in the config files (by default). Save the complex stuff for other parts of
   *  your code, not your config files
   *
   *  @param  string  $config_filename  usually just the name.ext, not the full path
   *  @param  string  $base_path  the path to use for any ./ config value resolution, see {@link normalizePaths()}    
   */
  public function load($config_filename,$base_path = ''){
  
    $config_file = $this->normalizeFilePath($config_filename);
    if(empty($config_file)){
      throw new \UnexpectedValueException(
        sprintf(
          'no config file named %s could be found in paths: [%s]',
          $config_filename,
          join(',',$this->getPaths())
        )
      );
    }//if
    
    $ext = mb_strtolower($config_file->getExtension());
    $instance = $this->getFileFormatInstance($ext,$config_file);
    $field_map = $instance->getFields();
    $field_map = $this->normalizePaths($base_path,$field_map);
  
    return $this->mergeFileFields($field_map);
  
  }//method
  
  /**
   *  find any paths in the config file that begin with ./ and normalize them to the $base_path
   *  
   *  @since  12-22-11
   *  @param  string  $base_path  the path that will be used to replace ./
   *  @param  array $field_map  the fields returned from parsing the config file
   *  @return array
   */
  protected function normalizePaths($base_path,array $field_map){
  
    // canary
    if(empty($base_path)){ return $field_map; }//if
    
    $iterator = new FlattenArrayIterator($field_map);
    foreach($iterator as $val){
    
      if(is_string($val)){
      
        $is_path_val = isset($val[0],$val[1]) // it has at least 2 characters
          // @note  I can't decide which one I like better, ./ is universal and always means
          // current directory, I am actually changing that to mean app_path, where ~/ means home
          // dir and is variable, so in this context, ~/ could mean app_path since that is the app's
          // home directory. I also thought of using my own, something like -/ or +/
          && (($val[0] == '.') || ($val[0] == '~')) // the first char starts with . or ~ 
          && (($val[1] == '/') || ($val[1] == '\\')); // the second char is a directory separator
      
        if($is_path_val){
        
          // strip off the ./
          $v = mb_substr($val,2);
          $p = new Path($base_path,$v);
          
          $keys = $iterator->keys();
          $key = array_pop($keys);
          
          // we need to replace the original value, we do it this way because you
          // can't do (as &$val) on an iterator, too bad...
          $pointer = &$field_map;
          foreach($keys as $k){ $pointer = &$pointer[$k]; }//foreach
          $pointer[$key] = $p;
          
        }//if
        
      }//if
    
    }//foreach
  
    ///\out::e($field_map);
    return $field_map;
  
  }//method
  
  /**
   *  this is just a wrapper method to make it easy for child classes to determine how they
   *  are going to merge the just read in config file fields   
   *
   *  @param  array $field_map  the associative array returned from the config file   
   */
  protected function mergeFileFields(array $field_map){ return $this->addFields($field_map); }//method
  
  /**
   *  actually find the full path to the config file
   *
   *  @param  string  $config_filename  usually just the name.ext, not the full path   
   *  @return \Path the full path to the config file
   */
  protected function normalizeFilePath($config_filename){
  
    // canary...
    if(empty($config_filename)){
      throw new \InvalidArgumentException('$config_filename was empty');
    }//if
    
    $ret_file = null;
    $config_file = new Path($config_filename);
    
    if($config_file->isFile()){
    
      $ret_file = $config_file;
    
    }else{
    
      $ret_file = $config_file->locate($this->getPaths());
    
    }//if/else
    
    
    return $ret_file;
  
  }//method

  /**
   *  actually get the object that will parse and return the config file
   *
   *  @param  string  $ext  the config file's extension
   *  @param  \Path $file the full path to the config file
   */
  protected function getFileFormatInstance($ext,Path $file){
  
    $ret_instance = null;
    $class_name = sprintf('%s\\Format\\%sFormat',__namespace__,ucfirst($ext));
    
    // canary, make sure the format class exists...
    if(!class_exists($class_name)){
    
      throw new \InvalidArgumentException(
        sprintf('no interface defined for config file extension: %s, tried: %s',$ext,$class_name)
      );
    
    }//if
  
    $ret_instance = new $class_name($file);
    return $ret_instance;
  
  }//method

}//class
