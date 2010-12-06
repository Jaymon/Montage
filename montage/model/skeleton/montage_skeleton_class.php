<?php

/**
 *  used by the create.php script to build a skeleton montage app
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-24-10
 *  @package montage 
 ******************************************************************************/
class montage_skeleton extends montage_base {

  /**
   *  holds the skeleton for the structure of an app
   *  
   *  the folder/file names are keys in the array, a file has a null/object instance value, a directory
   *  has an array value (with more file/dir keys, or empty if the dir should just be created)         
   *
   *  @var  array   
   */        
  protected $skeleton_map = array();

  /**
   *  @param  string  $montage_app_path the path that will be "populated" with a montage
   *                                    skeleton application   
   *  @param  array $options_map  key/val pairs
   */
  final function __construct($montage_app_path,$options_map = array()){
  
    $this->skeleton_map = array(
      'settings' => array(),
      'model' => array(),
      'plugins' => array(),
      'web' => array(),
      'cache' => array(),
      'data' => array(),
      'controller' => array(),
      'view' => array(),
      'README.textile' => null
    );
    
    $this->setField('start_postfix','_start');
    $this->setField('controller_postfix','_controller');
    
    $this->setField('montage_app_path',$montage_app_path);
    $this->setField('options_map',$options_map);
    
    // start populating settings folder...
    $options_map['docblock_desc'] = 'hold global app configuration';
    $this->addStartClass(montage_core::CLASS_NAME_APP_START,$options_map);
    
    $environment_list = (array)$options_map['environment'];
    foreach($environment_list as $environment){
      
      $options_map['docblock_desc'] = 'hold environment specific configuration';
      $this->addStartClass($environment,$options_map);
      
    }//foreach
    
    // add any controllers...
    if(!empty($options_map['controller'])){
      
      $controller_list = (array)$options_map['controller'];
      
      foreach($controller_list as $controller){
        
        // add controller start class...
        $this->addController($controller,$options_map);
      
      }//foreach
        
    }//if
    
    // these are the user facing folders...
    $this->addWeb($options_map);
    $this->addCli($options_map);
    
    // add any plugins...
    if(!empty($options_map['plugin'])){
      
      $plugin_list = (array)$options_map['plugin'];
      
      foreach($plugin_list as $plugin){
        
        // add controller start class...
        $this->addPlugin($plugin,$options_map);
      
      }//foreach
        
    }//if
    
    $this->start();

    clearstatcache(); // we want to make sure we don't have outdated file info
    $this->create($montage_app_path,$this->skeleton_map);
  
  }//method
  
  /**
   *  for custom children that want to do config stuff
   */
  protected function start(){}//method
  
  /**
   *  this actually creates the skeleton app, it's recursive
   *     
   *  @param  string  $basepath the path that will be created
   *  @param  array $basepath_map the file/folders that will be add at $basepath
   */ 
  protected function create($basepath,$basepath_map){
  
    foreach($basepath_map as $file_name => $type){
    
      $path = montage_path::get($basepath,$file_name);
      montage_cli::out($path);
      
      if($type === null){
        
        // it's a file and just needs to be created...
        if(is_file($path)){
        
          montage_cli::out('  ...file exists!');
        
        }else{
          
          file_put_contents($path,'',LOCK_EX);
          montage_cli::out('  ...file created!');
          
        }//if/else
      
      }else if($type instanceof skeleton_file){
      
        if(is_file($path)){
        
          montage_cli::out('  ...file skeleton exists!');
        
        }else{
        
          // it's a file is created an populated...
          file_put_contents($path,$type->get(),LOCK_EX);
          montage_cli::out('  ...file skeleton created!');
          
        }//if/else
      
      }else if(is_array($type)){
      
        // we have another directory, create it and populate it...
        montage_path::assure($path);
        montage_cli::out('  ...path created!');
        $this->create($path,$type);
      
      }//if/else if...
    
    }//foreach
    
  }//method
  
  /**
   *  this will add a start class to the internal skeleton
   *  
   *  @param  string  $name the name of the class to add      
   *  @param  array $options_map  key/val pairs
   *  @return boolean   
   */
  protected function addStartClass($name,$options_map){
  
    list($start_filename,$start_instance) = $this->getStartClass($name,$options_map);
    $this->skeleton_map['settings'][$start_filename] = $start_instance;
    return true;
  
  }//method
  
  /**
   *  returns a start class info array
   *  
   *  @param  string  $name the name of the class to add      
   *  @param  array $options_map  key/val pairs
   *  @return array array($start_filename,$start_instance);
   */
  protected function getStartClass($name,$options_map){
  
    $start_name = $this->getStartClassName($name);
    $start_filename = $this->getClassFilename($start_name);
    $start_instance = montage_factory::getBestInstance(
      'skeleton_start',
      array($start_name,$options_map)
    );
    
    return array($start_filename,$start_instance);
  
  }//method
  
  /**
   *  this will add a controller class to the internal skeleton at $controller
   *  
   *  @param  string  $controller the controller folder to add the controller class to   
   *  @param  string  $name the name of the class to add      
   *  @param  array $options_map  key/val pairs
   *  @return boolean   
   */
  protected function addControllerClass($controller,$name,$options_map){
  
    $controller_name = $this->getControllerClassName($name);
    $controller_filename = $this->getClassFilename($controller_name);
    $this->skeleton_map['controller'][$controller][$controller_filename] = montage_factory::getBestInstance(
      'skeleton_controller',
      array($controller_name,$options_map)
    );
  
    return true;
  
  }//method
  
