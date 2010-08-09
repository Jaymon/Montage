<?php

/**
 *  hold lots of html helper functions
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-23-10
 *  @package montage
 *  @subpackage help  
 ******************************************************************************/
class montage_html {

  /**
   *  return an <a href...>$body</a> tag
   *  
   *  @param  string  $val  the value to be "cleansed"
   *  @return string      
   */
  static function getLink($url,$body,$attr_map = array()){
  
    $attr_map['href'] = $url;
    return self::getTag('a',$body,$attr_map);
  
  }//method
  
  /**
   *  return a generic <$tagname $attr_map>$body</$tag_name> tag
   *  
   *  @param  string  $tag_name an html tag_name
   *  @param  string  $body the body that the $tag_name tag will wrap
   *  @param  array $attr_map the atrributes that the $tag_name tag will have      
   *  @return string
   */
  static function getTag($tag_name,$body,$attr_map = array()){
  
    // canary...
    if(empty($tag_name)){ return $body; }//if
  
    $tag_name = trim($tag_name,'<>');
  
    $format_str = '<%s %s>%s</%s>';
    $format_vars = array();
    
    $format_vars[] = $tag_name;
    
    if(!empty($attr_map)){
      $format_str = '<%s>%s</%s>';
      $format_vars[] = self::getAttributes($attr_map);
    }//if
  
    $format_vars[] = $body;
    $format_vars[] = $tag_name;
  
    return vsprintf($format_str,$format_vars);
  
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
  static function getAutoLinked($input,$tag_callback = null,$body_callback = null){
  
    // canary...
    if(empty($input)){ return $input; }//if
    
    $cb = new montage_html_cb();
    
    $param_map = array();
    $param_map['tag_callback'] = $tag_callback;
    $param_map['body_callback'] = $body_callback;
    
    return self::getBetweenTags($input,array($cb,'getAutoLinked'),$param_map);
  
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
  static function getBetweenTags($input,$callback,$param_map = array()){
  
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
                $plain_text = call_user_func($callback,$plain_text,$param_map);
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
      $plain_text = call_user_func($callback,$plain_text,$param_map);
      $input = mb_substr($input,0,$end_tag) // the first part of input
        .$plain_text; // the last part
    }//if
  
    return $input;
  
  }//method
  
  /**
   *  output all the attributes in a nicely formatted string
   *     
   *  @return string
   */       
  static function getAttributes($attr_map){
    
    // canary...
    if(empty($attr_map)){ return ''; }//if
  
    $ret_str = '';
    foreach($attr_map as $attr_name => $attr_val){
      
      if(is_array($attr_val) || is_object($attr_val)){
        $ret_str .= sprintf('%s="%s" ',$attr_name,json_encode($attr_val));
      }else{
        $ret_str .= sprintf('%s="%s" ',$attr_name,$attr_val);
      }//if/else
      
    }//foreach
    
    return trim($ret_str);
    
  }//method

}//class

/**
 *  this is a private class that should only be used by montage_html
 *
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 4-21-10
 *  @package  montage_html
 */
class montage_html_cb extends montage_base {
  
  /**
   *  finds explicite urls (eg, scheme://domain/path) and can return a match object like:
   *  array(0 => full match, 1 => first char match, 2 => second char til end) 
   */        
  const REGEX_URL_EXPLICIT = '#(?<=[^a-zA-Z0-9]|^)([a-zA-Z0-9]+://[^/\s]\S+)#u';
  
  /**
   *  hyperlink urls that are not linked
   *
   *  @param  array $input  a string of non-html text
   *  @return string  the replace string
   */
  function getAutoLinked($input,$param_map){
    
    // canary...
    if(empty($input) || ctype_space($input)){ return $input; }//if
  
    $this->setField('getAutoLinked::$body_callback',$param_map['body_callback']);
    $this->setField('getAutoLinked::$tag_callback',$param_map['tag_callback']);
  
    return preg_replace_callback(self::REGEX_URL_EXPLICIT,array($this,'handleAutolink'),$input);
  
  }//method
  
  /**
   *  called from {@link getAutoLinked()} and only that function
   *  
   *  this is where the actual substitution takes place
   *  
   *  @param  array $match  the match object given from preg_replace_callback   
   *  @return string 
   */
  private function handleAutoLink($match){
  
    list($actual_url,$last_char) = $this->getActualUrl($match[0]);
    
    $body_callback = $this->getField('getAutoLinked::$body_callback',null);
    
    $body_url = $actual_url;
    if(!empty($body_callback)){ $body_url = call_user_func($body_callback,$actual_url); }//if
    
    $tag_callback = array($this,'getDefaultLinkTag');
    if($this->hasField('getAutoLinked::$tag_callback')){
      $tag_callback = $this->getField('getAutoLinked::$tag_callback',$tag_callback);
    }//if
    
    list($start_tag,$stop_tag) = call_user_func($tag_callback,$actual_url);
    
    return sprintf('%s%s%s%s',$start_tag,$body_url,$stop_tag,$last_char);
      
  }//method
  
  /**
   *  if a $tag_callback isn't specified in {@link montage_html::getAutoLinked()} then this
   *  callback will be used
   *  
   *  @param  string  $url
   *  @return array array($start_tag,$stop_tag);
   */
  private function getDefaultLinkTag($url){
  
    $attr_map = array();
    $attr_map['class'] = 'autolinked';
    $attr_map['title'] = $url;
    $attr_map['href'] = $url;
    
    return array(sprintf('<a %s>',montage_html::getAttributes($attr_map)),'</a>');
  
  }//method
  
  /**
   *  checks the found url to find the actual url
   *  
   *  the actual found url is a url free of ending punctuation but keeping stuff like parens if the opening paren is
   *  in the url         
   *  
   *  @since  4-27-09   
   *  @param  array $url  the url to check   
   *  @return array array($url,$last_char)
   */
  private function getActualUrl($url){
  
    $ret_str = $url;
    $ret_last = '';
    $strip_chars = 0;
  
    $possible_last_chars = ')]}"\'.,!';
    $opposite_last_chars = '([{"\'';
  
    $url_len = mb_strlen($url);
    for($i = ($url_len - 1); $i >= 0 ;$i--){
    
      // is the last char a possible last char?
      $char_i = mb_strpos($possible_last_chars,$url[$i]);
      if($char_i !== false){
      
        // check if the last char has a an opposite...
        $opposite_char_i = isset($opposite_last_chars[$char_i]) 
          ? mb_strpos(mb_substr($url,0,($strip_chars - 1)),$opposite_last_chars[$char_i])
          : false;
        
        if($opposite_char_i !== false){
          
          // we found an opposite match, we don't need to look further or strip anymore...
          break;
          
        }else{
          
          $strip_chars--;
          
        }//if/else
      
      }else{
        
        // we found a char that can't be a last char, so we're done...
        break;
        
      }//if/else

    }//for

    if($strip_chars < 0){

      $ret_str = mb_substr($url,0,$strip_chars);
      $ret_last = mb_substr($url,$url_len + $strip_chars);
      
    }//if
  
    return array($ret_str,$ret_last);
  
  }//method

}//class   
