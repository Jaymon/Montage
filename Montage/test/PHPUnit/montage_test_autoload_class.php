<?php

/**
 *  used in the command line and the testing stuff to autoload mingo dependant classes  
 * 
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 10-5-10
 *  @package mingo 
 ******************************************************************************/

// personal debugging stuff, ignore..
$out_path_list = array(
  'out_class.php',
  'C:\Projects\Plancast\_active\lib\out_class.php',
  'E:\Projects\sandbox\out\git_repo\out_class.php'
);
foreach($out_path_list as $out_path){
  if(is_file($out_path)){ include_once($out_path); break; }//if
}//foreach

class montage_test_autoload {

  private static $is_registered = false;
  
  private static $path_map = array();

  public static function register(){
  
    // canary...
    if(self::$is_registered){ return true; }//if
  
    $basepath = realpath(
      join(DIRECTORY_SEPARATOR,array(dirname(__FILE__),'..','..','model'))
    );
  
    set_include_path(
      get_include_path()
      .PATH_SEPARATOR.
      $basepath
    );
    
    self::$is_registered = true;
    
    return spl_autoload_register(array(__CLASS__,'load'));
    
  }//method
  
  /**
   *  load a class   
   *      
   *  @return boolean true if the class was found, false if not (so other autoloaders can have a chance)
   */
  public static function load($class_name){
  
    $path_list = explode(PATH_SEPARATOR,get_include_path());
  
    $class_postfix_list = array('','_class','.class');
    
    foreach($path_list as $path){
    
      $dir_list = array();
      if(!isset(self::$path_map[$path])){
        $dir_list = self::getHierarchy($path);
        self::$path_map[$path] = $dir_list;
      }else{
        $dir_list = self::$path_map[$path];
      }//if/else
    
      foreach($dir_list as $dir){
      
        foreach($class_postfix_list as $class_postfix){
        
          $class_path = join(DIRECTORY_SEPARATOR,array($dir,sprintf('%s%s.php',$class_name,$class_postfix)));
        
          if(file_exists($class_path)){
          
            include_once($class_path);
            return true;
          
          }//if
          
        }//foreach
        
      }//foreach
    
    }//foreach
    
    return false;
  
  }//method
  
  private static function getHierarchy($path,$depth = 0){
  
    $ret_list = glob(join(DIRECTORY_SEPARATOR,array($path,'*')),GLOB_ONLYDIR);
    if(!empty($ret_list)){
      
      foreach($ret_list as $path){
        $ret_list = array_merge($ret_list,self::getHierarchy($path,$depth + 1));
      }//foreach
      
    }//if
    
    if($depth === 0){ array_unshift($ret_list,$path); }//if
    return $ret_list;
  
  }//method

}//class   
