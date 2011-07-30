<?php
/**
 *  allow introspection on a Controller
 *
 *  this is a really early version that will just allow a quick default help for CLI controllers
 *  
 *  @link http://stackoverflow.com/questions/2531085/are-there-any-php-docblock-parser-tools-available 
 *  @link http://www.phpriot.com/articles/reflection-api
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 7-29-11
 *  @package montage
 *  @subpackage Controller
 ******************************************************************************/
namespace Montage\Controller;

use ReflectionClass;

class ReflectionController extends ReflectionClass {

  public function __toString(){
  
    $ret_str = preg_replace('#Controller$#i','',$this->getShortName()).PHP_EOL;
  
    // now go through all the methods looking for "handle" methods...
  	$rmethod_list = $this->getMethods();
  	foreach($rmethod_list as $rmethod){
    
      // canary...
      if(!$rmethod->isPublic() || $rmethod->isStatic()){ continue; }//if
  
      $method_name = $rmethod->getName();
      $method_regex = '#^handle#i';
  
      if(preg_match($method_regex,$method_name)){
  
        $method_name = lcfirst(preg_replace($method_regex,'',$method_name));
      
        ///\out::b($method_name);
        $ret_str .= sprintf('  /%s',$method_name);
        
        if($doc_comment = $rmethod->getDocComment()){
        
          list($desc,$params) = $this->parseDocComment($doc_comment);
        
          $ret_str .= sprintf(' - %s',join(PHP_EOL,$desc));
          
        }//if
        
        $ret_str .= PHP_EOL.PHP_EOL;
        
      }//if
  
    }//foreach
  
    return $ret_str;
  
  }//method 
  
  protected function parseDocComment($doc_comment){
  
    // canary...
    if(empty($doc_comment)){ return array(); }//if
 
    $ret_desc = array();
    $ret_map = array();
 
    // blow up the comment...
    $lines = explode("\n",$doc_comment);
    $line = 0;
    
    // find short description...
    $str = '';
    
    foreach($lines as $line){
    
      $line = trim(ltrim(trim($line),'*/'));
      
      if(empty($line)){
      
        if(!empty($str)){
        
          if($str[0] == '@'){
          
            $ret_keys[] = $str;
          
          }else{
          
            $ret_desc[] = $str;
            $ret_desc[] = '';
          
          }//if/else
        
          $str = '';
        
        }//if
        
      }else{
      
        $str .= (empty($str) ? '' : ' ').$line;
        
      }//if
    
    }//foreach
 
    ///\out::e($ret_desc,$ret_keys);
  
    return array($ret_desc,$ret_keys);
  
  }//method

}//method
