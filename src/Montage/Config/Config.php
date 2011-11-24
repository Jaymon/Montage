<?php
/**
 *  the base class for any config objects that are used to configure stuff
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-17-11
 *  @package montage
 *  @subpackage Config 
 ******************************************************************************/     
namespace Montage\Config;

use Montage\Field\Field;
use Montage\Config\Configurable;
use Path;

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
   */
  public function load($config_filename){
  
    $config_file = $this->findFile($config_filename);
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
  
    return $this->mergeFileFields($field_map);
  
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
  protected function findFile($config_filename){
  
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
