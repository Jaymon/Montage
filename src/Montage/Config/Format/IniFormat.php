<?php
/**
 *  handle ini config files
 *  
 *  @link http://php.net/manual/en/function.parse-ini-file.php  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 11-23-11
 *  @package montage
 *  @subpackage Config 
 ******************************************************************************/     
namespace Montage\Config\Format;

use Path;

class IniFormat extends Format {

  /**
   *  get an associative array from the given conifg file
   *
   *  @return array
   */
  protected function parseFields(Path $file){
  
    $ret_map = array();
  
    $ret_map = parse_ini_file((string)$file);
  
    if($ret_map === false){
      
      throw new \UnexpectedValueException(
        sprintf('failed to parse ini file %s',$file)
      );
    
    }//if
    
    return $ret_map;
  
  }//method

}//class
