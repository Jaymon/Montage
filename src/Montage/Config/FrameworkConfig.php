<?php
/**
 *  a giant application wide place to store configuration  
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-17-11
 *  @package montage
 *  @subpackage Config 
 ******************************************************************************/     
namespace Montage\Config;

use Montage\Config\Config;
use Montage\Path;

class FrameworkConfig extends Config {

  /**
   *  get the environment the app is running under      
   *
   *  @return string
   */
  public function getEnv(){ return $this->getField('env',''); }//method
  
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

}//class