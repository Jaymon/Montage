<?php

/**
 *  render the template 
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-22-10
 *  @package montage
 *  @subpackage template 
 ******************************************************************************/
class montage_template extends montage_base {

  /**
   *  this is the default value, it will capture the output of the template and return it as a string
   */     
  const OPTION_OUT_STR = 1;
  /**
   *  if set, this will output the template to standard output
   */     
  const OPTION_OUT_STD = 2;

  /**
   *  the actual template file that will be used to render the view
   *  @var  string
   */
  protected $template = '';
  
  /**
   *  set to true when actually rendering the template
   *  
   *  @var  boolean
   */
  protected $in_template = false;
  
  final function __construct(){
    $this->start();
  }//method
  
  /**
   *  the actual template name (eg, layout.php or page_tmpl.php
   */        
  function setTemplate($val){ return $this->template = $val; }//method
  function getTemplate(){ return $this->template; }//method
  function hasTemplate(){ return !empty($this->template); }//method
  
  /**
   *  Open, parse, and return/output the template file.
   *     
   *  @param  integer $options  one or more of the OPTION_* class constants or'ed together (eg, OPTION_* | OPTION_*)
   *  @return mixed boolean if OPTION_OUT_STD is set, string if OPTION_OUT_STR is set   
   */        
  function out($options = self::OPTION_OUT_STR){
    
    // canary...
    if(!$this->hasTemplate()){
      throw new RuntimeException('no template file specified');
    }//if
    
    $ret_mix = '';
    
    // decide to return string or output to standard out, default is string...
    $is_ret_str = true;
    if($options & self::OPTION_OUT_STD){
      $is_ret_str = false;
      $ret_mix = false;
    }//if/else if
    
    $this->in_template = true;
    
    if($is_ret_str){
    
      // capture the template into a string...
      // NOTE 5-1-08: if the page just comes out blank for some reason, their might 
      //  be an unsupported character in the template, and even better are chances that it 
      //  is that dumbass windows quote symbol...
      ob_start();
        include($this->getTemplate()); // Include the file
        $ret_mix = ob_get_contents(); // Get the contents of the buffer
      // End buffering and discard
      ob_end_clean();
    
    }else{
    
      // go ahead and just print the output to the screen
      include($this->getTemplate());
      $ret_mix = true;
    
    }//if/else
    
    $this->in_template = false;
    
    return $ret_mix;
    
  }//method
  
  /**
   *  if the class should check more than one place for the template, add the alternate
   *  paths using this, setTemplate will then go through all the paths checking
   *  to see if the file exists in any path
   *  
   *  we render the template files using the include_paths so you can set other template files
   *  in the actual template and include them without having to actually know the path, it's all
   *  for your convenience programming               
   *  
   *  @param  string  $path the template path to add to the include paths
   */
  function setPath($path){
  
    // canary...
    if(empty($path)){
      throw new UnexpectedValueException('$path is empty');
    }//if
    if(!is_dir($path)){
      throw new UnexpectedValueException(sprintf('"%s" is not a valid directory',$path));
    }//if
  
    $path = get_include_path().PATH_SEPARATOR.$path;
    set_include_path($path);
    return $path;
  
  }//method
  
  /**
   *  overloaded from parent so when in the template it will return a montage_escaped wrapped
   *  value, this is useful for making sure user submitted input is safe while not having to worry
   *  about it anywhere else but the view      
   *  
   *  @see  parent::getField()
   */
  function getField($key,$default_val = null){
    
    $ret_mix = $default_val;
    
    if($this->in_template){
      if($this->existsField($key)){
        $class_name = montage_core::getCoreClassName('MONTAGE_ESCAPE');
        $ret_mix = new $class_name($this->field_map[$key]);
      }//if
    }else{
      $ret_mix = parent::getField($key,$default_val);
    }//if/else
  
    return $ret_mix;
    
  }//method
  
  /**
   *  always return the raw value, regardless of whether we are in the template or not
   *  
   *  @see  parent::getField()
   */
  function getRawField($key,$default_val = null){
    return parent::getField($key,$default_val);
  }//function

}//class     
