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
   *  test that an event can be listened for and notified
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
  
  /**
   *  test adding and removing a closure
   */
  public function testClosure(){
  
    $closure = function(Event $event){ $event->setField('event',1); };
    
    $event_name = __METHOD__;
    
    $this->dispatch->listen($event_name,$closure);
    $this->assertTrue($this->dispatch->has($event_name));
    
    $this->assertGreaterThan(0,count($this->dispatch->get($event_name)));
    
    $this->assertTrue($this->dispatch->kill($event_name));
  
    $this->assertFalse($this->dispatch->has($event_name));
    $this->assertEmpty($this->dispatch->get($event_name));
  
  }//method
  
  /**
   *  make sure persist works as expected
   */
  public function testPersist(){
  
    $closure = function(Event $event){ $event->setField('event',1); };
    $event_name = __METHOD__;
  
    $event = new Event($event_name,array(),true);
    $event = $this->dispatch->broadcast($event);
  
    $this->assertFalse($event->hasField('event'));
    
    $this->dispatch->listen($event_name,$closure);
  
    $this->assertEquals(1,$event->getField('event'));
  
  }//method
  
}//class
