<?php

require_once(join(DIRECTORY_SEPARATOR,array(dirname(__FILE__),'..','montage_test_base_class.php')));

class montage_path_Test extends montage_test_base {

  public function testBuild(){
  
    $base = $this->getClassFixtureBase();
    
    $test_list = array();
    $test_list[] = array(
      'in' => array($base,'montage_path'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'montage_path'))
    );
    $test_list[] = array(
      'in' => array($base,'montage_path/'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'montage_path'))
    );
    $test_list[] = array(
      'in' => array($base,'\\montage_path'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'montage_path'))
    );
    $test_list[] = array(
      'in' => array($base,'/montage_path'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'montage_path'))
    );
    $test_list[] = array(
      'in' => array($base,array('montage_path','foo'),'1'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'montage_path','foo','1'))
    );
    
    foreach($test_list as $test_map){
    
      $rclass = new ReflectionClass('montage_path');
      $instance = $rclass->newInstanceArgs($test_map['in']);
      
      $this->assertEquals($test_map['path_str'],$instance->__toString());
    
    }//foreach
  
  }//method
  
  public function testSubDir(){
  
    $base = $this->getClassFixtureBase();
    $instance = new montage_path($base,'montage_path');
    
    $actual = $instance->getSubDirs();
    $expected = array(
      join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar')),
      join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','1')),
      join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','2')),
      join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','3')),
      join(DIRECTORY_SEPARATOR,array($instance->__toString(),'foo')),
      join(DIRECTORY_SEPARATOR,array($instance->__toString(),'foo','1'))
    );
    
    $this->assertEquals($expected,$actual);
  
  }//method
  
  public function testGetFiles(){
  
    $base = $this->getClassFixtureBase();
    $instance = new montage_path($base,'montage_path');
    
    $test_list = array();
    $test_list[] = array(
      'in' => '',
      'out' => array(
        join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','2','1.txt')),
        join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','2','monkey.txt')),
        join(DIRECTORY_SEPARATOR,array($instance->__toString(),'che.txt')),
        join(DIRECTORY_SEPARATOR,array($instance->__toString(),'foo','1','monkey.txt'))
      )
    );
    $test_list[] = array(
      'in' => '#monkey#',
      'out' => array(
        join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','2','monkey.txt')),
        join(DIRECTORY_SEPARATOR,array($instance->__toString(),'foo','1','monkey.txt'))
      )
    );
    
    foreach($test_list as $test_map){
    
      $actual = call_user_func(array($instance,'getFiles'),$test_map['in']);
      $this->assertEquals($test_map['out'],$actual);
    
    }//foreach
    
  }//method
  
  public function xtestCreateAndClear(){
  
    $base = $this->getClassFixtureBase();
    
    // if no error gets thrown, then the folder was created successfully...
    $instance = new montage_path($base,'montage_path',md5(microtime(true)));
    
    $path = $instance->__toString();
    
    // now get rid of the path...
    $instance->kill();
  
  
  }//method

}//class
