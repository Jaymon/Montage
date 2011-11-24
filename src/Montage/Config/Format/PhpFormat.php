<?php
/**
 *  handle php config files
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 11-23-11
 *  @package montage
 *  @subpackage Config 
 ******************************************************************************/     
namespace Montage\Config\Format;

use Path;

class PhpFormat extends Format {

  /**
   *  get an associative array from the given conifg file
   *
   *  @return array
   */
  protected function parseFields(Path $file){
  
    // we wrap it in a closure so it can't access the class using $this in the config file
    $closure = function(Path $_f_i_l_e_){
    
      // move all the defined variables into the local closure symbol table
      include((string)$_f_i_l_e_);
      
      // take all the local variables and convert them into an array
      $ret_map = compact(array_keys(get_defined_vars()));
      
      // we named if funny so there is less chance a config value will clash
      unset($ret_map['_f_i_l_e_']);
      
      return $ret_map;
    
    };
    
    $ret_map = $closure($file);
    return $ret_map;
  
  }//method

}//class
