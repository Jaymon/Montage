<?php
/**
 *  this class provides annotation support for class properties
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 10-25-11
 *  @package montage 
 ******************************************************************************/
namespace Montage\Annotation;

class ParamAnnotation extends Annotation {

  /**
   *  create an annotation from the given reflection
   *
   *  @param  \ReflectionProperty $reflection   
   */
  public function __construct(\ReflectionProperty $reflection){
  
    parent::__construct($reflection);
  
  }//method
  
  /**
   *  get the class name that this annotation represents
   *  
   *  @return string  the found class name
   */
  public function getClassName(){
  
    $ret_str = '';
    
    if($type = $this->findType()){
    
      $ret_str = $this->findClassName($type);
    
    }//if
    
    return $ret_str;
  
  }//method
  
  /**
   *  discover the class name from the given type
   *  
   *  the $type can come from different places depending on what tag was being looked at      
   *
   *  @param  string  $type
   *  @return string  the found class name      
   */
  protected function findClassName($type){
  
    // canary, a valid class name has to be the only thing in the type...
    if(mb_strpos($type,'|') !== false){ return ''; }//if
    
    $class_name = '';
    
    // only check non-builtin types...
    $regex = sprintf('#^(%s)$#i',join('|',$this->types));
    
    // make sure the var type is a class name...
    if((mb_strpos($type,'\\') !== false) || !preg_match($regex,$type)){
      
      $class_name = $type;
      
    }//if
    
    return $class_name;
    
  }//method
  
  /**
   *  find the type depending on the class's reflection instance
   *
   *  @return string  the found type   
   */
  protected function findType(){
  
    $type = '';
  
    if($this->reflection instanceof \ReflectionProperty){
    
      if($type = $this->rdocblock->getTag('var')){
      
        // var tags can be in the form: type desc, so get rid of the desc...
        $type = preg_split('#\s+#',$type,2);
        $type = $type[0];
        
      }//if
    
    }else{
    
      throw new \DomainException('TBI for other Reflection types');
    
    }//if/else
  
    return $type;
  
  }//method
  
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
