<?php
namespace Montage\Test\PHPUnit;

require_once('out_class.php');
require_once(__DIR__.'/Test.php');
require_once(__DIR__.'/../../Path.php');

use ReflectionClass;
use Montage\Path;

class PathTest extends Test {

  /**
   *  tests creating Path objects passing in different random bits   
   */
  public function testCreation(){
  
    $base = $this->getFixture();
    
    $test_list = array();
    $test_list[] = array(
      'in' => array($base,'Path'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path'))
    );
    $test_list[] = array(
      'in' => array($base,'Path/'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path'))
    );
    $test_list[] = array(
      'in' => array($base,'\\Path'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path'))
    );
    $test_list[] = array(
      'in' => array($base,'/Path'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path'))
    );
    $test_list[] = array(
      'in' => array($base,array('Path','foo'),'1'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path','foo','1'))
    );
    $test_list[] = array(
      'in' => array(new Path($base),array('Path','foo'),'1'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path','foo','1'))
    );
    $test_list[] = array(
      'in' => array($base,array(array('Path','foo')),' ','','1'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path','foo','1'))
    );
    
    foreach($test_list as $test_map){
    
      $rclass = new ReflectionClass('Montage\\Path');
      $instance = $rclass->newInstanceArgs($test_map['in']);
      
      $this->assertEquals($test_map['path_str'],$instance->__toString());
    
    }//foreach
  
  }//method
  
  /**
   *  tests the {@link Montage\Path::getChildren()} method
   */
  public function testGetChildren(){
  
    $base = $this->getFixture('Path');
    $instance = new Path($base);
    
    $test_list = array();
    $test_list[] = array(
      'in' => '',
      'out' => array(
        'files' => array(
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'che.txt'))
        ),
        'folders' => array(
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar')),
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'foo'))
        )
      )
    );
    $test_list[] = array(
      'in' => '#che#',
      'out' => array(
        'files' => array(
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'che.txt'))
        ),
        'folders' => array()
      )
    );
    $test_list[] = array(
      'in' => '#something-not-matching#',
      'out' => array(
        'files' => array(),
        'folders' => array()
      )
    );
    
    foreach($test_list as $test_map){
    
      $actual = call_user_func(array($instance,'getChildren'),$test_map['in']);
      $this->assertEquals($test_map['out'],$actual);
    
    }//foreach
  
  }//method
  
  /**
   *  tests the {@link Montage\Path::getDescendants()} method
   */
  public function testGetSubPaths(){
  
    $base = $this->getFixture('Path');
    $instance = new Path($base);
    
    $test_list = array();
    $test_list[] = array(
      'in' => '',
      'out' => array(
        'files' => array(
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','2','1.txt')),
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','2','monkey.txt')),
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'che.txt')),
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'foo','1','monkey.txt'))
        ),
        'folders' => array(
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar')),
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','1')),
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','2')),
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','3')),
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'foo')),
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'foo','1'))
        )
      )
    );
    $test_list[] = array(
      'in' => '#che#',
      'out' => array(
        'files' => array(
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'che.txt'))
        ),
        'folders' => array()
      )
    );
    $test_list[] = array(
      'in' => '#1$#',
      'out' => array(
        'files' => array(),
        'folders' => array(
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','1')),
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'foo','1'))
        )
      )
    );
    $test_list[] = array(
      'in' => '#nothing-matching#',
      'out' => array(
        'files' => array(),
        'folders' => array()
      )
    );
    
    foreach($test_list as $test_map){
    
      $actual = call_user_func(array($instance,'getSubPaths'),$test_map['in']);      
      $this->assertEquals($test_map['out'],$actual,$test_map['in']);
    
    }//foreach
  
  }//method
  
  /**
   *  make sure the sub path method works as expected
   *
   *  @since  6-20-11
   */
  public function testIsSubPath(){
  
    $base = $this->getFixture('Path');
    $instance = new Path($base);
    
    $test_list = array();
    $test_list[] = array(
      'init' => array('foo','bar'),
      'in' => new Path('foo'),
      'out' => true
    );
    
    foreach($test_list as $i => $test_map){
    
      $rpath = new ReflectionClass('Montage\Path');
      $instance = $rpath->newInstanceArgs($test_map['init']);
    
      $actual = call_user_func(array($instance,'isSubPath'),$test_map['in']);      
      $this->assertEquals($test_map['out'],$actual,$i);
    
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
