<?php
namespace Montage\PHPUnit;
  
use PHPUnit\FrameworkTestCase;
use Montage\Request\Request;

class RequestTest extends FrameworkTestCase {
  
  /**
   *  when create() is used, make sure that uri and method params override any actual values
   */
  public function testCreate(){
  
    $framework = $this->getFramework();
    $container = $framework->getContainer();
    $request_class_name = $container->getClassName('montage\Request\Requestable');
    
    $request = call_user_func(
      array($request_class_name,'create'),
      '/foo/bar/', 
      'GET', 
      array(), 
      array(),
      array(),
      array(),
      array()
    );
    
    $this->assertSame('http://localhost/foo/bar/',$request->getUrl());
    $this->assertSame('http://localhost',$request->getBase());
    
    ///\out::i($request);
    ///\out::e($request->getUrl(),$request->getBase());
  
  }//method
  
  public function testSetBase(){
  
    $instance = new Request();
  
    $list = array();
    $list[] = array(
      'in' => array(
        'setBase' => array('localhost:8080/foo/bar')
      ),
      'out' => array(
        'getBase' => 'localhost:8080/foo/bar',
        'getHost' => 'localhost',
        'getPath' => ''
      )
    );
    
    foreach($list as $map){
    
      foreach($map['in'] as $method => $params){
      
        call_user_func_array(array($instance,$method),$params);
      
      }//foreach
      
      foreach($map['out'] as $method => $params){
      
        $ret = call_user_func_array(array($instance,$method));
        $this->assertSame($params,$ret);
      
      }//foreach
    
    }//foreach
  
    /* 
    test localhost:8080/foo/bar
    test localhost
    test http://localhost
    
    and make sure that when the base has something like /foo/bar that all the methods return the right stuff
    like $request->getBaseUrl() and $request->getBase()
    */
  
  }//method
  
}//class
