<?php

/**
 *  use to get info on the framework, handy for debugging
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 4-15-10
 *  @package montage
 *  @subpackage help 
 ******************************************************************************/
class montage_info {

  /**
   *  generate a backtrace list
   *  
   *  @param  integer $skip how many rows to skip of debug_backtrace   
   *  @return array a list of method calls from first to latest (by default debug_backtrace gives latest to first)
   */
  static function getBacktrace($skip = 1){
  
    // set the backtrace...
    $backtrace = debug_backtrace();
    $backtrace = array_reverse(array_slice($backtrace,$skip));
    $ret_list = array();
    
    foreach($backtrace as $key => $bt_map){
    
      $class = empty($bt_map['class']) ? '' : $bt_map['class'];
      $method = empty($bt_map['function']) ? '' : $bt_map['function'];
      $file = empty($bt_map['file']) ? 'unknown' : $bt_map['file'];
      $line = empty($bt_map['line']) ? 'unknown' : $bt_map['line'];
      
      $method_call = '';
      if(empty($class)){
        if(!empty($method)){ $method_call = $method; }//if
      }else{
        if(empty($method)){
          $method_call = $class;
        }else{
        
          $method_reflect = new ReflectionMethod($class,$method);
          $method_call = sprintf(
            '%s %s::%s',
            join(' ',Reflection::getModifierNames($method_reflect->getModifiers())),
            $class,
            $method
          );
          
        }//if/else
      }//if/else
      
      $arg_list = array();
      if(!empty($bt_map['args'])){
      
        foreach($bt_map['args'] as $arg){
        
          if(is_object($arg)){
            $arg_list[] = get_class($arg);
          }else{
            if(is_array($arg)){
              $arg_list[] = sprintf('Array(%s)',count($arg));
            }else if(is_bool($arg)){
              $arg_list[] = $arg ? 'TRUE' : 'FALSE';
            }else if(is_null($arg)){
              $arg_list[] = 'NULL';
            }else if(is_string($arg)){
              $arg_list[] = sprintf('"%s"',$arg);
            }else{
              $arg_list[] = $arg;
            }//if/else
          }//if/else
        
        }//foreach
      
      }//if
      
      $ret_list[] = sprintf('%s(%s) - %s:%s',$method_call,join(', ',$arg_list),$file,$line);
      
    }//foreach
  
    return $ret_list;
  
  }//method
  
  /**
   *  get information about what urls are mapped to what controller class::method in the defined
   *  controller namespace
   *  
   *  inspired by a similar thing I saw in FubuMVC Diagnostics:
   *  http://guides.fubumvc.com/getting_started.html         
   *  
   *  @return array
   */
  static function getControllers(){
  
    $ret_list = array();
  
    // some needed information to decide about methods... 
    $method_prefix = montage_forward::CONTROLLER_METHOD_PREFIX;
    $method_prefix_len = mb_strlen($method_prefix);
    $method_default = montage_forward::CONTROLLER_METHOD;
    $class_default = montage_forward::CONTROLLER_CLASS_NAME;
    
    $class_name_list = montage_core::getControllerClassNames();
    
    if(!empty($class_name_list)){
    
      foreach($class_name_list as $class_name){
      
      	$ret_map = array();
      	$prefix_list = array();
      	
      	// make sure we're not dealing with the default class...
      	if(mb_stripos($class_name,$class_default) === false){ $prefix_list[] = $class_name; }//if
      	
      	// now go through all the methods looking for "handle" methods...
      	$rclass = new ReflectionClass($class_name);
      	$rmethod_list = $rclass->getMethods();
      	foreach($rmethod_list as $rmethod){
        
          if($rmethod->isPublic() && !$rmethod->isStatic()){
          
            $is_controller_method = false;
            $method_name = $rmethod->getName();
          
            if(mb_stripos($method_name,$method_default) === 0){
          
              $is_controller_method = true;
          
            }else if(mb_stripos($method_name,$method_prefix) === 0){
            
              $prefix_list[] = mb_substr($method_name,$method_prefix_len);
              $is_controller_method = true;
              
            }//if/else if
            
            if($is_controller_method){
            
              $ret_map['callback'] = array($class_name,$method_name);
              $ret_map['params'] = array();
              $has_no_params = true; // set to false if one of the params has to be passed in
              $prefix_path = join('/',$prefix_list);
              if(empty($prefix_path)){
                $prefix_path = '/';
              }else{
                $prefix_path = sprintf('/%s/',$prefix_path);
              }//if
              
              // now go through the parameters to finish building the path...
              $rparam_list = $rmethod->getParameters();
              foreach($rparam_list as $rparam){
              
                $prefix_param = sprintf('$%s',$rparam->getName());
              
                if($rparam->isDefaultValueAvailable()){
                
                  $ret_map['path'] = $prefix_path;
                  $ret_list[] = $ret_map;
                
                }else{
                  $has_no_params = false;
                }//if/else
              
                $ret_map['params'][] = $prefix_param;  
                $ret_map['path'] = sprintf('%s%s/',$prefix_path,$prefix_param);
                $ret_list[] = $ret_map;
              
              }//foreach
              
              if($has_no_params){
              
                $ret_map['params'] = array();  
                $ret_map['path'] = $prefix_path;
                $ret_list[] = $ret_map;
              
              }//method
              
              // get rid of the method...
              array_pop($prefix_list);
              
            }//if
          
          }//if
        
        }//foreach

      }//foreach
  
    }//if

    return $ret_list;
  
  }//method

}//class     
