<?php
/*
name the skeleton folder "skeleton" folder and it should contain a mostly working copy of the very
beginning of a montage app, so it should have all the folders, and an .htaccess, etc.

let the user pass in other controllers (eg, php create.php --controller=foo --controller=bar)
so those will be created also and placed in the default controller/ folder with index and error

I guess we'll have a model/scripts that contains build classes that are called through create.php
and that use montage_factory so a user could override it if they wanted. This class should
have classes that go though the template files and replace things like $date and $user in the
docblocks so they can be customized with the correct date, version, etc.

make sure to check if the folder/file already exists and not to overwrite it, that way a user
could call create.php --controller=new_controller_name and have them be created without ruining
the project, this would be handy for quickly adding new controllers, start classes, and plugins

--override=montage_request might be cool, that would create a class named "app_request" or
"request" and have it extend montage_request and put in the app/model directory

--controller=controller_name --controller-class="controller_class_name" if you specify more than
one controller than it will put a controller_class_name in every --controller specified

passing in multiple controllers needs to create multiple indexes (eg, index_controller.php, but the first
one will be set to index.php and the htaccess will point to that one, all the others get index_controller.php

*/

// these are the constants the index should set...
define('MONTAGE_CONTROLLER','skeleton');
define('MONTAGE_DEBUG',true);
define('MONTAGE_ENVIRONMENT','dev');
define('MONTAGE_APP_PATH',realpath(dirname(__FILE__)));
define('MONTAGE_HANDLE',false); // we only need to start montage, not have it handle the request
define('MONTAGE_CACHE_PATH',sys_get_temp_dir()); // override default cache path to the temp dir

// start montage...
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_APP_PATH,'start.php')));

// we want the cache to be rebuilt every call of this script...
montage_cache::kill();

///out::setPrintObject(false);

// these are required...
$required_argv = array(
  'path' => null, // the path where the project will be created
  'controller' => array('frontend','cli'), // the default controller(s)
  'environment' => array('dev','prod'), // the default environment(s)
  'app' => '', // the application name, if specified, then it will be appended to "path"
  'author' => montage::getRequest()->getServerField(array('user','USERNAME'),'')
);

if(in_array('--help',$argv,true)){

  montage_cli::out('Easily create a new Montage app!');
  montage_cli::out('');
  montage_cli::out('OPTIONS:');
  montage_cli::out(' --help              Bring up this help message.');
  montage_cli::out(' --path              REQUIRED - The path the app will be created in.');
  montage_cli::out(' --controller        Controllers to be created. Default: %s.',$required_argv['controller']);
  montage_cli::out(' --controller-class  Controller classes to be created in each controller.');
  montage_cli::out(' --environment       Environments to be created. Default: %s.',$required_argv['environment']);
  montage_cli::out(' --app               The app\'s name, appended to path if included.');
  montage_cli::out(' --author            The creator of the new app. Default: %s',$required_argv['author']);
  montage_cli::out(' --include-path      A path containing classes/files that extend this script');
  montage_cli::out(' --plugin            Create a plugin skeleton with the given name.');
  exit();

}//if

try{

  $options_map = montage_cli::parseArgv(montage_cli::getArgv(),$required_argv);

  $path = montage_path::format(montage_path::get($options_map['path'],$options_map['app']));

  // add any passed in include paths, this will allow customized create scripts...
  if(!empty($options_map['include-path'])){
    
    $options_map['include-path'] = (array)$options_map['include-path'];
    
    foreach($options_map['include-path'] as $include_path){
      montage_core::setPath($include_path);
    }//foreach
    
  }//if

  // let the user know what we're going to do...
  montage_cli::out('Creating a new Montage app with settings:');
  montage_cli::out(' Path: %s',$path);
  montage_cli::out(' Author: %s',$options_map['author']);
  montage_cli::out(' Environment: %s',$options_map['environment']);
  montage_cli::out(' Controllers: %s',$options_map['controller']);
  montage_cli::out('');

  montage_factory::getBestInstance(
    'montage_skeleton',
    array(
      $path,
      $options_map
    )
  );
  
}catch(Exception $e){

  montage_cli::out('ERROR: %s',$e->getMessage());
  
}//try/catch
