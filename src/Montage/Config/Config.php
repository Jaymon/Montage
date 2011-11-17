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

use Montage\Config\Interfaces\PhpInterface;

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


  public function load($config_filename){
  
    $config_file = $this->findFile($config_file);
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
    $interface = $this->getInterface($ext);
    $interface->addFile($config_file);
    
    $field_map = $interface->getFields();
  
    return $this->addFields($field_map);
  
  }//method
  
  protected function findFile($config_filename){
  
    // canary...
    if(empty($config_filename)){
      throw new \InvalidArgumentException('$config_filename was empty');
    }//if
    $config_file = new Path($config_filename);
    return $config_file->locate($this->getPaths());
  
  }//method

  protected function getInterface($ext){
  
    $ret_instance = null;
  
    switch($ext){
    
      case 'php':
    
        $ret_instance = new PhpInterface();
        break;

      default:
      
        throw new \InvalidArgumentException(
          sprintf('no interface defined for config file extension: %s',$ext)
        );
        
        break;
    
    }//switch
  
    return $ret_instance;
  
  }//method

}//class
