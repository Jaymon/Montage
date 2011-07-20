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
      'in' => array('',1),
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
      'in' => array('#che#',1),
      'out' => array(
        'files' => array(
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'che.txt'))
        ),
        'folders' => array()
      )
    );
    $test_list[] = array(
      'in' => array('#something-not-matching#',1),
      'out' => array(
        'files' => array(),
        'folders' => array()
      )
    );
    $test_list[] = array(
      'in' => array('',-1),
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
      'in' => array('#che#',-1),
      'out' => array(
        'files' => array(
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'che.txt'))
        ),
        'folders' => array()
      )
    );
    $test_list[] = array(
      'in' => array('#1$#',-1),
      'out' => array(
        'files' => array(),
        'folders' => array(
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'bar','1')),
          join(DIRECTORY_SEPARATOR,array($instance->__toString(),'foo','1'))
        )
      )
    );
    $test_list[] = array(
      'in' => array('#nothing-matching#',-1),
      'out' => array(
        'files' => array(),
        'folders' => array()
      )
    );
    
    foreach($test_list as $test_map){
    
      $actual = call_user_func_array(array($instance,'getChildren'),$test_map['in']);
      $this->assertEquals($test_map['out'],$actual);
    
    }//foreach
  
  }//method
  
  /**
   *  make sure the sub path method works as expected
   *
   *  @since  6-20-11
   */
  public function testInAndIsMethods(){
    
    $test_list = array();
    $test_list[] = array(
      'method' => 'inParents',
      'init' => array('foo','bar'),
      'in' => array(new Path('foo')),
      'out' => true
    );
    $test_list[] = array(
      'method' => 'inParents',
      'init' => array('foo'),
      'in' => array(new Path('foo','bar')),
      'out' => false
    );
    $test_list[] = array(
      'method' => 'inChildren',
      'init' => array('foo'),
      'in' => array(new Path('foo','bar')),
      'out' => true
    );
    $test_list[] = array(
      'method' => 'inChildren',
      'init' => array('foo','bar'),
      'in' => array(new Path('foo')),
      'out' => false
    );
    $test_list[] = array(
      'method' => 'inFamily',
      'init' => array('foo'),
      'in' => array(new Path('foo','bar')),
      'out' => true
    );
    $test_list[] = array(
      'method' => 'inFamily',
      'init' => array('foo','bar'),
      'in' => array(new Path('foo')),
      'out' => true
    );
    $test_list[] = array(
      'method' => 'inFamily',
      'init' => array('baz','bar'),
      'in' => array(new Path('foo')),
      'out' => false
    );
    $test_list[] = array(
      'method' => 'isChild',
      'init' => array('foo','bar','baz'),
      'in' => array(new Path('foo','bar')),
      'out' => true
    );
    $test_list[] = array(
      'method' => 'isChild',
      'init' => array('foo','bar','baz'),
      'in' => array('foo',new Path('foo','bar')),
      'out' => true
    );
    $test_list[] = array(
      'method' => 'isChild',
      'init' => array('foo','bar','baz'),
      'in' => array(new Path('foo','bar'),new Path('che','baz')),
      'out' => false
    );
    $test_list[] = array(
      'method' => 'isParent',
      'init' => array('foo'),
      'in' => array(new Path('foo','bar')),
      'out' => true
    );
    $test_list[] = array(
      'method' => 'isParent',
      'init' => array('foo'),
      'in' => array(new Path('foo','bar'),new Path('foo','bar','che','baz')),
      'out' => true
    );
    $test_list[] = array(
      'method' => 'isParent',
      'init' => array('foo'),
      'in' => array(new Path('foo','bar'),new Path('che','baz')),
      'out' => false
    );
    
    foreach($test_list as $i => $test_map){
    
      $rpath = new ReflectionClass('Montage\Path');
      $instance = $rpath->newInstanceArgs($test_map['init']);
    
      $actual = call_user_func_array(array($instance,$test_map['method']),$test_map['in']);      
      $this->assertEquals($test_map['out'],$actual,$i);
    
    }//foreach
  
  }//method
  
  /**
   *  @since  6-23-11
   */
  public function testClear(){
  
    $path_list = array();
    $class_name_bits = explode('\\',get_class($this));
    $path_list[0] = new Path(sys_get_temp_dir(),end($class_name_bits));
    $path_list[0]->assure();
    $total_count = 0;
    
    for($i = 1,$max = rand(0,5); $i < $max ;$i++){
    
      $path_list[$i] = new Path($path_list[0],md5(microtime(true)));
      $path_list[$i]->assure();
      usleep(1);
      $total_count++;
    
    }//for
    
    foreach($path_list as $key => $path){
    
      for($i = 0,$max = rand(0,10); $i < $max ;$i++){
      
        tempnam($path,$i);
        $total_count++;
      
      }//for
    
    }//foreach
    
    ///$file_count = count($path_list[0]);
    
    $ret_count = $path_list[0]->clear();
    $this->assertEquals($total_count,$ret_count);
  
  }//method
  
  /**
   *  @since  6-23-11
   */
  public function testKill(){
  
    $path_list = array();
    $class_name_bits = explode('\\',get_class($this));
    $path_list[0] = new Path(sys_get_temp_dir(),end($class_name_bits));
    $path_list[0]->assure();
    $total_count = 0;
    
    for($i = 1,$max = rand(0,5); $i < $max ;$i++){
    
      $path_list[$i] = new Path($path_list[0],md5(microtime(true)));
      $path_list[$i]->assure();
      usleep(1);
      $total_count++;
    
    }//for
    
    foreach($path_list as $key => $path){
    
      for($i = 0,$max = rand(0,10); $i < $max ;$i++){
      
        tempnam($path,$i);
        $total_count++;
      
      }//for
    
    }//foreach
    
    ///$file_count = count($path_list[0]);
    
    $ret_count = $path_list[0]->kill();
    $this->assertEquals(($total_count + 1),$ret_count);
    $this->assertFalse($path_list[0]->exists());
  
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
