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
  
  protected $docblock_index = 0;

  protected $docblock_index_start = 0;
  
  protected $line_buffer = array();

  protected $field_map = array();

  public function __construct($docblock){
  
    $this->parseDocBlock($docblock);
  
  }//method
  
  public static function export(){ return serialize($this); }//method
  public function __toString(){ return $this->docblock; }//method

  protected function bookmarkChar(){
  
    $this->docblock_index_start = $this->docblock_index;
  
  }//method
  
  protected function restoreChar(){
  
    $this->docblock_index = $this->docblock_index_start;
  
  }//method

  protected function currentChar(){
  
    // canary...
    if(!isset($this->docblock[$this->docblock_index])){
      $this->throwParseException('Parser has moved passed EOF');
    }//if
  
    return $this->docblock[$this->docblock_index];
  
  }//method

  protected function peekChar($offset = 1){
  
    $ret_char = null;
  
    if(isset($this->docblock[$this->docblock_index + $offset])){
    
      $ret_char = $this->docblock[$this->docblock_index + $offset];
    
    }//if
  
    return $ret_char;
  
  }//method

  protected function nextChar($offset = 1){
  
    $this->docblock_index += $offset;
    return $this->currentChar();
  
  }//method

  protected function parseDocBlock($docblock){
    
    // canary...
    if(empty($docblock)){ $this->throwParseException('$docblock was empty'); }//if
  
    $this->docblock = $docblock;
    $this->docblock_index = 0;
    $this->line_buffer = array();
    
    \out::e(str_split($this->docblock));
    
    $this->parseOpen();
    
    $this->parseShortDesc();
    
    $this->parseLongDesc();
    
    $this->parseTags();
    
    $this->parseEmptyLines();
  
    ///\out::e(substr($this->docblock,$this->docblock_index));
    \out::e($this->field_map);
  
    $this->parseClose();
  
  }//method
  
  protected function parseClose(){
  
    $ret_count = $this->parseSpace();
    $is_valid = true;
    
    if(!$this->isChar('*')){ $is_valid = false; }//if
    if(!$this->isChar('/',1)){ $is_valid = false; }//if
  
    if($is_valid){
    
      $ret_count = 2;
      $this->nextChar(1); // move to the very last char
    
    }else{
    
      $this->throwParseException('A DocBlock must close with a *'.'/');
    
    }//if/else
  
  }//method
  
  protected function parseOpen(){
  
    $ret_count = 0;
    $is_valid = true;
    
    if(!$this->isChar('/')){ $is_valid = false; }//if
    if(!$this->isChar('*',1)){ $is_valid = false; }//if
    if(!$this->isChar('*',2)){ $is_valid = false; }//if
  
    if($is_valid){
    
      $ret_count = 3;
    
      $this->nextChar(3); // move to the char after the last *
    
    }else{
    
      $this->throwParseError('DocBlock must start with a /'.'**');
    
    }//if/else

    $ret_count += $this->parseWhitespace();
    
    return $ret_count;
  
  }//method
  
  protected function parseTag(){
  
    $ret_count = $this->parseLineStart();
    
    if(!$this->isChar('@')){
      $this->throwParseException('Not a valid tag');
    }//if
    
    // get the name...
    do{
    
      $name .= $this->currentChar();
      $this->nextChar();
      
    }while(!$this->isWhitespace());
    
    \out::e($name);
    
    // now the rest of the line and any other lines until another tag or EOF is found
    // will be a part of this tag
    
  }//method
  
  protected function parseLine(){
  
    $ret_count = $this->parseLineStart();
    $line = '';
    
    if($this->isTag()){ $this->throwParseException('A Line is not a tag'); }//if
    
    while(!$this->isLinebreak()){
      
      $line .= $this->currentChar();
      $this->nextChar();
      $ret_count++;
    
    }//while
    
    $this->line_buffer[] = $line;
    
    $ret_count += $this->parseLinebreak();
    
    return $ret_count;
    
  }//method 
  
  protected function parseLineStart(){
  
    $ret_count = $this->parseWhitespace();
    
    if($this->isChar('*')){
    
      $this->nextChar();
      $ret_count++;
    
      $ret_count += $this->parseSpace();
  
    }else{
    
      $this->throwParseException('A valid line starts with [space] * [space]');
    
    }//if/else
  
    return $ret_count;
  
  }//method
  
  protected function parseEmptyLines(){
  
    $ret_count = 0;
  
    try{
      
      do{
      
        $this->bookmarkChar();
        $count = $this->parseEmptyLine();
        $ret_count += $count;
      
      }while($count > 0);
      
    }catch(\DomainException $e){
    
      $this->restoreChar();
    
    }//try/catch
  
    return $ret_count;
  
  }//method
  
  protected function parseEmptyLine(){
  
    $ret_count = $this->parseLineStart();
    $ret_count += $this->parseLinebreak();
    
    return $ret_count;
  
  }//method
  
  protected function isEOF(){
    
    $ret_bool = false;
    
    if($this->isChar('*')){
    
      if($this->isChar('/')){
      
        $ret_bool = true;
      
      }//if
    
    }//if
    
    return $ret_bool;
  
  }//method
  
  protected function parseLinebreak(){
  
    $ret_count = 0;
    $char = $this->currentChar();
    
    if($char === "\n"){
    
      $ret_count++;
    
    }else if($char === "\r"){
    
      $ret_count++;
    
      if($this->peekChar(1) === "\n"){
      
        $ret_count++;
      
      }//if
    
    }//if/else if
  
    if($ret_count > 0){
    
      $this->nextChar($ret_count);
    
    }else{
    
      $this->throwParseException('A valid linebreak is "\n" | "\r" | "\r\n"');
    
    }//if/else
  
    return $ret_count;
  
  }//method
  
  protected function isLinebreak(){
  
    $ret_bool = false;
    $char = $this->currentChar();
    
    if($char === "\n"){
    
      $ret_bool = true;
    
    }else if($char === "\r"){
    
      if($this->peekChar(1) === "\n"){
      
        $ret_bool = true;
      
      }//if
    
    }//if/else if
  
    return $ret_bool;
  
  }//method
  
  protected function parseLongDesc(){
  
    $ret_count = 0;
    ///$this->line_buffer = array();
  
    try{
    
      while(true){
      
        $this->bookmarkChar();
        $ret_count += $this->parseLine();
        $ret_count += $this->parseEmptyLines();
        
      }//while
      
    }catch(\DomainException $e){
    
      $this->restoreChar();
      \out::e($e->getMessage());
    
    }//try/catch
  
    $this->setLines('long_desc');
    
    return $ret_count;
  
  }//method
  
  protected function parseShortDesc(){
  
    try{
    
      $this->bookmarkChar();
    
      $ret_count = $this->parseLine();
      
      if($count = $this->parseEmptyLines()){
      
        $ret_count += $count;
        $this->setLines('short_desc');
        
      }//if
      
    }catch(\DomainException $e){
    
      $this->restoreChar();
    
    }//try/catch
    
    return $ret_count;
  
  }//method
  
  protected function parseSpace(){
  
    $ret_count = 0;
    
    $char = $this->currentChar();
    while(($char === ' ') || ($char === "\t")){
    
      $char = $this->nextChar();
      $ret_count++;
    
    }//while
    
    return $ret_count;
  
  }//method
  
  protected function parseWhitespace(){
  
    $ret_count = 0;
  
    while($this->isWhitespace()){
      
      $this->nextChar();
      $ret_count++;
      
    }//while
  
    return $ret_count;
  
  }//method

  protected function isTag(){ return $this->isChar('@'); }//method

  protected function isWhitespace(){ return ctype_space($this->currentChar()); }//method

  protected function isChar($expected_char,$offset = 0){
  
    $ret_bool = true;
    $char = $this->peekChar($offset);
  
    if($char !== $expected_char){
  
      $ret_bool = false;
    
    }//if
    
    return $ret_bool;
  
  }//method
  
  protected function throwParseException($msg){
  
    $msg .= '. Remaining DocBlock: '.mb_substr($this->docblock,$this->docblock_index);
  
    throw new \DomainException($msg);
  
  }//method
  
  protected function setLines($key){
  
    $this->field_map[$key] = join(PHP_EOL,$this->line_buffer);
    $this->line_buffer = array();
    
  }//method

}//method
