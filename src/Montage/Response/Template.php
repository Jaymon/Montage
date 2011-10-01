<?php

/**
 *  render the template 
 *   
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-22-10
 *  @package montage
 *  @subpackage template 
 ******************************************************************************/
namespace Montage\Response;

use Montage\Field\Field;
use Montage\Escape;
use Path;

class Template extends Field {

  /**
   *  this is the default value, it will capture the output of the template and return it as a string
   */     
  const OUT_STR = 1;
  /**
   *  if set, this will output the template to standard output
   */     
  const OUT_STD = 2;

  /**
   *  the actual template file that will be used to render the view
   *  @var  string
   */
  protected $template = '';
  
  /**
   *  the default template extension
   *
   *  @var  string   
   */
  protected $template_ext = '.php';
  
  /**
   *  hold all the paths this instance will use to find the template file
   *  
   *  @var  array
   */
  protected $path_list = array();
  
  /**
   *  the actual template name (eg, layout.php or page_tmpl.php
   */        
  public function setTemplate($val){ return $this->template = $val; }//method
  public function getTemplate(){ return $this->template; }//method
  public function hasTemplate(){ return !empty($this->template); }//method
  
  /**
   *  Open, parse, and return/output the template file.
   *     
   *  @param  integer $options  one or more of the class constants or'ed together (eg, OPTION_* | OPTION_*)
   *  @return mixed boolean if OUT_STD is set, string if OUT_STR is set   
   */        
  public function handle($options = self::OUT_STR){
    
    // canary...
    if(!$this->hasTemplate()){ throw new \RuntimeException('no template file specified'); }//if
    
    $ret_mix = '';
    $template = $this->getTemplate();

    // decide to return string or output to standard out, default is string...
    $is_ret_str = true;
    if($options & self::OUT_STD){
      $is_ret_str = false;
      $ret_mix = false;
    }//if/else if
    
    if($is_ret_str){
    
      // capture the template into a string...
      // NOTE 5-1-08: if the page just comes out blank for some reason, their might 
      //  be an unsupported character in the template, and even better are chances that it 
      //  is that dumbass windows quote symbol...
      ob_start();
        $this->render($template);
        $ret_mix = ob_get_contents(); // Get the contents of the buffer
      // End buffering and discard
      ob_end_clean();
    
    }else{
    
      $ret_mix = $this->render($template);
    
    }//if/else
    
    return $ret_mix;
    
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
   *  overloaded from parent so when in the template it will return a montage_escaped wrapped
   *  value, this is useful for making sure user submitted input is safe while not having to worry
   *  about it anywhere else but the view      
   *  
   *  @see  parent::getField()
   */
  public function escField($key,$default_val = null){
    
    $ret_mix = $default_val;
    
    if($this->existsField($key)){
      $ret_mix = new Escape($this->field_map[$key]);
    }//if
    
    return $ret_mix;
    
  }//method
  
  /**
   *  include a template
   *  
   *  this will usually be called inside a template
   *  
   *  @param  string  $template the template name
   *  @return boolean
   */
  protected function render($template){
  
    // canary...
    if(empty($template)){ throw new \InvalidArgumentException('$template was empty'); }//if
  
    // go ahead and just print the output to the screen
    $template_path = $this->normalizePath($template);
    include($template_path);
    
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
  
    if(!preg_match(sprintf('#%s$#i',preg_quote($this->template_ext)),$template)){
    
      $template .= $this->template_ext;
    
    }//if
  
    return $template;
  
  }//method

}//class     
