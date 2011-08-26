<?php
namespace Montage\PHPUnit;
  
use PHPUnit\FrameworkTestCase;
use Montage\Event\Dispatch;
use Montage\Event\Event;

class EventTest extends FrameworkTestCase {
  
  protected $dispatch = null;
  
  public function setUp(){
  
    $framework = $this->getFramework();
    $container = $framework->getContainer();
    $this->dispatch = $container->getInstance('Montage\Event\Dispatch');
  
  }//method
  
  /**
   *  when create() is used, make sure that uri and method params override any actual values
   */
  public function testListen(){
  
    $closure = function(Event $event){
    
      $test = $event->getField('test');
      $test->assertSame('test',$event->getName());
      $test->assertFalse($event->isPersistent());
      
      $event->setField('foo',1);
    
    };
  
    $this->dispatch->listen('test',$closure);
    
    $event = new Event('test',array(),false);
    $event->setField('test',$this);
    
    $event = $this->dispatch->broadcast($event);
    
    $this->assertEquals(1,$event->getField('foo'));
  
  }//method
  
  public function testClosure(){
  
    $closure = function(Event $event){ $event->setField('event',1); };
    
    $event_name = __METHOD__;
    
    $this->dispatch->listen($event_name,$closure);
    $this->assertTrue($this->dispatch->has($event_name));
    
  
  
  
  
  
  }//method
  
}//class
