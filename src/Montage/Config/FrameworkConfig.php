<?php
/**
 *  a giant application wide place to store key/value pairs 
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-17-11
 *  @package montage
 *  @subpackage Config 
 ******************************************************************************/     
namespace Montage\Config;

use Montage\Config\Config;

class FrameworkConfig extends Config {

  public function getEnv(){ return $this->getField('env',''); }//method
  
  public function getDebugLevel(){ return $this->getField('debug_level',''); }//method
  
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

  public function getAppPath(){ return $this->getField('app_path',''); }//method

  public function getFrameworkPath(){ return $this->getField('framework_path',''); }//method
  
  public function getCharset(){ return $this->getField('charset','UTF-8'); }//method
  
  public function getTimezone(){ return $this->getField('timezone','UTC'); }//method

}//class
