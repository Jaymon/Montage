<?php

/**
 *  hold lots of text helper functions
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-23-10
 *  @package montage 
 *  @subpackage help  
 ******************************************************************************/
class montage_text {

  /**
   *  return true if a string is binary
   *   
   *  this method is a cross between http://bytes.com/topic/php/answers/432633-how-tell-if-file-binary
   *  and this: http://groups.google.co.uk/group/comp.lang.php/msg/144637f2a611020c?dmode=source
   *  but I'm still not completely satisfied that it is 100% accurate, though it seems to be
   *  accurate for my purposes.
   *  
   *  @param  string  $val  the val to check to see if contains binary characters
   *  @return boolean true if binary, false if not
   */
  static function isBinary($val){
  
    $val = (string)$val;
    $ret_bool = false;
    $not_printable_count = 0;
    for($i = 0, $max = strlen($val); $i < $max ;$i++){
      if(ord($val[$i]) === 0){ $ret_bool = true; break; }//if
      if(!ctype_print($val[$i])){
        if(++$not_printable_count > 5){ $ret_bool = true; break; }//if
      }//if 
    }//for
    
    return $ret_bool;
  
  }//method

  /**
   *  recursively strip all the slashes from a $val
   *  
   *  @param  mixed $val
   *  @return $val with all slashes stripped
   */
  static public function killSlashes($val){
  
    // canary...
    if(empty($val)){ return $val; }//if
    if(is_object($val)){ return $val; }//if
    
    if(is_array($val)){
      $val = array_map(array(__CLASS__,'killSlashes'),$val);
    }else{
      $val = stripslashes($val);
    }//if/else
    
    return $val;
    
  }//method

  /**
   *  true if $val is a url
   *  
   *  @param  string  $val
   *  @return true if $val is a url, false otherwise
   */
  static public function isUrl($val){
    // canary...
    if(empty($val)){ return false; }//if
    return preg_match('#^\w+://\S{3,}#',$val) ? true : false;
  }//method

  /**
   *  return a safe value for $val that is suitable for display in stuff like the value attribute 
   *  
   *  @param  string  $val  the value to be "cleansed"
   *  @return string      
   */
  static public function getSafe($val){ 
    return empty($val) ? '' : htmlspecialchars($val,ENT_COMPAT,MONTAGE_CHARSET,false);
  }//method
  
  /**
   *  perform a word safe substring operation that works exactly like php's built-in
   *  substr function.   
   *  
   *  a word safe substring is a str that doesn't cut off in the middle of a word, 
   *  so this function will make sure it breaks on a word break, this function keeps 
   *  the same pos/neg start and len functionality of the builtin substr function, 
   *  it just guarantees not to break in the middle of a word...
   *  
   *  @param  string  $str  the text to substring
   *  @param  integer $start  where to start, if negative start that many chars from the end
   *  @param  integer $len  where to end, if neg, end that many chars from end of $str            
   *  @return string
   */        
  static public function getWordSubStr($str,$start,$len = 0){
  
    // sanity...
    if(empty($str)){ return $str; }//if
    
    $ret_str = $str;
    $start = (int)$start;
    $len = (int)$len;
    $orig_len = $len;
    $orig_start = $start;
    $orig_str_len = mb_strlen($str);
    if(empty($len)){ $len = $orig_str_len; }//if
    
    // if start is negative, we want to start that many characters from the end of the string...
    if($start < 0){
      $start = $orig_str_len + $start;
      if($start < 0){ $start = 0; }//if
    }//if
    
    // if length is negative, we want to end that many characters from the end...
    if($len < 0){ $len = ($orig_str_len + $len) - $start; }//if
    
    if(($len > 0) && ($start >= 0)){
      
      ///out::e($len,$start);
      $len_start = $start + $len;
      
      // make sure start and len both end on whitespace...
      while(($len_start > $start) && isset($str[$len_start]) && ($str[$len_start] != ' ') && ($str[$len_start] != "\r") && ($str[$len_start] != "\t") && ($str[$len_start] != "\n")){
        $len--;
        $len_start--;
      }//if
      $len--; // compensate for the whitespace that was found
      while(isset($str[$start]) && ($str[$start] != ' ') && ($str[$start] != "\r") && ($str[$start] != "\t") && ($str[$start] != "\n")){
        $start--;
        $len++;
      }//if
      $start++;
      
      // if the string had no whitespace, cut it like normal in the middle of the word...
      if($len < 1){ $len = $orig_len; }//if
      if($start < 1){ $start = $orig_start; }//if
      
      $ret_str = mb_substr($str,$start,$len);
    
    }//if
    
    return $ret_str;
  
  }//method
  
