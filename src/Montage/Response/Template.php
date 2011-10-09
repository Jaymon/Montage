<?php

/**
 *  render the template 
 *   
 *  @version 0.3
 *  @author Jay Marcyes
 *  @since 2-22-10
 *  @package montage
 *  @subpackage template 
 ******************************************************************************/
namespace Montage\Response;

use Montage\Field\Field;
use Montage\Escape;
use Path;
use Montage\Dependency\Dependable;

class Template extends Field implements Dependable {

  /**
   *  the actual template file that will be used to render the view
   *  @var  string
   */
  protected $template = '';
  
  /**
   *  the default template extension
   *
   *  most likely, you won't ever need to touch this! But if you do need to change it:
   *  this is appended on the end (see {@link normalizePath()}), so you must include the
   *  period of an extension (eg, .ext not just ext), this will allow you to do something 
   *  like _template.php and have it still work correctly.
   *      
   *  @var  string
   */
  protected $template_postfix = '.php';
  
  /**
   *  hold all the paths this instance will use to find the template file
   *  
   *  @var  array
   */
  protected $path_list = array();
  
  /**
   *  holds the container instance
   *
   *  @var  \Montage\Dependency\Containable
   */
  protected $container = null;
  
  public function setContainer(\Montage\Dependency\Containable $container){ $this->container = $container; }//method
  public function getContainer(){ return $this->container; }//method
  
  /**
   *  the actual template name (eg, layout.php or page_tmpl.php
   */        
  public function setTemplate($val){ return $this->template = $val; }//method
  public function getTemplate(){ return $this->template; }//method
  public function hasTemplate(){ return !empty($this->template); }//method
  
  /**
   *  this is the method called automatically in the Framework, override if you want to change defaults
   *
   *  @return mixed
   */
  public function handle(){
    
    $template = $this->getTemplate();
    return $this->out($template);
    
  }//method
  
  /**
   *  if the class should check more than one place for the template, add the alternate
   *  paths using this, setTemplate will then go through all the paths checking
   *  to see if the file exists in any path
   *  
   *  we render the template files using the include_paths so you can set other template files
   *  in the actual template and include them without having to actually know the path, it's "all
   *  for your convenience" programming
   *  
   *  @param  string  $path the template path to add to the include paths
   */
  public function addPath($path){
  
    // canary...
    if(empty($path)){
      throw new \UnexpectedValueException('$path is empty');
    }//if
    if(!($path instanceof Path)){
      $path = new Path($path);
    }//if
  
    $this->path_list[] = $path;
    return $this;
  
  }//method
  
  public function addPaths(array $path_list){
  
    foreach($path_list as $path){ $this->addPath($path); }//foreach
    
    return $this;
  
  }//method
  
  /**
   *  return the output of a template
   *  
   *  if you want to just echo the template to the screen, use {@link out()}
   *  
   *  @param  string  $template the template name
   *  @param  array $field_map  if you have any specific fields to pass to the template   
   *  @return boolean
   */
  public function render($template,array $field_map = array()){
    
    $ret_str = '';
    
    // capture the template into a string...
    // NOTE 5-1-08: if the page just comes out blank for some reason, there might 
    //  be an unsupported character in the template...
    ob_start();
    
      try{
      
        $this->out($template,$field_map);
        $ret_str = ob_get_contents(); // Get the contents of the buffer
        
      }catch(\Exception $e){
      
        ob_end_clean();
        throw $e;
      
      }//try/catch
        
    // End buffering and discard
    ob_end_clean();
    
    return $ret_str;
    
  }//method
  
  /**
   *  echo to the screen the output of a template
   *  
   *  if you want to return the template output, use {@link render()}
   *  
   *  @param  string  $template the template name
   *  @param  array $field_map  if you have any specific fields to pass to the template   
   *  @return boolean
   */
  public function out($template,array $field_map = array()){
  
    // canary...
    if(empty($template)){ throw new \InvalidArgumentException('$template was empty'); }//if
  
    $ret_str = '';
    $template_path = $this->normalizePath($template);
    $orig_field_map = array();
    
    // if there are passed in fields than add those to the previous fields...
    if(!empty($field_map)){
    
      $orig_field_map = $this->getFields();
      $field_map = array_merge($orig_field_map,$field_map);
      $this->setFields($field_map);
      
    }//if
    
    include($template_path);
    
    // restore the original fields...
    if(!empty($field_map)){
    
      $this->setFields($orig_field_map);
    
    }//if
    
    return true;
    
  }//method
  
  /**
   *  find the first full path by appending the $template onto each of the paths in {@link $path_list}
   *
   *  @var  string  $template   
   *  @return string  the full template path
   */
  protected function normalizePath($template){
  
    // canary, template might already be a full path...
    if(is_file($template)){ return $template; }//if
  
    $ret_path = null;
    $template = $this->normalizeTemplateName($template);
  
    foreach($this->path_list as $dir){
    
      $ret_path = new Path($dir,$template);
      if($ret_path->exists()){
        break;
      }else{
        $ret_path = null;
      }//if/else
    
    }//foreach
    
    if(empty($ret_path)){
    
      throw new \UnexpectedValueException(
        sprintf(
          'Could not find template [%s] in paths: [%s]',
          $template,
          join(',',$this->path_list)
        )
      );
    
    }//if
  
    return $ret_path;
  
  }//method
  
  /**
   *  normalize the template name by making sure it ends in {@link $template_ext}
   *  
   *  @param  string  $template the template name
   *  @return string  the normalized template
   */
  protected function normalizeTemplateName($template){
  
    if(!preg_match(sprintf('#%s$#i',preg_quote($this->template_postfix)),$template)){
    
      $template .= $this->template_postfix;
    
    }//if
  
    return $template;
  
  }//method

}//class     
