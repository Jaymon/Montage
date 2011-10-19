<?php
/**
 *  parse a docblock comment  
 * 
 *  @link https://wiki.php.net/rfc/docblockparser
 *  @link http://stackoverflow.com/questions/5604587/php-annotations-addendum-or-doctrine-annotation
 *  @link http://www.doctrine-project.org/projects/common/2.0/docs/reference/annotations/en  
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 10-18-11
 *  @package Montage
 *  @subpackage Reflection 
 ******************************************************************************/
namespace Montage\Reflection;

class ReflectionDocBlock implements \Reflector {

  protected $docblock = '';
  
  protected $docblock_index = -1;

  protected $field_map = array();

  public function __construct($docblock){
  
    $this->parseDocBlock($docblock);
  
  }//method
  
  public static function export(){ return serialize($this); }//method
  public function __toString(){ return $this->docblock; }//method

  protected function getCurrentChar(){
  
    // canary...
    if(!isset($this->docblock[$this->docblock_index])){ return null; }//if
  
    return $this->docblock[$this->docblock_index];
  
  }//method

  protected function peekNextChar($offset = 1){
  
    $ret_char = null;
  
    if(isset($this->docblock[$this->docblock_index + $offset])){
    
      $ret_char = $this->docblock[$this->docblock_index + $offset];
    
    }//if
  
    return $ret_char;
  
  }//method

  protected function getNextChar(){
  
    $this->docblock_index++;
    return $this->getCurrentChar();
  
  }//method

  protected function parseDocBlock($docblock){
    
    // canary...
    if(empty($docblock)){ throw new \InvalidArgumentException('$docblock was empty'); }//if
  
    $this->docblock = $docblock;
    $this->docblock_index = -1;
    
    $this->isChar($this->getNextChar(),'/');
    $this->isChar($this->getNextChar(),'*');
    $this->isChar($this->getNextChar(),'*');
  
    $this->getNextChar(); // should be whitespace
  
    $this->parseWhitespace();
    
    if($this->isChar($this->getCurrentChar(),'*',false) && $this->isChar($this->peekNextChar(),'/',false)){
    
      
    }else{
    
      $this->parseShortDesc();
      
    }//if/else
  
  }//method
  
  protected function parseTag(){
  
    // canary...
    $this->isChar($this->getCurrentChar(),'@');
    
    // get the name...
    $name = '';
    
    do{
    
      $name .= $this->getCurrentChar();
      $char = $this->getNextChar();
    
    }while(!$this->isWhitespace($char));
  
  }//method
  
  protected function parseLine(){
  
    $this->parseSpace(true);
  
    $this->isChar($this->getCurrentChar(),'*');
    
    $this->getNextChar();
    
    $this->parseSpace();
    
    if($this->isChar($this->getCurrentChar(),'@',false)){
    
      $this->parseTag();
    
    }else{
    
      \out::e($this->getCurrentChar());
      $this->parseStr();
    
    }//if/else
  
  }//method
  
  protected function parseShortDesc(){
  
    $this->parseLine();
  
  
  }//method
  
  protected function parseSpace($is_optional = true){
  
    // canary...
    $char = $this->getCurrentChar();
    if(($char !== ' ') && ($char !== "\t")){
      if($is_optional){
        return 0;
      }else{
        $this->throwParseException('Expected Space');
      }//if/else
    }//if
    
    $ret_count = 0;
    
    do{
    
      $char = $this->getNextChar();
      $ret_count++;
    
    }while(($char === ' ') || ($char === "\t"));
    
    return $ret_count;
  
  }//method
  
  protected function parseWhitespace(){
  
    // canary...
    if(!ctype_space($this->getCurrentChar())){
      $this->throwParseException('Whitespace was expected');
    }//if
  
    $ret_count = 1;
  
    // move passed all whitespace
    while(ctype_space($this->getNextChar())){ $ret_count++; }//while
    
    return $ret_count;
  
  }//method

  protected function isWhitespace($char){ return ctype_space($char); }//method

  protected function isChar($char,$expected_char,$is_fatal = true){
  
    $ret_bool = true;
  
    if($char !== $expected_char){
    
      if($is_fatal){
      
        $this->throwParseException(
          sprintf('Char "%s" does not match expected char "%s"',$char,$expected_char)
        );
        
      }else{
      
        $ret_bool = false;
      
      }//if/else
    
    }//if
    
    return $ret_bool;
  
  }//method
  
  protected function throwParseException($msg){
  
    throw new \DomainException($msg);
  
  }//method

}//method
