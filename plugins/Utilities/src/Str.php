<?php

/**
 *  hold lots of text helper methods
 *  
 *  @version 0.4
 *  @author Jay Marcyes
 *  @since 2-23-10
 *  @package Utilities
 ******************************************************************************/
class Str implements \ArrayAccess, \IteratorAggregate {

  /**
   *  hold the internal string
   *  
   *  @var  string      
   */
  protected $str = '';
  
  /**
   *  holds the url regex that is used to find/replace urls in bodies of text
   *
   *  @link http://daringfireball.net/2010/07/improved_regex_for_matching_urls
   *  @var  string      
   */
  protected $url_regex = '';
  
  /**
   *  holds temporary values for some of the methods
   *  
   *  @see  linkify()      
   *  @var  array
   */
  protected $field_map = array();

  /**
   *  create class instance
   *
   *  @param  string|array  $str  if array it will join it by a space
   */
  public function __construct($str){
  
    // canary...
    if(empty($str)){ throw new \InvalidArgumentException('$str was empty'); }//if
  
    $args = func_get_args();
    $this->str = $this->build($args);
    
    $this->url_regex = '@\b
      (                           # Capture 1: entire matched URL
        (?:
          [a-z][\w-]+:                # URL protocol and colon
          (?:
            /{1,3}                        # 1-3 slashes
            |                             #   or
            [a-z0-9%]                     # Single letter or digit or %
                                          # (Trying not to match e.g. "URI::Escape")
          )
          |                           #   or
          www\d{0,3}[.]               # "www.", "www1.", "www2." … "www999."
          |                           #   or
          [a-z0-9.\-]+[.][a-z]{2,4}/  # looks like domain name followed by a slash
        )
        (?:                           # One or more:
          [^\s()<>]+                      # Run of non-space, non-()<>
          |                               #   or
          \(([^\s()<>]+|(\([^\s()<>]+\)))*\)  # balanced parens, up to 2 levels
        )+
        (?:                           # End with:
          \(([^\s()<>]+|(\([^\s()<>]+\)))*\)  # balanced parens, up to 2 levels
          |                                   #   or
          [^\s`!()\[\]{};:\'".,<>?«»“”‘’]        # not a space or one of these punct chars
        )
      )@xi';
  
  }//method
  
  /**
   *  return the internal raw string
   *  
   *  @return string      
   */
  public function __toString(){ return $this->str; }//method
  
  /**
   *  since you can't do things like [0,-4] on a string this is the next best thing
   *  
   *  this allows you to get as close as possible to Python's string handling, and it
   *  is a little different than substr because if just an offset is given it will just
   *  return one character and not the entire string from that offset forward      
   *
   *  @example     
   *    $this(0,-5); // cut off last 5 characters
   *    $this(-3); // get third to last character
   *    $this(-3,null) // get from 3 to last char until end of string                     
   *
   *  @param  integer $offset where to start on the string
   *  @param  integer $length how big you want the string to be
   *  @return self  
   */
  public function __invoke($offset,$length = 1){
  
    $ret_str = '';
    
    if(empty($length)){
    
      $ret_str = mb_substr($this->str,$offset);
    
    }else{
    
      $ret_str = mb_substr($this->str,$offset,$length);
    
    }//if/else
  
    return $this->getInstance($ret_str);
  
  }//method

  /**
   *  turns $val into something that could be used as a path bit
   *  
   *  basically, take some $val and turn it into a path bit
   *  
   *  @example
   *    $val = 'This is a String WITH spaces and a /';
   *    self::convert($val); // -> 'this_string_spaces'
   *      
   *  @since  2-4-10
   *  @param  integer $char_limit max chars $input can be
   *  @param  string  $space_delim  what you want to use for spaces (usually dash or underscore)            
   *  @return self
   */
  public function pathify($char_limit = 0,$space_delim = '_'){
  
    $ret_str = $this->str;
  
    // get rid of whitespace fat...
    $ret_str = trim($ret_str);
    
    // replace anything that isn't a space or word char with nothing...
    $ret_str = preg_replace('/[^\w \-]/','',$ret_str);
  
    // impose a character limit if there is one...
    if($char_limit > 0){
    
      // this will lowercase everything and return an array of non-stop words...
      $ret_str = $this->getInstance($this->killStopWords());
      $ret_str = $ret_str->subWord(0,$char_limit);
      
    }else{
    
      // this will lowercase everything and return an array of non-stop words...
      $ret_str = $this->getInstance(join($space_delim,$this->killStopWords()));
    
    }//if/else
    
    return $ret_str;
  
  }//method

  /**
   *  return true if a string is binary
   *   
   *  this method is a cross between http://bytes.com/topic/php/answers/432633-how-tell-if-file-binary
   *  and this: http://groups.google.co.uk/group/comp.lang.php/msg/144637f2a611020c?dmode=source
   *  but I'm still not completely satisfied that it is 100% accurate, though it seems to be
   *  accurate for my purposes.
   *  
   *  @return boolean true if binary, false if not
   */
  public function isBinary(){
  
    $val = $this->str;
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
   *  true if string is a url
   *  
   *  @param  string  $val
   *  @return true if $val is a url, false otherwise
   */
  public function isUrl($val){ return preg_match('#^[a-zA-Z]+://\S{3,}#',$this->str) ? true : false; }//method
  
  /**
   *  given a url: http://www.example.com/, this function would return example.com, 
   *  
   *  @since  8-15-10   
   *  @return string  empty string if any problems are encounted or it can't find the host.
   */
  public function getHost(){
  
    // canary...
    if(!$this->isUrl()){ return ''; }//if
  
    $ret_str = parse_url($this->str,PHP_URL_HOST);
  
    // get rid of the www if it exists, otherwise return the whole base url...
    $ret_str = preg_replace('/^www\./i','',$ret_str);
    
    return $ret_str;
  
  }//method

  /**
   *  return a safe value for $val that is suitable for display in stuff like the value attribute 
   *  
   *  @return self
   */
  public function getSafe(){
    
    return $this->getInstance(
      htmlspecialchars(
        $this->str,
        ENT_COMPAT /* | ENT_SUBSTITUTE | ENT_HTML5 ar php>=5.4 */,
        mb_internal_encoding(),
        false
      )
    );
    
  }//method
  
  /**
   *  perform a word safe substring operation that works exactly like php's built-in
   *  substr function.   
   *  
   *  a word safe substring is a str that doesn't cut off in the middle of a word, 
   *  so this function will make sure it breaks on a word break, this function keeps 
   *  the same pos/neg start and len functionality of the builtin substr function, 
   *  it just guarantees not to break in the middle of a word which means the returned
   *  string could be slightly less than the desired length, but never longer   
   *  
   *  @param  string  $str  the text to substring
   *  @param  integer $start  where to start, if negative start that many chars from the end
   *  @param  integer $len  where to end, if neg, end that many chars from end of $str            
   *  @return self
   */
  public function subWord($start,$len = 0){
  
    $str = $this->str;
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
    
    return $this->getInstance($ret_str);
  
  }//method
  
  /**
     *  leverage substr() to limit the length of a string, but also put ellipses 
     *  at the end if length is over limit 
     *  
     *  @param  integer $start  where to start, if negative start that many chars from the end
     *  @param  integer $len  where to end, if neg, end that many chars from end of $str            
     *  @return string
     */
  public function getExcerpt($start,$len){
  
    // canary...
    if(empty($str)){ return ''; }//if
    
    $orig_size = mb_strlen($this->str);
    $ret_str = $this->subWord($start,$len);
    
    if(mb_strlen($ret_str) < $orig_size){
      $ret_str .= '…'; ///'...';
    }//if
    
    return $ret_str;
      
  }//method
  
  /**
   *  strip the links from $val, this will make it easier to do things with certain input
   *  
   *  based off this regex url: http://daringfireball.net/2009/11/liberal_regex_for_matching_urls
   *    \b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))   
   *  7-28-10 - updated to this regex: http://daringfireball.net/2010/07/improved_regex_for_matching_urls    
   *
   *  @return self      
   */
  public function killUrls(){
  
    $ret_str = preg_replace($this->url_regex,$this->str);
    return $this->getInstance($this->str);
  
  }//method
  
  /**
   *  removes certain stop words from the string
   *  
   *  @since  2-4-10
   *  @return array a word list with stop words removed
   */
  public function killStopWords(){
  
    $str = $this->getInstance(mb_strtolower($this->str));
    $words_list = $str->getWords();
  
    $stop_words_list = array('i', 'me', 'my', 'myself', 'we', 'our', 'ours', 'ourselves', 
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
 
    return array_diff($words_list, $stop_words_list);
  
  }//method
  
  /**
   *  strip the cdata from a string
   *
   *  @since  5-9-10   
   *  @return self  without CDATA tags   
   */
  public function killCdata(){
  
    // this one works but requires the cdata to be at the start and end of the string...
    ///$ret_str = preg_replace("/^<!\[CDATA\[(.*?)\]\]>$/siu","\\1",$input);
    
    $ret_str = preg_replace('/<!\[CDATA\[(.*?)\]\]>/si','\1',$this->str);
    
    return $this->getInstance($ret_str);
  
  }//method
  
  /**
   *  strip all whitespace from the string
   *
   *` @since  5-9-10   
   *  @return self
   */
  public function killSpaces(){
    return $this->getInstance(preg_replace('/\s+/','',$this->str));
  }//method
  
  /**
   *  strip all newlines from a string
   *
   *  NOTE: the reason str_replace isn't used is because I don't want multiple newlines to become 2 spaces.
   *  
   *  @since  5-9-10
   *  @return self  $input without newlines
   */
  public function killNewlines(){
    return $this->getInstance(preg_replace('#[\r\n]+#',' ',$this->str));
  }//method
  
  /**#@+
   *  Required definition of interface ArrayAccess
   *  @link http://www.php.net/manual/en/class.arrayaccess.php   
   */
  /**
   *  Set a value given it's key e.g. $A['title'] = 'foo';
   */
  public function offsetSet($name,$val){
    throw new \BadMethodCallException(sprintf('%s instances are read-only',__CLASS__));
  }//method
  /**
   *  Return a value given it's key e.g. echo $A['title'];
   */
  public function offsetGet($name){
    
    return isset($this->str[$name]) ? $this->str[$name] : '';
    
  }//method
  /**
   *  Unset a value by it's key e.g. unset($A['title']);
   */
  public function offsetUnset($name){
    throw new \BadMethodCallException(sprintf('%s instances are read-only',__CLASS__));
  }//method
  /**
   *  Check value exists, given it's key e.g. isset($A['title'])
   */
  public function offsetExists($name){ return isset($this->str[$name]); }//method
  /**#@-*/

  /**
   *  reuired method definition for IteratorAggregate
   *
   *  @return ArrayIterator allows this class to be iteratable by going throught he main array
   */
  public function getIterator(){ return new ArrayIterator(str_split($this->str)); }//spl method
  
  /**
   *  get all the words of the string in an array
   *
   *  @since  11-3-11
   *  @return array      
   */
  public function getWords(){
  
    // these are all the punctuation characters that will be stripped...
    $punct = preg_quote('~`!@#$%^&*()_-+={}[]\\:|\'";<>,./?','#');
    
    $regex = sprintf(
      '#'
      .'(?:(?<=[^%s])[%s]+(?=\s|$))' // a punct char proceeded by a non-punct char and followed by a word break
      .'|'
      .'(?:(?<=\s|^)[%s]+(?=[^%s]))' // a puncT char proceeded by a word break and followed by a non-punct char
      .'#',
      $punct,$punct,$punct,$punct
    );
    
    $ret_str = preg_replace($regex,' ',$this->str);
  
    return preg_split('#\s+#u',$ret_str,-1,PREG_SPLIT_NO_EMPTY);
  
  }//method
  
  /**
   *  true if the string contains any or all of the $words
   *
   *  @since  11-3-11
   *  @param  string|array  $words
   *  @param  boolean $match_all  if true, all the $words have to be in the string   
   *  @return boolean         
   */
  public function contains($words,$match_all = false){
  
    // sanity...
    if(empty($words)){ return false; }//if
  
    $ret_bool = false;
    
    if($match_all){
    
      $ret_bool = true;
      $words = (array)$words;
      foreach($words as $word){
      
        list($word_regex) = $this->getWordRegex($word);
      
        if(!preg_match($word_regex,$this->str)){
        
          $ret_bool = false;
          break;
        
        }//if
      
      }//foreach
    
    }else{
    
      list($word_regex) = $this->getWordRegex($words);
    
      $ret_bool = preg_match($word_regex,$this->str) ? true : false;
    
    }//if/else
  
    return $ret_bool;
  
  }//method
  
  /**
   *  find any words matching $words in $input and wrap them in a CSS stylable span.
   *  the span has the class "found" so it can be styled in css
   *   
   *  @since  9-3-08  
   *  @param  string|array  the words to search for either in array form or space separated
   *  @return self  with any found words highlighted
   */
  public function highlight($words){
    
    // sanity...
    if(empty($words)){ return $this->getInstance((string)$this); }//if
  
    list($word_regex) = $this->getWordRegex($words);
  
    $callback = function($input) use ($word_regex) {
    
      return preg_replace($word_regex,'<span class="highlight">\0</span>',$input);
    
    };//closure
    
    return $this->getInstance($this->getBetweenTags($this->str,$callback));

  }//method
  
  /**
   *  find and return all the urls in the string
   *  
   *  @since  3-14-08   
   *  @return array all the found urls, empty array if none found         
   */
  public function getLinks(){
  
    $ret_list = array();
  
    $matched = array();
    if(preg_match_all($this->url_regex,$this->str,$matched)){
    
      $ret_list = $matched[1];
    
    }//if
  
    return $ret_list;
  
  }//method
  
  /**
   *  find any urls and wrap them in <a> tags
   *  
   *  @param  string  $input  the input that will be autolinked
   *  @param  callback  $tag_callback should accept a string and return an array with 2 indexes
   *                                  (ie, array('<a>','</a>') or array($start_tag,$stop_tag)).   
   *  @param  callback  $body_callback  if you don't want just the url to be the body, 
   *                                    you can specify a callback here, it should
   *                                    take a string and return a string, this will 
   *                                    allow you to do things like shorten the url
   *                                    or whatever, whatever is returned will be the 
   *                                    body of the generated <a> tag   
   *  @return string  $input with stray urls auto hyperlinked
   */
  public function linkify($tag_callback = null,$body_callback = null){
  
    // canary...
    if(empty($tag_callback)){ $tag_callback = array($this,'cbTag'); }//if
    
    $this->field_map['tag_callback'] = $tag_callback;
    $this->field_map['body_callback'] = $body_callback;
    
    $ret_str = $this->getBetweenTags($this->str,array($this,'cbBetweenLinkify'));
    
    // get rid of temp variables...
    unset($this->field_map['tag_callback'],$this->field_map['body_callback']);
    
    return $this->getInstance($ret_str);
    
  }//method
  
  /**
   *  hyperlink urls that are not linked
   *
   *  this is the callback that {@link linkify()} passes to {@link getBetweenTags()}
   *      
   *  @param  array $input  a string of non-html text
   *  @return string  the replace string
   */
  protected function cbBetweenLinkify($input){
  
    // canary...
    if(empty($input) || ctype_space($input)){ return $input; }//if
  
    return preg_replace_callback(
      $this->url_regex,
      array($this,'cbLink'),
      $input
    );
  
  }//method
  
  /**
   *  called from {@link cbBetween()} and only that function
   *  
   *  this is where the actual substitution takes place
   *  
   *  @param  array $match  the match object given from preg_replace_callback   
   *  @return string
   */
  protected function cbLink($match){
  
    $start_tag = $stop_tag = '';
    $body = $match[0];
    
    if(!empty($this->field_map['body_callback'])){
    
      $body = call_user_func($this->field_map['body_callback'],$body);
      
    }//if
    
    list($start_tag,$stop_tag) = call_user_func($this->field_map['tag_callback'],$match[0]);
    
    return $start_tag.$body.$stop_tag;
      
  }//method
  
  /**
   *  if a $tag_callback isn't specified in {@link linkify()} then this
   *  callback will be used
   *  
   *  @param  string  $url
   *  @return array array($start_tag,$stop_tag);
   */
  protected function cbTag($url){
      
    return array(
      sprintf(
        '<a class="linkify" href="%" title="%s">',
        $url,
        $url
      ),
      '</a>'
    );
  
  }//method
  
  /**
   *  run the $callback on the text of $input that is between html tags
   *  
   *  @since  4-27-09   
   *  @param  string  $input  the html/xml input to get between the tags
   *  @param  array|string  $callback a valid php callback, the callback should 
   *                                  accept a string and the $param_map and return a string
   *  @return string  $input with the callback ran on it
   */           
  protected function getBetweenTags($input,$callback){
  
    // canary...
    if(empty($input)){ return $input; }//if
    if(empty($callback)){ return $input; }//if
  
    $len = mb_strlen($input);
    $start_tag = $end_tag = 0;
    $in_tag = false;
    for($i = 0; $i < $len; $i++){
    
      switch($input[$i]){
      
        case '<':
          if(!$in_tag){
            $start_tag = $i;
            if(isset($input[$i+1])){
              if(!ctype_space($input[$i+1])){
                $in_tag = true;
              }//if
            }//if
            
            if($in_tag){
              
              $length = $start_tag - $end_tag;
              
              if($length > 0){
                
                $plain_text = mb_substr($input,$end_tag,$length);
                $plain_text = call_user_func($callback,$plain_text);
                $plain_text_len = mb_strlen($plain_text);
                if($plain_text_len > $length){
                
                  $input = mb_substr($input,0,$end_tag) // the first part of input
                    .$plain_text // the new plain text
                    .mb_substr($input,$end_tag + $length); // the last part of input
                  
                  // compensate for having a little longer input now...
                  $len_dif = $plain_text_len - $length;
                  $end_tag += $len_dif;
                  $start_tag += $len_dif;
                  $i += $len_dif;
                  $len = mb_strlen($input);
                
                }//if
              }//if
              
            }//if
          }//if
          break;
          
        case '>':
          if($in_tag){
            $end_tag = ($i + 1);
            $in_tag = false;
          }//if
          break;

      }//switch

    }//for
    
    $length = $len - $end_tag;
    if($length > 0){
      $plain_text = mb_substr($input,$end_tag,$length);
      $plain_text = call_user_func($callback,$plain_text);
      $input = mb_substr($input,0,$end_tag) // the first part of input
        .$plain_text; // the last part
    }//if
  
    return $input;
  
  }//method
  
  /**
   *  break up the words into a regex
   *
   *  @since  11-3-11
   *  @param  array|string  $words
   *  @return array array($regex,$word_list)         
   */        
  protected function getWordRegex($words){
  
    // split words by spaces if they are not already in a list...
    if(!is_array($words)){
    
      $words = $this->getInstance($words);
      $words = $words->getWords();
    
    }else{
    
      $words = array_filter($words);
      
    }//if/else
    
    $word_regex = sprintf('#(?:%s)#i',join('|',$words));
  
    return array($word_regex,$words);
  
  }//method
  
  /**
   *  wrap a new instance around $str
   *
   *  @since  11-3-11
   *  @param  string  $str   
   *  @return self
   */
  protected function getInstance($str){
  
    // canary...
    if($str instanceof self){ return $str; }//if
  
    $class_name = get_class($this);
    return new $class_name($str);
  
  }//method
  
  /**
   *  compile all the passed in $words into a string
   *
   *  @since  11-7-11
   *  @param  string|array  $words
   *  @return string         
   */
  protected function build($words){
  
    // canary...
    if(is_string($words)){ return $words; }//if
  
    $ret_str = '';
  
    $words = (array)$words;
    foreach($words as $word){
      
      if(is_array($words)){
      
        $ret_str[] = $this->build($word);
      
      }else{
      
        $ret_str[] = $word;
      
      }//if/else
    
    }//foreach
  
    return join(' ',$ret_str);
  
  }//method

}//class     
