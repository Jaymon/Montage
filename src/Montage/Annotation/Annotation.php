<?php
/**
 *  common annotation methods
 *   
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 10-25-11
 *  @package montage 
 ******************************************************************************/
namespace Montage\Annotation;

use Montage\Reflection\ReflectionDocBlock;

abstract class Annotation {

  /**
   *  hold the reflection object passed into {@link __construct()}
   *
   *  @var  \Reflector   
   */
  protected $reflection = null;
  
  /**
   *  hod the parsed doc block for the {@link $reflection} object
   *
   *  @var  \Montage\Reflection\ReflectionDocBlock   
   */
  protected $rdocblock = null;
  
  /**
   *  the built-in types
   *      
   *  @link http://us.php.net/manual/en/language.types.intro.php
   *  @var  array
   */
  protected $types = array(
    'bool',
    'boolean',
    'int',
    'integer',
    'float',
    'double',
    'string',
    'array',
    'object',
    'resource',
    'mixed',
    'null',
    'callback',
    'self',
    'this'
  );

  /**
   *  create an annotation from the given reflection
   *
   *  @param  \Reflector  $reflection   
   */
  public function __construct(\Reflector $reflection){
  
    $this->reflection = $reflection;
    
    if($docblock = $reflection->getDocComment()){
    
      $this->rdocblock = new ReflectionDocBlock($docblock);
    
    }//if
    
  }//method
  
  /**
   *  get the doc block that was used to create the annotation
   *
   *  @return \Montage\Reflection\ReflectionDocBlock   
   */
  public function getDocBlock(){ return $this->rdocblock; }//method
  
  /**
   *  get the namespace the docblock belongs to
   * 
   *  @return string
   */
  /* protected function getDocBlockNamespace(){
  
    if($this->reflection instanceof \ReflectionProperty){
    
      $ret_str = $this->reflection->getDeclaringClass()->getNamespaceName();
    
    }else{
    
      $ret_str = $this->reflection->getNamespaceName();
    
    }//if/else
  
    return $ret_str;
  
  }//method */

}//class     
