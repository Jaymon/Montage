<?php
namespace Montage\Test\PHPUnit;

use PHPUnit\FrameworkTestCase;

use ReflectionClass;
use Montage\Dependency\ReflectionFile;

class ReflectionFileTest extends FrameworkTestCase {

  public function testFindClasses(){
  
    /* 
    $test_map_prototype = array(
      'in' => '<'.'?php',
      'out' => array(
        0 => array(
          'class' => '',
          'extends' => array(),
          'implements' => array()
        )
      )
    );
    */
  
    $test_list = array();
    $test_list[] = array(
      'in' => '<'.'?php
        abstract class Foo extends Bar implements Che {}
        ',
      'out' => array(
        0 => array(
          'class' => '\Foo',
          'extends' => array('\Bar'),
          'implements' => array('\Che'),
          'callable' => false
        )
      )
    );
    $test_list[] = array(
      'in' => '<'.'?php
        abstract class Foo extends Bar implements Che {
        
          abstract public function goo(){}
        
        }
        ',
      'out' => array(
        0 => array(
          'class' => '\Foo',
          'extends' => array('\Bar'),
          'implements' => array('\Che'),
          'callable' => false
        )
      )
    );
    $test_list[] = array(
      'in' => '<'.'?php
        abstract public function foo(){}
        ',
      'out' => array()
    );
    $test_list[] = array(
      'in' => '<'.'?php
        interface Foo {}
        ',
      'out' => array(
        0 => array(
          'class' => '\Foo',
          'extends' => array(),
          'implements' => array(),
          'callable' => false
        )
      )
    );
    $test_list[] = array(
      'in' => '<'.'?php
        interface Foo extends Bar {}
        ',
      'out' => array(
        0 => array(
          'class' => '\Foo',
          'extends' => array('\Bar'),
          'implements' => array(),
          'callable' => false
        )
      )
    );
    $test_list[] = array(
      'in' => '<'.'?php
        namespace Mingo;

        use Montage\AutoLoad\AutoLoadable;
        use MingoAutoload;

        class AutoLoader extends MingoAutoload implements AutoLoadable {}
        ',
      'out' => array(
        0 => array(
          'class' => '\Mingo\AutoLoader',
          'extends' => array('\MingoAutoload'),
          'implements' => array('\Montage\AutoLoad\AutoLoadable'),
          'callable' => true
        )
      )
    );
    
    $test_list[] = array(
      'in' => '<'.'?php
        namespace {
        
          use StdObject;
        
          class happy extends StdObject {}
        
        }
        ',
      'out' => array(
        0 => array(
          'class' => '\happy',
          'extends' => array('\StdObject'),
          'implements' => array(),
          'callable' => true
        )
      )
    );
    $test_list[] = array(
      'in' => '<'.'?php
        
        namespace foo {
        
          class foo extends \bang\boom\pow,che\bar {}
          
        }
        
        namespace bar {
        
          use foo;
        
          class bar implements \Serializable,\Countable {}
        
        }
        ',
      'out' => array(
        0 => array(
          'class' => '\foo\foo',
          'extends' => array('\bang\boom\pow','\foo\che\bar'),
          'implements' => array(),
          'callable' => true
        ),
        1 => array(
          'class' => '\bar\bar',
          'extends' => array(),
          'implements' => array('\Serializable','\Countable'),
          'callable' => true
        )
      )
    );
    $test_list[] = array(
      'in' => '<'.'?php
        use che;
      
        class foo extends \bang\boom\pow,che\bar {}
        ',
      'out' => array(
        0 => array(
          'class' => '\foo',
          'extends' => array('\bang\boom\pow','\che\bar'),
          'implements' => array(),
          'callable' => true
        )
      )
    );
    $test_list[] = array(
      'in' => '<'.'?php
        use che;
      
        class foo extends \bang\boom\pow {}
        ',
      'out' => array(
        0 => array(
          'class' => '\foo',
          'extends' => array('\bang\boom\pow'),
          'implements' => array(),
          'callable' => true
        )
      )
    );
    $test_list[] = array(
      'in' => '<'.'?php
        use che;
      
        class foo extends che\bar {}
        ',
      'out' => array(
        0 => array(
          'class' => '\foo',
          'extends' => array('\che\bar'),
          'implements' => array(),
          'callable' => true
        )
      )
    );
    $test_list[] = array(
      'in' => '<'.'?php
        use che;
      
        class foo extends che\bar {}
        ',
      'out' => array(
        0 => array(
          'class' => '\foo',
          'extends' => array('\che\bar'),
          'implements' => array(),
          'callable' => true
        )
      )
    );
    $test_list[] = array(
      'in' => '<'.'?php
        class foo extends bar {}
        ',
      'out' => array(
        0 => array(
          'class' => '\foo',
          'extends' => array('\bar'),
          'implements' => array(),
          'callable' => true
        )
      )
    );
    $test_list[] = array(
      'in' => '<'.'?php
        namespace happy;
      
        use \Montage\Classes;
        use out;
        use \Montage\Path as Foo;
        
        class Bar extends Foo implements \Countable {}//class
        ',
      'out' => array(
        0 => array(
          'class' => '\happy\Bar',
          'extends' => array('\Montage\Path'),
          'implements' => array('\Countable'),
          'callable' => true
        )
      )
    );
  
    $temp_file = tempnam(sys_get_temp_dir(),__CLASS__);
  
    foreach($test_list as $i => $test_map){
    
      file_put_contents($temp_file,$test_map['in'],LOCK_EX);
      $rfile = new ReflectionFile($temp_file);
      $this->assertEquals($test_map['out'],$rfile->getClasses(),$i);
    
    }//foreach
  
  }//method
  
  /**
   *  test ->hasClass()
   *  
   *  @since  9-7-11
   */
  public function testHasClass(){
  
    $test_list = array();
    $test_list[] = array(
      'file' => '<'.'?php
        namespace Mingo;

        use Montage\AutoLoad\AutoLoadable;
        use MingoAutoload;

        class AutoLoader extends MingoAutoload implements AutoLoadable {}
        ',
      'in' => '\Mingo\AutoLoader',
      'out' => true
    );
    $test_list[] = array(
      'file' => '<'.'?php
        namespace Mingo;

        use Montage\AutoLoad\AutoLoadable;
        use MingoAutoload;

        class AutoLoader extends MingoAutoload implements AutoLoadable {}
        ',
      'in' => '\blah\AutoLoader',
      'out' => false
    );
  
    $temp_file = tempnam(sys_get_temp_dir(),__CLASS__);
  
    foreach($test_list as $i => $test_map){
    
      file_put_contents($temp_file,$test_map['file'],LOCK_EX);
      $rfile = new ReflectionFile($temp_file);
      $this->assertEquals($test_map['out'],$rfile->hasClass($test_map['in']),$i);
    
    }//foreach
  
  }//method

}//class
