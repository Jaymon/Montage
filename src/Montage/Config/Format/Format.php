<?php
/**
 *  the base class for any config formats
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 11-23-11
 *  @package montage
 *  @subpackage Config 
 ******************************************************************************/     
namespace Montage\Config\Format;

use Path;

abstract class Format {

  /**
   *  the config file
   *  
   *  @var  \Path
   */
  protected $file = null;

  /**
   *  create an instance of this class that will point to the [assed in $file path
   *  
   *  @param  \Path $file the full file path to the config file         
   */
  public function __construct(Path $file){
  
    $this->file = $file;
  
  }//method

  /**
   *  get an associative array from the given conifg file
   *
   *  @return array
   */
  public function getFields(){
  
    return $this->parseFields($this->file);
  
  }//method

  /**
   *  actually parse the config file and get the values
   *  
   *  this is format dependant so the child classes will handle this
   *      
   *  @param  \Path $file the full file path to the config file 
   *  @return array the associative array parsed from the config file
   */
  abstract protected function parseFields(Path $file);

}//class
