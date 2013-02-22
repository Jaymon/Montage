<?php
/**
 *  a giant application wide place to store configuration  
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-17-11
 *  @package montage
 *  @subpackage Config 
 ******************************************************************************/     
namespace Montage\Config;

use Montage\Config\Config;
use Path;

class FrameworkConfig extends Config {

  /**
   *  get the environment the app is running under      
   *
   *  @return string
   */
  public function getEnv(){ return $this->getField('env',''); }//method
  
  /**
   *  true if the passed in environment is the currently set environment      
   *
   *  @since  11-1-11   
   *  @return boolean
   */
  public function isEnv($val){ return $this->isField('env',$val); }//method
  
  /**
   *  get the debug level the app is running under  
   *   
   *  @return integer
   */
  public function getDebugLevel(){ return $this->getField('debug_level',0); }//method
  
  /**
   *  ture if the app is showing errors, false if errors are hidden
   *  
   *  @return boolean   
   */
  public function showErrors(){
  
    $debug_level = $this->getDebugLevel();
    return $debug_level > 0;
  
  }//method
  
  /**
   *  get the global error level   
   *
   *  -1 is synonomous with the older: (E_ALL | E_STRICT | E_PARSE)
   *  
   *  @link http://www.php.net/manual/en/errorfunc.constants.php
   *  @return integer an OR'd group of E_ global constants   
   */
  public function getErrorLevel(){ return $this->getField('error_level',-1); }//method

  /**
   *  get the application path
   *  
   *  the application path is the root directory where your application lives      
   *
   *  @return string   
   */
  public function getAppPath(){ return $this->getField('app_path',''); }//method
  
  /**
   *  get the public path
   *  
   *  the public path is the path where the index.php would be      
   *
   *  @return string   
   */
  public function getPublicPath(){ return new Path($this->getField('app_path'),'public'); }//method
  
  /**
   *  get the application's data path
   *  
   *  tha data path is where application data should go      
   *
   *  @return string   
   */
  public function getDataPath(){ return new Path($this->getField('app_path'),'data'); }//method

  /**
   *  get the framework path
   *  
   *  the framework path is the root directory where the framework lives      
   *
   *  @return string   
   */
  public function getFrameworkPath(){ return $this->getField('framework_path',''); }//method
  
  /**
   *  get all the plugin paths
   *  
   *  the plugin paths are the root directories of your installed plugins      
   *
   *  @return array
   */
  public function getPluginPaths(){ return $this->getField('plugin_paths',array()); }//method
  
  /**
   *  get the app's set Charset
   *  
   *  @return string   
   */
  public function getCharset(){ return $this->getField('charset','UTF-8'); }//method
  
  /**
   *  get the app's set Timezone
   *  
   *  @return string
   */
  public function getTimezone(){ return $this->getField('timezone','UTC'); }//method
  
  /**
   *  overrides parent to allow paths to be appended instead of just over-written  
   *
   *  @since  4-3-12   
   *  @param  array $field_map  the associative array returned from the config file   
   */
  protected function mergeFileFields(array $field_map){
    
    $path_key_list = array(
      'src_paths',
      'view_paths',
      'vendor_paths',
      'asset_paths',
      'plugin_paths',
      'test_paths',
      'config_paths'
    );
    
    foreach($path_key_list as $path_key){
    
      if(!empty($field_map[$path_key])){
      
        if(isset($this->field_map[$path_key])){
        
          $this->field_map[$path_key] = array_merge(
            $this->field_map[$path_key],
            (array)$field_map[$path_key]
          );
        
        }else{
        
          $this->field_map[$path_key] = $field_map[$path_key];
        
        }//if/else
        
        unset($field_map[$path_key]);
        
      }//if
    
    }//foreach
    
    // merge the rest of the file fields normally
    return parent::mergeFileFields($field_map);
  
  }//method

}//class
