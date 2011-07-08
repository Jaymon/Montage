<?php
namespace Montage\Test\PHPUnit;

require_once('out_class.php');
require_once(__DIR__.'/Test.php');

require_once(__DIR__.'/../../Field/GetFieldable.php');
require_once(__DIR__.'/../../Field/SetFieldable.php');
require_once(__DIR__.'/../../Field/Fieldable.php');
require_once(__DIR__.'/../../Field/Field.php');
require_once(__DIR__.'/../../Url.php');

use ReflectionClass;
use Montage\Url;

class PathTest extends Test {

  protected $url = null;

  protected function setUp(){
  
    $current_url = 'http://example.com/current';
    $base_url = 'http://example.com/';
  
    $this->url = new TestUrl($current_url,$base_url);
  
  }//method

   /**
   *  test the build method
   */
  public function testBuild(){
  
    $test_list = array();
    $test_list[] = array(
      'in' => array(
        'http://app.com',
        array('foo','bar'),
        array('get' => 1)
      ),
      'out' => 'http://app.com/foo/bar/?get=1'
    );
    
    $this->assertCalls($this->url,'build',$test_list);
  
  }//method

  /**
   *  test the normalize method
   */
  public function testNormalize(){
  
    $test_list = array();
    $test_list[] = array(
      'in' => array(array('http://app.com','foo','bar',array('get' => 1))),
      'out' => array(
        'url' => 'http://app.com',
        'path' => array('foo','bar'),
        'query' => array('get' => 1)
      )
    );
    $test_list[] = array(
      'in' => array(array('foo','bar',array('get' => 1))),
      'out' => array(
        'url' => 'http://example.com',
        'path' => array('foo','bar'),
        'query' => array('get' => 1)
      )
    );
    $test_list[] = array(
      'in' => array(array('http://app.com',array('get' => 1))),
      'out' => array(
        'url' => 'http://app.com',
        'path' => array(),
        'query' => array('get' => 1)
      )
    );
    $test_list[] = array(
      'in' => array(array('http://app.com','foo','bar')),
      'out' => array(
        'url' => 'http://app.com',
        'path' => array('foo','bar'),
        'query' => array()
      )
    );
    $test_list[] = array(
      'in' => array(array('http://app.com',' ','')),
      'out' => array(
        'url' => 'http://app.com',
        'path' => array(' ',''),
        'query' => array()
      )
    );
  
    $this->assertCalls($this->url,'parse',$test_list);
  
  }//method

  /**
   *  test the url get method   
   */
  public function testGet(){
    
    $test_list = array();
    $test_list[] = array(
      'in' => array(''),
      'out' => 'http://example.com'
    );
    $test_list[] = array(
      'in' => array(' '),
      'out' => 'http://example.com'
    );
    
    $this->assertCalls($this->url,'get',$test_list);
  
  }//method

}//class

/**
 *  makes protected methods public for testing purposes
 *  
 *  if I had php >= 5.3.2 
 *  $rurl = new \ReflectionObject($this->url); 
 *  $rmethod = $rurl->getMethod('parse'); 
 *  $rmethod->setAccessible(true);
 *  
 *  @link http://stackoverflow.com/questions/249664/best-practices-to-test-protected-methods-with-phpunit  
 */
class TestUrl extends Url {

  public function normalize(array $args){ return parent::normalize($args); }//method
  
  public function build($url,array $path,array $query = array()){
    return parent::build($url,$path,$query);
  }//method

}//method
