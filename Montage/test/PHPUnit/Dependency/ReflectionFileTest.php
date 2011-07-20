<?php
namespace Montage\Test\PHPUnit;

use ReflectionClass;
use Montage\Dependency\ReflectionFile;
use out;

require_once('out_class.php');
  
require_once(__DIR__.'/../Test.php');
///require_once(__DIR__.'/../../../Path.php');

///require_once(__DIR__.'/../../../Dependency/Reflection.php');
require_once(__DIR__.'/../../../Dependency/ReflectionFile.php');

class ReflectionFileTest extends Test {

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
        namespace Mingo;

        use Montage\AutoLoad\AutoLoadable;
        use MingoAutoload;

        class AutoLoader extends MingoAutoload implements AutoLoadable {}
        ',
      'out' => array(
        0 => array(
          'class' => '\Mingo\AutoLoader',
          'extends' => array('\MingoAutoload'),
          'implements' => array('\Montage\AutoLoad\AutoLoadable')
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
          'implements' => array()
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
          'implements' => array()
        ),
        1 => array(
          'class' => '\bar\bar',
          'extends' => array(),
          'implements' => array('\Serializable','\Countable')
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
          'implements' => array()
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
          'implements' => array()
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
          'implements' => array()
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
          'implements' => array()
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
          'implements' => array()
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
          'implements' => array('\Countable')
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

}//class