  /**
     *  leverage substr() to limit the length of a string, but also put ellipses at the end if length is over limit 
     *  
     *  @param  string  $str  the text to substring
     *  @param  integer $start  where to start, if negative start that many chars from the end
     *  @param  integer $len  where to end, if neg, end that many chars from end of $str            
     *  @return string
     */
  static public function getExcerpt($str,$start,$len)
  {
      // canary...
      if(empty($str)){ return ''; }//if
      
      $orig_size = mb_strlen($str);
      $ret_str = self::getWordSubStr($str,$start,$len);
      
      if(mb_strlen($ret_str) < $orig_size){
        $ret_str .= '...';
      }//if
      
      return $ret_str;
      
  }//method
  
  /**
   *  strip the links from $val, this will make it easier to do things with certain input
   *  
   *  @param  string  $val
   *  @return string  the $val with any urls removed      
   */
  static public function killUrls($val){
  
    return empty($val)
      ? ''
      : preg_replace('#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#u','',$input);
  
  }//method
  
  /**
   *  turns $input into a url safe path
   *  
   *  @since  2-4-10   
   *  @param  string  $input
   *  @param  integer $char_limit max chars $input can be         
   *  @return string  url safe string
   */
  static public function getSafePath($input,$char_limit = 0){
  
    // canary...
    if(empty($input)){ return ''; }//if
  
    $ret_str = trim($input);
    $ret_str = preg_replace('/[^\w \-]/u','',$ret_str); // replace anything that isn't a space or word char with nothing
    $ret_str = mb_strtolower($ret_str);
    $ret_str = join('_',array_filter(self::killStopWords($ret_str)));
  
    // impose a character limit if there is one...
    if($char_limit > 0){
      $ret_str = mb_substr($ret_str,0,$char_limit);
      // make sure there isn't something dumb on the end like a "_", or just contains ___...
      $ret_str = preg_replace('/(?:^[_]+|[_]+)$/u','',$ret_str);
    }//if
    
    return $ret_str;
  
  }//method
  
  /**
   *  removes certain stop words from $word_list
   *  
   *  @since  2-4-10   
   *  @param  array|string  $word_list
   *  @return array $word_list with stop words removed
   */
  static public function killStopWords($words_list){
  
    // error checking...
    if(is_array($words_list)){
    
      // make sure all the words are trimmed...
      $words_list = array_map('trim',$words_list);
    
    }else{
    
      if(empty($words_list)){
        return array();
      }else{
        $words_list = preg_match('/\s+/u',$words_list);
      }//if/else
      
    }//if
  
    $stop_words = array('i', 'me', 'my', 'myself', 'we', 'our', 'ours', 'ourselves', 
      'you', 'your', 'yours', 'yourself', 'yourselves', 'he', 'him', 'his', 'himself', 
      'she', 'her', 'hers', 'herself', 'it', 'its', 'itself', 'they', 'them', 'their', 
      'theirs', 'themselves', 'what', 'which', 'who', 'whom', 'this', 'that', 'these', 
      'those', 'am', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 
      'had', 'having', 'do', 'does', 'did', 'doing', 'a', 'an', 'the', 'and', 'but', 
      'if', 'or', 'because', 'as', 'until', 'while', 'of', 'at', 'by', 'for', 'with', 
      'about', 'against', 'between', 'into', 'through', 'during', 'before', 'after', 
      'above', 'below', 'to', 'from', 'up', 'down', 'in', 'out', 'on', 'off', 'over', 
      'under', 'again', 'further', 'then', 'once', 'here', 'there', 'when', 'where', 
      'why', 'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other', 
      'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 
      'very'
    );
 
    return array_diff($words_list, $stop_words);
  
  }//method
  
  /**
   *  strip the cdata from a string
   *
   *  @since  5-9-10   
   *  @param  string  $input
   *  @return string  $input without CDATA tags   
   */
  static public function killCdata($input){
  
    // this one works but requires the cdata to be at the start and end of the string...
    ///$ret_str = preg_replace("/^<!\[CDATA\[(.*?)\]\]>$/siu","\\1",$input);
    
    $ret_str = preg_replace('/<!\[CDATA\[(.*?)\]\]>/siu','\1',$input);
    
    return $ret_str;
  
  }//method
  
  /**
   *  strip all whitespace from a string
   *
   *` @since  5-9-10   
   *  @param  string  $input
   *  @return string  $input without white space
   */
  static public function killSpaces($input){
    return preg_replace('/\s+/u','',$input);
  }//method
  
  /**
   *  strip all newlines from a string
   *
   *  NOTE: the reason str_replace isn't used is because I don't want multiple newlines to become 2 spaces.
   *  
   *  @since  5-9-10   
   *  @param  string  $input
   *  @return string  $input without newlines
   */
  static public function killNewlines($input){
    return preg_replace('/[\r\n]+/u',' ',$input);
  }//method

}//class     
