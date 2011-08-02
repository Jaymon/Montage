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
    
    $c->set(__CLASS__,$arr);
    
    $ret_arr = $c->get(__CLASS__);
  
  }//method

}//class

