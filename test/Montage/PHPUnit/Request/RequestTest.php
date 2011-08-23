<?php
namespace Montage\PHPUnit;
  
use PHPUnit\FrameworkTestCase;

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
  
}//class
