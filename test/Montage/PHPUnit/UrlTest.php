<?php
namespace Montage\Test\PHPUnit;

use ReflectionClass;
use Montage\Url;
use PHPUnit\TestCase;

class UrlTest extends TestCase {

  protected $url = null;

  protected function setUp(){
  
    $current_url = 'http://example.com/current';
    $base_url = 'http://example.com/';
  
    $this->url = new TestUrl($current_url,$base_url);
  
  }//method

  /**
   *  @since  12-17-11
   */
  public function testSet(){
  
    $this->url->set('foo',array('foo','bar',array('baz' => 'che')));
  
    $this->assertEquals('http://example.com/foo/bar/?baz=che',$this->url->getFoo());
    $this->assertEquals(
      'http://example.com/foo/bar/happy/?baz=che&duh=dah',
      $this->url->getFoo('happy',array('duh' => 'dah'))
    );
  
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
    $test_list[] = array(
      'in' => array(
        'http://app.com/foo/bar',
        array(),
        array('get' => 1)
      ),
      'out' => 'http://app.com/foo/bar/?get=1'
    );
    $test_list[] = array(
      'in' => array(
        'http://app.com/?get=2',
        array(),
        array('get' => 1)
      ),
      'out' => 'http://app.com/?get=1'
    );
    $test_list[] = array(
      'in' => array(
        'http://app.com/?get=2',
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
        'default_url' => false,
        'path' => array('foo','bar'),
        'query' => array('get' => 1)
      )
    );
    $test_list[] = array(
      'in' => array(array('foo','bar',array('get' => 1))),
      'out' => array(
        'url' => 'http://example.com',
        'default_url' => true,
        'path' => array('foo','bar'),
        'query' => array('get' => 1)
      )
    );
    $test_list[] = array(
      'in' => array(array('http://app.com',array('get' => 1))),
      'out' => array(
        'url' => 'http://app.com',
        'default_url' => false,
        'path' => array(),
        'query' => array('get' => 1)
      )
    );
    $test_list[] = array(
      'in' => array(array('http://app.com','foo','bar')),
      'out' => array(
        'url' => 'http://app.com',
        'default_url' => false,
        'path' => array('foo','bar'),
        'query' => array()
      )
    );
    $test_list[] = array(
      'in' => array(array('http://app.com',' ','')),
      'out' => array(
        'url' => 'http://app.com',
        'default_url' => false,
        'path' => array(' ',''),
        'query' => array()
      )
    );
  
    $this->assertCalls($this->url,'normalize',$test_list);
  
  }//method

  /**
   *  test the url get method   
   */
  public function testGet(){
    
    $test_list = array();
    $test_list[] = array(
      'in' => array(''),
      'out' => 'http://example.com/',
      'out2' => '/'
    );
    $test_list[] = array(
      'in' => array(' '),
      'out' => 'http://example.com/',
      'out2' => '/'
    );
    $test_list[] = array(
      'in' => array('http://app.com',array('get' => 2)),
      'out' => 'http://app.com/?get=2',
      'out2' => 'http://app.com/?get=2'
    );
    $test_list[] = array(
      'in' => array('http://app.com','foo','bar',array('get' => 2)),
      'out' => 'http://app.com/foo/bar/?get=2',
      'out2' => 'http://app.com/foo/bar/?get=2'
    );
    $test_list[] = array(
      'in' => array('http://app.com','foo','bar'),
      'out' => 'http://app.com/foo/bar/',
      'out2' => 'http://app.com/foo/bar/'
    );
    $test_list[] = array(
      'in' => array('foo/bar'),
      'out' => 'http://example.com/foo/bar/',
      'out2' => '/foo/bar/'
    );
    $test_list[] = array(
      'in' => array('foo','bar'),
      'out' => 'http://example.com/foo/bar/',
      'out2' => '/foo/bar/'
    );
    $test_list[] = array(
      'in' => array('http://localhost:8080','foo','bar'),
      'out' => 'http://localhost:8080/foo/bar/',
      'out2' => 'http://localhost:8080/foo/bar/'
    );
    $test_list[] = array(
      'in' => array('http://user:pass@localhost:8080','foo','bar'),
      'out' => 'http://user:pass@localhost:8080/foo/bar/',
      'out2' => 'http://user:pass@localhost:8080/foo/bar/'
    );
    $test_list[] = array(
      'in' => array('http://user:pass@app.com','foo',array('bar' => 1)),
      'out' => 'http://user:pass@app.com/foo/?bar=1',
      'out2' => 'http://user:pass@app.com/foo/?bar=1'
    );
    
    $this->assertCalls($this->url,'get',$test_list);
  
    $this->url->useDomain(false);
  
    foreach($test_list as $key => $test_map){
    
      $test_map['out'] = $test_map['out2'];
      $this->assertCall($this->url,'get',$test_map,$key);
    
    }//foreach
  
  }//method
  
  /**
   *  test the url get method   
   */
  public function testGetCurrent(){
  
    $test_list = array();
    $test_list[] = array(
      'in' => array(''),
      'out' => 'http://example.com/current/',
      'out2' => '/current/'
    );
    $test_list[] = array(
      'in' => array(' '),
      'out' => 'http://example.com/current/',
      'out2' => '/current/'
    );
    $test_list[] = array(
      'in' => array(array('get' => 2)),
      'out' => 'http://example.com/current/?get=2',
      'out2' => '/current/?get=2'
    );
    $test_list[] = array(
      'in' => array('foo/bar'),
      'out' => 'http://example.com/current/foo/bar/',
      'out2' => '/current/foo/bar/'
    );
    $test_list[] = array(
      'in' => array('foo','bar'),
      'out' => 'http://example.com/current/foo/bar/',
      'out2' => '/current/foo/bar/'
    );
    
    $this->assertCalls($this->url,'getCurrent',$test_list);
    
    $this->url->useDomain(false);
  
    foreach($test_list as $key => $test_map){
    
      $test_map['out'] = $test_map['out2'];
      $this->assertCall($this->url,'getCurrent',$test_map,$key);
    
    }//foreach
  
  }//method
  
  /**
   *  this is just to make sure that urls are created right when there is a base url
   *  that also includes a path and url is set up to use absolute paths without domains   
   *
   *  @since  11-9-11   
   */
  public function testRelativeBase(){
  
    $current_url = 'http://example.com/foo/bar/';
    $base_url = 'http://example.com/foo/bar/';
  
    $url = new TestUrl($current_url,$base_url,false);
  
    $ret = $url->get('/foo/bar');
    $this->assertEquals('/foo/bar',$ret);
  
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
