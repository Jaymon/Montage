<?php
/**
 *  parse a docblock comment 
 * 
 *  @link https://wiki.php.net/rfc/docblockparser
 *  @link http://stackoverflow.com/questions/5604587/php-annotations-addendum-or-doctrine-annotation
 *  @link http://www.doctrine-project.org/projects/common/2.0/docs/reference/annotations/en  
 *  @link https://github.com/mvriel/Docblox/blob/master/src/DocBlox/Reflection/DocBlock.php  
 *  @link http://stackoverflow.com/questions/2531085/are-there-any-php-docblock-parser-tools-available 
 * 
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 10-18-11
 *  @package Montage
 *  @subpackage Reflection 
 ******************************************************************************/
namespace Montage\Reflection;

class ReflectionDocBlock implements \Reflector {

  /**
   *  holds the full raw docblock this instance is reflection on
   *
   *  @var  string   
   */
  protected $docblock = '';
  
  /**
   *  during parsing, this holds the current char being parsed
   *
   *  @var  integer   
   */
  protected $docblock_index = 0;

  /**
   *  holds the bookmarked char in case the parser fails it can restart here
   *
   *  @var  integer   
   */
  protected $docblock_index_bookmark = 0;
  
  /**
   *  holds lines found during parsing
   *
   *  @var  array   
   */
  protected $line_buffer = array();

  /**
   *  this holds the finished parsed fields from the docblock
   *
   *  @var  array   
   */
  protected $field_map = array();

  /**
   *  the built-in types
   *  
   *  this is currently not used, but I had some ideas for the future and I figured 
   *  I would keep this here for now 
   *      
   *  @link http://us.php.net/manual/en/language.types.intro.php
   *  @var  array   
   */
  protected $primitive_types = array(
    'bool',
    'boolean',
    'integer',
    'float',
    'double',
    'string',
    'array',
    'object',
    'resource',
    'mixed',
    'null',
    'callback'
  );

  /**
   *  create the object
   *  
   *  @param  string  $docblock the docblock to be reflected
   */
  public function __construct($docblock){
  
    $this->parseDocBlock($docblock);
  
  }//method

  /**
   *  get the short description
   *
   *  @return string   
   */
  public function getShortDesc(){ return $this->getField('short_desc',''); }//method
  
  /**
   *  get the long description
   *
   *  @return string   
   */
  public function getLongDesc(){ return $this->getField('long_desc',''); }//method
  
  /**
   *  get the tag
   *
   *  @param  string  $name a tag name   
   *  @return string   
   */
  public function getTag($name){ return $this->getField($name,''); }//method
  
  /**
   *  true if the tag exists
   *
   *  @param  string  $name a tag name   
   *  @return boolean
   */
  public function hasTag($name){
  
    return isset($this->field_map[$name]);
  
  }//method
  
  public static function export(){ return serialize($this); }//method
  public function __toString(){ return $this->docblock; }//method

  /**
   *  get the field from field_map
   *  
   *  @param  string  $name
   *  @param  mixed $default_val
   *  $return mixed usually a string, but could be an array
   */
  protected function getField($name,$default_val = ''){
  
    $ret_str = $default_val;
    if(isset($this->field_map[$name])){
      $ret_str = $this->field_map[$name];
    }//if
    
    return $ret_str;
  
  }//method

  /**
   *  bookmarks the index so parsing can try again on failure
   */
  protected function bookmarkChar(){
  
    $this->docblock_index_bookmark = $this->docblock_index;
  
  }//method
  
  /**
   *  restores the previously bookmarked char
   */
  protected function restoreChar(){
  
    $this->docblock_index = $this->docblock_index_bookmark;
  
  }//method

  /**
   *  gets the current char
   *
   *  @return string   
   */
  protected function currentChar(){
  
    // canary...
    if(!isset($this->docblock[$this->docblock_index])){
      $this->throwParseException('Parser has moved passed EOF');
    }//if
  
    return $this->docblock[$this->docblock_index];
  
  }//method

  /**
   *  take a look at the current index plus offset
   *
   *  @param  integer $offset
   *  @return string  the char at current + offset, null if it doesn't exist   
   */
  protected function peekChar($offset = 1){
  
    $ret_char = null;
  
    if(isset($this->docblock[$this->docblock_index + $offset])){
    
      $ret_char = $this->docblock[$this->docblock_index + $offset];
    
    }//if
  
    return $ret_char;
  
  }//method

