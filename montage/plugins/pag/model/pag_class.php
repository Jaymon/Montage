<?php

/**
 *  handle tag parsing 
 *
 *  @todo
 *    - should get() take a limit (ie, $this->get($tag_list,$limit) so it will stop
 *      after we get the a certain number of tags?  
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 8-8-10
 *  @package parse
 ******************************************************************************/
class pag {

  /**
   *  set the text that will be parsed
   *     
   *  @var  string
   */        
  protected $text = '';

  /**
   *  the starting delim
   *  
   *  this allows flexibility in what kind of tags you can parse, for example, by setting
   *  this to [ and $delim_stop to ] you can parse shortags
   *  
   *  @see  setDelimStart(), setDelims()               
   *  @var  string
   */
  protected $delim_start = '<';
  
  /**
   *  the stop delim
   *  
   *  @see  setDelimStop(), setDelims()               
   *  @var  string
   */
  protected $delim_stop = '>';

  /**
   *  @param  string  $text the text that will be parsed for tags
   */
  public final function __construct($text = ''){
  
    $this->setText($text);
  
  }//method

  /**
   *  set the text this instance will use for {@link get()} calls
   *  
   *  @param  string  $text
   */
  public function setText($text){
    
    // we need to make sure encoding is alright...
    ///out::e(mb_detect_encoding($text));
    ///out::e(mb_internal_encoding());
    ///$text = iconv(mb_detect_encoding($text),'ASCII//TRANSLIT',$text);
    
    $this->text = $text;
    
  }//method
  
  public function setDelims($delim_start,$delim_stop){ 
    $this->setDelimStart($delim_start);
    $this->setDelimStop($delim_stop);
  }//method
  public function setDelimStart($delim){ $this->delim_start = $delim; }//method
  public function setDelimStop($delim){ $this->delim_stop = $delim; }//method

  /**
   *  find and return tags
   *  
   *  yeah, yeah, this uses regular expressions to parse html, yeah, yeah, that's bad,
   *  it should never be done, world's going to end. I don't care! Sometimes
   *  you need a simple solution that doesn't fail and doesn't format, and accounts
   *  for stupid things like <a class="..." class="..."> without getting mad. Yes, this
   *  class will fail on a lot of html, but it will not fail on a lot also, and so far
   *  it's seemed to not fail more than fail      
   *  
   *  lest you think I'm not informed...   
   *  @link http://stackoverflow.com/questions/1732348/regex-match-open-tags-except-xhtml-self-contained-tags/1732454#1732454
   *  @link http://www.codinghorror.com/blog/2009/11/parsing-html-the-cthulhu-way.html   
   *  @link http://oubliette.alpha-geek.com/2004/01/12/bring_me_your_regexs_i_will_create_html_to_break_them   
   *   
   *  @param  string|array  $tag_list one or more tags (eg, "a" or array("a","img"))
   *  @return array a list of {@link parse_tag_info} isntances
   */
  public function get($tag_list){
  
    // canary...
    if(empty($tag_list)){ throw new InvalidArgumentException('No tags were passed in (eg, "a")'); }//if
    if(empty($this->text)){
      throw new UnexpectedValueException('Use setText() to set the text to be parsed');
    }//if
    
    // canary, assure we have an array...
    $tag_list = (array)$tag_list;
    
    // escape all the tags...
    $tag_list = array_map('preg_quote',$tag_list,array_fill(0,count($tag_list),'#'));
    
    $ret_tag_list = array();
    $delim_start = preg_quote($this->delim_start,'#');
    $delim_stop = preg_quote($this->delim_stop,'#');
    
    foreach($tag_list as $tag_name){
    
      $current_tag_list = array();
      $matched = array();
      $tag_stack = array();
      $text = $this->text;
      $offset = 0;
  
      $regex = sprintf(
        '#%s\s*(/)?\s*(%s)(?=\s|/|%s)[^%s]*(/)?%s#i',
        $delim_start,
        $tag_name,
        $delim_stop,
        $delim_stop,
        $delim_stop
      );
      
      while(preg_match($regex,$text,$matched,PREG_OFFSET_CAPTURE)){
      
        if(!empty($matched[0][0])){
        
          // check for / before tag_name to decide if this is an opening or closing tag...
          if(empty($matched[1][0])){
          
            // make sure this isn't a self closing tag...
            if(empty($matched[3][0])){
              
              // it's an opening tag so push it onto the stack...
              $tag_info = new pag_tag($matched[2][0]);
              $tag_info->setStart($matched[0][0]);
              $tag_info->setOffset($offset + $matched[0][1]);
              $tag_info->setAttr($this->getAttr($matched[0][0]));
              $tag_stack[] = $tag_info;
              
            }else{
              
              // it's a self-closing tag, so just put it into the return...
              $tag_info = new pag_tag($matched[2][0]);
              $tag_info->setRaw($matched[0][0]);
              $tag_info->setOffset($offset + $matched[0][1]);
              $tag_info->setFull($matched[0][0]);
              $tag_info->setAttr($this->getAttr($matched[0][0]));
              $current_tag_list[] = $tag_info;
              
            }//if/else
            
          }else{
          
            // it's a closing tag so pop the opening tag off the stack and record the value...
          
            $tag_info = array_pop($tag_stack);
          
            if(!empty($tag_info)){
            
              $body_start = $tag_info->getBodyOffset();
              $body_stop = $offset + $matched[0][1];
              $body_length = $body_stop - $body_start;
              
              $tag_info->setBodyLength($body_length);
              $tag_info->setBody(mb_substr($this->text,$body_start,$body_length));
              $tag_info->setStop($matched[0][0]);

              $current_tag_list[] = $tag_info;
            
            }//if
  
          }//if/else
        
          // move on through the text...
          $text_start = $matched[0][1] + mb_strlen($matched[0][0]);
          $offset += $text_start;
          $text = mb_substr($text,$text_start);
          
        }//if
      
      }//while
      
      // clear all the leftovers...
      $ret_tag_list = array_merge($ret_tag_list,$current_tag_list,$tag_stack);
      
    }//foreach
    
    return $ret_tag_list;
  
  }//method
  
  /**
   *  return the first found tag
   *  
   *  this is just a quick way to do a common thing
   *  
   *  @since  8-15-10   
   *  @param  string  $tag  the tag name
   *  @return pag_tag
   */
  public function getOne($tag){
  
    $list = $this->get($tag);
    return empty($list) ? null : $list[0];
  
  }//method
  
  protected function getAttr($text){
  
    // sanity checking...
    if(empty($text)){ return array(); }//if
    
    $ret_map = array();
    $matched = array();
    
    $regex_list = array();
    $regex_list[] = '#(?<=\s)(\w+)\s*=\s*"(.*?)"#siu'; // match: attr="this is the value to be returned"
    $regex_list[] = '#(?<=\s)(\w+)\s*=\s*\'(.*?)\'#siu'; // match: attr='this is the value to be returned'
    $regex_list[] = '#(?<=\s)(\w+)\s*=([^\'"][^\s>]*)#iu'; // match: attr=value

    foreach($regex_list as $regex){
      if(preg_match_all($regex,$text,$matched)){
        foreach($matched[0] as $key => $val){
          // concatenate if this attribute has been seen before (eg, class="..." class="...")...
          $attr = mb_strtolower($matched[1][$key]);
          if(isset($ret_map[$attr])){
            $ret_map[$attr] .= sprintf(' %s',$matched[2][$key]);
          }else{
            $ret_map[$attr] = $matched[2][$key];
          }//if/else
        }//foreach
      }//if
    }//foreach
    
    return $ret_map;
  
  }//method

}//class
