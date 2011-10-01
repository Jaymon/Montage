<?php

$base = realpath(__DIR__.'/../src');
require_once($base.'/Path.php');

class PathTest extends \PHPUnit_Framework_TestCase {

  public function testGetSibling(){
  
    $path = $this->getFixturePath('Path','foo');
    
    preg_match('#bar\\\\2#','C:\Projects\Sandbox\Montage\_active\test\fixtures\Path\bar\2\1.txt',$matched);
    \out::e($matched);
    
    preg_match('#bar/2#','C:/Projects/Sandbox/Montage/_active/test/fixtures/Path/bar/2/1.txt',$matched);
    \out::e($matched);
    
    preg_match('/bar\/2/i','C:/Projects/Sandbox/Montage/_active/test/fixtures/Path/bar/2/1.txt',$matched);
    \out::e($matched);
    
    preg_match('/bar\\\\2/i','C:\Projects\Sandbox\Montage\_active\test\fixtures\Path\bar\2\1.txt',$matched);
    \out::e($matched);
    
    preg_match('#bar\/2#','C:/Projects/Sandbox/Montage/_active/test/fixtures/Path/bar/2/1.txt',$matched);
    \out::e($matched);
    
    $regex = '#bar'.DIRECTORY_SEPARATOR.'2#';
    preg_match($regex,'C:\Projects\Sandbox\Montage\_active\test\fixtures\Path\bar\2\1.txt',$matched);
    \out::e($matched);
    
    ///$path->getSibling('#bar/2$#');
  
  }//method

  /**
   *  makes sure Path does what would you expect if empty bits are passed in, or
   *  if a Path instance is passed in that has no actual path   
   *
   *  @since  9-27-11
   */
  public function testEmptyPath(){
  
    $path = new Path('');
    $this->assertEquals('',(string)$path);
    
    $path = new Path($path);
    $this->assertEquals('',(string)$path);
    
    $path = new Path('foo',$path,'bar');
    $expected = new Path('foo','bar');
    $this->assertEquals((string)$expected,(string)$path);
    
    $path = new Path(new Path(''),'foo','bar');
    $expected = new Path('foo','bar');
    $this->assertEquals((string)$expected,(string)$path);
  
  }//method

  /**
   *  a Path instance will try and create the parent folder structure before writing
   *  the contents of the file if the parent folders don't already exist   
   *
   *  @since  9-26-11
   */
  public function testFolderAutoCreationWhenWriting(){
  
    $path_str = (string)$this->getTempPath(md5(microtime(true)),md5(microtime(true)),'test.txt');
    
    $set_path_txt = 'this is the string';
    
    $set_path = new Path($path_str);
    $set_path->set($set_path_txt);
  
    $get_path = new Path($path_str);
    $get_path_txt = $get_path->get();
    
    $this->assertSame($set_path_txt,$get_path_txt);
    
  }//method

  public function testPathStuff(){

    ///$path = new Path('E:\Projects\sandbox\montage\_active\test\fixtures\Path');
    $path = new Path('E:\Projects\sandbox\montage\_active\test\fixtures\Path\che.txt');
    
    foreach($path as $p){
    
      \out::e($p);
    
    }//foreach
    
    return;
    
    \out::b();
    
    \out::p('iterate');
    
    foreach($path->createIterator() as $p){
    
      \out::e($p->getFilename());
      \out::e($p->getBasename());
    
    }//foreach
  
    \out::p();
  
  }//method


  /**
   *  make sure ->getParent() works
   *
   *  @since  8-30-11   
   */
  public function testGetParent(){
  
    $test_list = array();
    $test_list[] = array(
      'create' => 'foo/bar/baz/che',
      'in' => '#bar#',
      'out' => join(DIRECTORY_SEPARATOR,array('foo','bar'))
    );
    $test_list[] = array(
      'create' => 'foo/bar/baz/che',
      'in' => '',
      'out' => join(DIRECTORY_SEPARATOR,array('foo','bar','baz'))
    );
    $test_list[] = array(
      'create' => '#foo#',
      'in' => '',
      'out' => ''
    );
    
    foreach($test_list as $key => $test_map){
    
      $instance = new Path($test_map['create']);
      $ret_instance = $instance->getParent($test_map['in']);
      $this->assertEquals($test_map['out'],(string)$ret_instance,$key);
    
    }//foreach
  
  }//method

  /**
   *  tests creating Path objects passing in different random bits   
   */
  public function testCreation(){
  
    $base = $this->getFixturePath();
    
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
  
    $base = $this->getFixturePath('Path');
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
    
    foreach($test_list as $key => $test_map){
    
      $actual = call_user_func_array(array($instance,'getChildren'),$test_map['in']);
      $this->assertEquals($test_map['out'],$actual,$key);
    
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
  
  protected function getFixturePath($path){
  
    $path = func_get_args();
    $ret_path = sprintf(
      '.%sfixtures%s%s',
      DIRECTORY_SEPARATOR,
      DIRECTORY_SEPARATOR,
      join(DIRECTORY_SEPARATOR,$path)
    );
    
    return realpath($path);
  
  }//method
  
  protected function getTempPath($path){
  
    $path = func_get_args();
    
    $ret_path = sys_get_temp_dir();
    if(mb_substr($ret_path,-1) !== DIRECTORY_SEPARATOR){
    
      $ret_path .= DIRECTORY_SEPARATOR;
    
    }//if
    
    $ret_path = sprintf(
      '%s%s',
      $ret_path,
      join(DIRECTORY_SEPARATOR,$path)
    );
  
    return $ret_path;
    
  }//method

}//class