  /**
   *  move the current index to current + $offset
   *
   *  @param  integer $offset
   *  @return string  the new current char   
   */
  protected function nextChar($offset = 1){
  
    $this->docblock_index += $offset;
    return $this->currentChar();
  
  }//method

  /**
   *  actually parse the docblock
   *
   *  @param  string  $docblock what's being parsed
   */
  protected function parseDocBlock($docblock){
    
    // canary...
    if(empty($docblock)){ $this->throwParseException('$docblock was empty'); }//if
  
    $this->docblock = $docblock;
    $this->docblock_index = 0;
    $this->line_buffer = array();
    
    ///\out::e(str_split($this->docblock));
    
    $this->parseOpen();
    $this->parseShortDesc();
    $this->parseLongDesc();
    $this->parseTags();
    $this->parseEmptyLines();
  
    ///\out::b();
    ///\out::e($this->docblock);
    ///\out::e($this->field_map);
  
    $this->parseClose();
  
  }//method
  
  /**
   *  parse the close of a docblock
   *  
   *  close is defined as [whitespace] { * }+ /     
   *
   *  @return integer   
   */
  protected function parseClose(){
  
    $ret_count = $this->parseWhitespace();
    $is_valid = true;
    $offset = 1;
    
    if($this->isChar('*')){
    
      // allow for multi star closing docblock lines...
      while($this->isChar('*',$offset)){ $offset++; }//while
    
    }else{
    
      $is_valid = false;
      
    }//if/else

    if(!$this->isChar('/',$offset)){ $is_valid = false; }//if
  
    if($is_valid){
    
      $ret_count = 2;
      $this->nextChar(1); // move to the very last char
    
    }else{
    
      $this->throwParseException('A DocBlock must close with a *'.'/');
    
    }//if/else
  
  }//method
  
  /**
   *  parse a docblock opening
   *  
   *  open is defined as /** [whitespace]      
   *
   *  @return integer   
   */
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
  
  /**
   *  parse any @name tags from the docblock
   *      
   *  @return integer
   */        
  protected function parseTags(){
  
    $ret_count = 0;

    try{

      $this->bookmarkChar();
  
      while($count = $this->parseTag()){
      
        $ret_count += $count;
        
      }//if
      
    }catch(\DomainException $e){
    
      $this->restoreChar();
    
    }//try/catch
     
    return $ret_count;
  
  }//method
  
  /**
   *  parse one @name tags from the docblock
   *  
   *  a tag is defined as: 
   *    [space] , "* " , "@" , tagname , space , { string | emptyline }* , linebreak ;
   *      
   *  @return integer
   */
  protected function parseTag(){
  
    $ret_count = $this->parseLineStart();
    $name = '';
    
    if(!$this->isChar('@')){
      $this->throwParseException('Not a valid tag');
    }//if
    
    $this->nextChar(); // move past the @
    
    // get the name...
    do{
    
      $name .= $this->currentChar();
      $this->nextChar();
      $ret_count++;
      
    }while(!$this->isWhitespace());
    
    // finish the rest of the line...
    $ret_count += $this->parseSpace();
    $ret_count += $this->parseStr();
    
    $this->parseEmptyLines();
    
    try{
    
      // now get any other lines that are not tag lines...
      while(true){
      
        $this->bookmarkChar();
        $ret_count += $this->parseLine();
        $ret_count += $this->parseEmptyLines();
        
      }//while
      
    }catch(\DomainException $e){
    
      $this->restoreChar();
    
    }//try/catch
  
    $this->setLines($name);
    
    return $ret_count;
    
  }//method
  
  /**
   *  parse the body of a line
   *
   *  this isn't really a string, more like a sentence, but the grammar I was using
   *  called it a string, so I just stuck with the same names   
   *      
   *  a str is defined as: { character }+
   *  
   *  @return integer
   */
  protected function parseStr(){
  
    $ret_count = 0;
    $line = '';
  
    while(!$this->isLinebreak()){
      
      $line .= $this->currentChar();
      $this->nextChar();
      $ret_count++;
    
    }//while
  
    $this->line_buffer[] = $line;
  
  }//method
  
  /**
   *  parse a line
   *  
   *  defined: [space] , "*" , [space] , any character minus "@" , string , [space] , linebreak   
   *
   *  @return integer   
   */        
  protected function parseLine(){
  
    $ret_count = $this->parseLineStart();
    $line = '';
    
    if($this->isChar('@')){ $this->throwParseException('A Line is not a tag'); }//if
    
    $ret_count += $this->parseStr();
    $ret_count += $this->parseLinebreak();
    
    return $ret_count;
    
  }//method 
  
