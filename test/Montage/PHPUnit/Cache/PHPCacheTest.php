<?php
namespace Montage\PHPUnit;
  
use PHPUnit\FrameworkTestCase;
use Montage\Cache\PHPCache;

class PHPCacheTest extends FrameworkTestCase {
  
  /**
   *  make sure the DIC will create class instances for type hinted constructor params
   */
  public function testSimpleCache(){
  
    $c = new PHPCache();
    $c->setPath(sys_get_temp_dir());
    
    $arr = array(
      'foo' => array(
        'bar' => 1,
        'c' => 2,
        'd' => array(1,2,3,4,5,6)
      ),
      'che' => true,
      'baz' => 'this is a string'
    );
    
    $c->set(__METHOD__,$arr);
    
    $ret_arr = $c->get(__METHOD__);
    $this->assertSame($arr,$ret_arr);
  
  }//method
  
  /**
   *  test to make sure an object can be cached and read back using php code cache
   *
   *  @since  8-22-11
   */
  public function testObjectCache(){
  
    $c = new PHPCache();
    $c->setPath(sys_get_temp_dir());
    
    $obj = new \StdClass();
    $obj->foo = 'bar';
    $obj->bar = 'foo';
    
    $arr = array(
      'obj' => $obj
    );
    
    $c->set(__METHOD__,$arr);
    
    $ret_arr = $c->get(__METHOD__);
    ///\out::e($ret_arr);
    
    $this->assertArrayHasKey('obj',$ret_arr);
    $this->assertInstanceOf('StdClass',$ret_arr['obj']);
    $this->assertEquals('bar',$ret_arr['obj']->foo);
    $this->assertEquals('foo',$ret_arr['obj']->bar);
  
  }//method

}//class