  /**
   *  this will add a complete plugin skeleton, with start class
   *  
   *  this is handy for quickly adding a new plugin      
   *  
   *  @param  string  $plugin the name of the plugin
   *  @param  array $options_map  key/val pairs
   *  @return boolean
   */
  protected function addPlugin($plugin,$options_map){
  
    // set up the plugins file structure...
    $this->skeleton_map['plugins'][$plugin] = array();
    $this->skeleton_map['plugins'][$plugin]['settings'] = array();
    $this->skeleton_map['plugins'][$plugin]['model'] = array();
    $this->skeleton_map['plugins'][$plugin]['README.textile'] = null;
    
    // add the start class...
    $options_map['docblock_desc'] = 'hold plugin specific configuration';
    list($start_filename,$start_instance) = $this->getStartClass($plugin,$options_map);
    $this->skeleton_map['plugins'][$plugin]['settings'][$start_filename] = $start_instance;
    
    return true;
  
  }//method
  
  /**
   *  this will add a complete controller, with start class, and default controller classes
   *  
   *  @param  string  $controller the name of the controller
   *  @param  array $options_map  key/val pairs
   *  @return boolean   
   */
  protected function addController($controller,$options_map){
  
    $options_map['docblock_desc'] = 'hold controller specific configuration';
    $this->addStartClass($controller,$options_map);
  
    // add the controller path...
    $this->skeleton_map['controller'][$controller] = array();
    
    // add the default controllers...
    $options_map['docblock_desc'] = 'Default Controller for all requests that have no where else to go';
    $this->addControllerClass($controller,montage_forward::CONTROLLER_CLASS_NAME,$options_map);
    
    $options_map['docblock_desc'] = 'error controller, called when an exception is encountered';
    $this->addControllerClass($controller,montage_forward::CONTROLLER_ERROR_CLASS_NAME,$options_map);
    
    // add any other controller classes...
    if(!empty($options_map['controller-class'])){
  
      foreach($options_map['controller-class'] as $controller_class_name){
      
        $options_map['docblock_desc'] = 'A controller class';
        $this->addControllerClass($controller,$controller_class_name,$options_map);
      
      }//foreach
      
    }//if
    
    return true;
  
  }//method
  
  /**
   *  this will add a web folder, a web folder provides the public facing portion of the app
   *
   *  @param  array $options_map  key/val pairs
   *  @return boolean   
   */
  protected function addWeb($options_map){
    
    // canary...
    if(empty($options_map['controller'])){ return false; }//if
    if(empty($options_map['environment'])){ return false; }//if
    
    $montage_app_path = $this->getField('montage_app_path','');
    $montage_path = montage_path::getFramework();
    
    $controller_list = (array)$options_map['controller'];
    reset($controller_list);
    $controller = current($controller_list);
    
    $environment_list = (array)$options_map['environment'];
    
    foreach($environment_list as $environment){
    
      // set index.skel specific crap...
      $options_map['controller'] = $controller;
      $options_map['environment'] = $environment;
      $options_map['montage_app_path'] = $montage_app_path;
      $options_map['montage_path'] = $montage_path;
      unset($options_map['docblock_desc']);
      
      $index_name = sprintf('index_%s.php',$environment);
      
      $this->skeleton_map['web'][$index_name] = montage_factory::getBestInstance(
        'skeleton_index',
        array($index_name,$options_map)
      );
    
    }//foreach
    
    // set the .htaccess file to point to the first environment if it isn't previously there...
    if(empty($this->skeleton_map['web']['.htaccess'])){
    
      reset($this->skeleton_map['web']);
      $index_name = key($this->skeleton_map['web']);
      
      $this->skeleton_map['web']['.htaccess'] = montage_factory::getBestInstance(
        'skeleton_htaccess',
        array($index_name,$options_map)
      );
    
    }//if
    
    return true;
  
  }//method
  
  /**
   *  this will add a cli folder, a cli folder is like a web folder but for the command line
   *
   *  @param  array $options_map  key/val pairs
   *  @return boolean   
   */
  protected function addCli($options_map){
    
    $controller_list = (array)$options_map['controller'];
    $environment_list = (array)$options_map['environment'];

    // canary...
    if(!in_array('cli',$controller_list,true)){ return false; }//if
    if(empty($options_map['environment'])){ return false; }//if
    
    // add the cli folder...
    $this->skeleton_map['cli'] = array();
    
    
    $montage_app_path = $this->getField('montage_app_path','');
    $montage_path = montage_path::getFramework();
    
    $controller = 'cli';
    $options_map['docblock_desc'] = 'Command line interface for Montage';
    
    foreach($environment_list as $environment){
    
      // set index.skel specific crap...
      $options_map['controller'] = $controller;
      $options_map['environment'] = $environment;
      $options_map['montage_app_path'] = $montage_app_path;
      $options_map['montage_path'] = $montage_path;
      
      $index_name = sprintf('cli_%s.php',$environment);
      
      $this->skeleton_map['cli'][$index_name] = montage_factory::getBestInstance(
        'skeleton_index',
        array($index_name,$options_map)
      );
    
    }//foreach
    
    return true;
  
  }//method
  
  /**
   *  gets the start class name with the appropriate postfix attached
   *  
   *  @param  string  $name the name of the class to add      
   *  @return string
   */
  protected function getStartClassName($name){
    return sprintf('%s%s',$name,$this->getField('start_postfix',''));
  }//method
  
  /**
   *  gets the controller class name with the appropriate postfix attached
   *  
   *  @param  string  $name the name of the class to add      
   *  @return string
   */
  protected function getControllerClassName($name){
    return sprintf('%s%s',$name,$this->getField('controller_postfix',''));
  }//method
  
  /**
   *  gets a filename from the given class name
   *  
   *  @param  string  $class_name the name of the class to use to get the filename
   *  @return string
   */
  protected function getClassFilename($class_name){
    return sprintf('%s_class.php',$class_name);
  }//method

}//class     