  /**
   *  parse the start of a line
   *  
   *  defined: [space] , "*" , [space]    
   *
   *  @return integer   
   */
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
  
  /**
   *  parse all the empty lines in a row
   *
   *  @return integer      
   */
  protected function parseEmptyLines(){
  
    $ret_count = 0;
  
    try{
      
      do{
      
        $this->bookmarkChar();
        $count = $this->parseEmptyLine();
        
        if($count > 0){
          
          $ret_count += $count;
          $this->line_buffer[] = PHP_EOL;
        
        }//if
      
      }while($count > 0);
      
    }catch(\DomainException $e){
    
      $this->restoreChar();
    
    }//try/catch
  
    return $ret_count;
  
  }//method
  
  /**
   *  parse an empty lines
   *
   *  defined: [space] , "*" , [space] , linebreak
   *  
   *  @return integer      
   */
  protected function parseEmptyLine(){
  
    $ret_count = $this->parseLineStart();
    $ret_count += $this->parseLinebreak();
    
    return $ret_count;
  
  }//method
  
  /**
   *  parse a line break
   *
   *  defined: "\n" | "\r" | "\r\n"
   *  
   *  @return integer      
   */
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
  
  /**
   *  true if the next char(s) is a line break
   *  
   *  @return boolean
   */
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
  
  /**
   *  parse a long description
   *  
   *  defined: line , { line | emptyline }*
   *  
   *  #return integer      
   */
  protected function parseLongDesc(){
  
    $ret_count = 0;
  
    try{
    
      while(true){
      
        $this->bookmarkChar();
        $ret_count += $this->parseLine();
        $ret_count += $this->parseEmptyLines();
        
      }//while
      
    }catch(\DomainException $e){
    
      $this->restoreChar();
    
    }//try/catch
  
    $this->setLines('long_desc');
    
    return $ret_count;
  
  }//method
  
  /**
   *  parse the short description
   *  
   *  the short desc is usually the first line of a multiline docblock
   *  
   *  defined: line , { emptyline }+            
   *
   *  @return integer   
   */
  protected function parseShortDesc(){
  
    $ret_count = 0;
  
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
  
  /**
   *  parse spaces
   *  
   *  defined:  " " | "\t"      
   *
   *  return  integer   
   */
  protected function parseSpace(){
  
    $ret_count = 0;
    
    $char = $this->currentChar();
    while(($char === ' ') || ($char === "\t")){
    
      $char = $this->nextChar();
      $ret_count++;
    
    }//while
    
    return $ret_count;
  
  }//method
  
  /**
   *  parse whitespace
   *  
   *  whitespace is any space including linebreaks      
   *
   *  @return integer   
   */
  protected function parseWhitespace(){
  
    $ret_count = 0;
  
    while($this->isWhitespace()){
      
      $this->nextChar();
      $ret_count++;
      
    }//while
  
    return $ret_count;
  
  }//method

  /**
   *  true if the current char is a whitespace character
   *  
   *  @return boolean
   */
  protected function isWhitespace(){ return ctype_space($this->currentChar()); }//method

  /**
   *  true if the current char matches $expected char
   *  
   *  @param  string  $expected_char
   *  @param  integer $offset if you want to see another char besides the current char
   *  @return boolean
   */
  protected function isChar($expected_char,$offset = 0){
  
    $ret_bool = true;
    $char = $this->peekChar($offset);
  
    if($char !== $expected_char){
  
      $ret_bool = false;
    
    }//if
    
    return $ret_bool;
  
  }//method
  
  /**
   *  throw a parse exception
   *  
   *  this just takes the $msg and adds more info and throws a DomainException
   *  
   *  @throws \DomainException            
   *  @param  string  $msg
   */
  protected function throwParseException($msg){
  
    $msg .= '. Remaining DocBlock: '.mb_substr($this->docblock,$this->docblock_index);
  
    throw new \DomainException($msg);
  
  }//method
  
  /**
   *  set any lines in the $line_buffer into the $key
   *  
   *  @param  string  $key
   */
  protected function setLines($key){
  
    // canary...
    if(empty($this->line_buffer)){ return; }//if
  
    $str = trim(join(PHP_EOL,$this->line_buffer));
    
    if(isset($this->field_map[$key])){
    
      $this->field_map[$key] = (array)$this->field_map[$key];
      $this->field_map[$key][] = $str;
    
    }else{
    
      $this->field_map[$key] = $str;
    
    }//if/else
  
    $this->line_buffer = array();
    
  }//method

}//method
