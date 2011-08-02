<?php
namespace Montage\PHPUnit {
  
  use PHPUnit\FrameworkTestCase;
  use Montage\Dependency\Reflection;
  use Montage\Dependency\ReflectionContainer;
  
  class ContainerTest extends FrameworkTestCase {
  
    protected $container = null;
  
    public function setUp(){
    
      $reflection = new Reflection();
      $reflection->addFile(__FILE__);
      
      $this->container = new ReflectionContainer($reflection);
      
      ///out::e(spl_autoload_functions());
    
    }//method
    
    /**
     *  make sure the DIC will create class instances for type hinted constructor params
     */
    public function testgetInstanceNoParams(){
    
      $instance = $this->container->getInstance('Montage\Test\Fixtures\Dependency\Foo');
      $this->assertTrue($instance instanceof \Montage\Test\Fixtures\Dependency\Bar);
      $this->assertTrue($instance->che instanceof \Che);
    
    }//method
    
    /**
     *  make sure you can set fields and those fields will be used for the class's __construct()
     *  params
     */ 
    public function testgetInstanceFieldParams(){
    
      $this->container->setField('one',__FUNCTION__);
      $this->container->setField('two',__METHOD__);
    
      $instance = $this->container->getInstance('Baz');
      $this->assertTrue($instance instanceof \Baz);
      
      $this->assertEquals(__FUNCTION__,$instance->one);
      $this->assertEquals(__METHOD__,$instance->two);
      $this->assertNull($instance->three);
      
    }//method
    
    /**
     *  setter injection should add dependencies through methods
     *
     *  @since  6-15-11
     */
    public function testSetterInjection(){
    
      $instance = $this->container->getInstance('Montage\Test\Fixtures\Dependency\FooBar');
      $this->assertTrue($instance instanceof \Montage\Test\Fixtures\Dependency\FooBar);
      $this->assertTrue($instance->che instanceof \Che);
      
    }//method
    
    /**
     *  make sure that trying to find an instance with divergent children fails
     *
     *  @since  6-17-11
     */
    public function testMultiChoice(){
    
      $this->setExpectedException('\LogicException');
      $instance = $this->container->getInstance('Montage\Test\Fixtures\Dependency\A');
    
    }//method
  
    /**
     *  make sure that trying to resolve a divergent dependency fails
     *
     *  @since  6-17-11
     */
    public function testMultiChoiceDependency(){
    
      $this->setExpectedException('\LogicException');
      $instance = $this->container->getInstance('Montage\Test\Fixtures\Dependency\AA');
    
    }//method
  
  }//class
  
}//namespace

namespace Montage\Test\Fixtures\Dependency {

  class Bar extends Foo {
  
    public $che = null;
  
    public function __construct(\Che $che){
    
      $this->che = $che;
    
    }//method
    
  }//class

  class Foo {

    public function __construct($one,$two){}//method
  
  }//class
  
  class FooBar {
  
    public $che = null;
  
    public function setChe(\Che $che){ $this->che = $che; }//method
  
  }//class
  
  class A {}//method
  class B extends A {}//method
  class C extends A {}//method
  
  class AA {
    public function __construct(\Montage\Test\Fixtures\Dependency\A $a){}//method
  }//method

}//namespace

// http://us.php.net/manual/en/language.namespaces.definitionmultiple.php
namespace {
  
  class Baz {
  
    public $one = '';
    public $two = null;
    public $three = 0;
  
    public function __construct($one,$two,$three = null){
    
      $this->one = $one;
      $this->two = $two;
      $this->three = $three;
    
    }//method
  
  }//class
  
  class Che {
  
    public function __construct(){}//method
  
  }//class
  
}//namespace
