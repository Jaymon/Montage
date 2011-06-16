<?php
namespace Montage\Test\PHPUnit {
  
  use Montage\Dependency\Reflection;
  use Montage\Dependency\Container;
  use out;
  
  require_once('out_class.php');
  
  require_once(__DIR__.'/../Test.php');
  require_once(__DIR__.'/../../../Path.php');
  require_once(__DIR__.'/../../../Field.php');
  
  require_once(__DIR__.'/../../../Dependency/Container.php');
  require_once(__DIR__.'/../../../Dependency/Reflection.php');
  require_once(__DIR__.'/../../../Dependency/ReflectionFile.php');
  require_once(__DIR__.'/../../../Dependency/Injector.php');
  
  class ContainerTest extends Test {
  
    protected $container = null;
  
    public function setUp(){
    
      ///out::e($this->container);
    
      $reflection = new Reflection();
      $reflection->addFile(__FILE__);
      ///out::i($reflection);
      
      $this->container = new Container($reflection);
      
      ///out::e(spl_autoload_functions());
    
    }//method
    
    public function tearDown(){
    
      // we need to unregister the autoloader...
      $reflection = $this->container->getReflection();
      $reflection->__destruct();
    
    }//method
    
    /**
     *  make sure the DIC will create class instances for type hinted constructor params
     */
    public function testFindInstanceNoParams(){
    
      $instance = $this->container->findInstance('Montage\Test\Fixtures\Dependency\Foo');
      $this->assertTrue($instance instanceof \Montage\Test\Fixtures\Dependency\Bar);
      $this->assertTrue($instance->che instanceof \Che);
    
    }//method
    
    /**
     *  make sure you can set fields and those fields will be used for the class's __construct()
     *  params
     */ 
    public function testFindInstanceFieldParams(){
    
      $this->container->setField('one',__FUNCTION__);
      $this->container->setField('two',__METHOD__);
    
      $instance = $this->container->findInstance('Baz');
      $this->assertTrue($instance instanceof \Baz);
      
      $this->assertEquals(__FUNCTION__,$instance->one);
      $this->assertEquals(__METHOD__,$instance->two);
      $this->assertNull($instance->three);
      
    }//method
    
    /**
     *  setter injection should add dependencies if they are there
     *
     *  @since  6-15-11
     */
    public function testSetterInjection(){
    
      $instance = $this->container->findInstance('Montage\Test\Fixtures\Dependency\FooBar');
      $this->assertTrue($instance instanceof \Montage\Test\Fixtures\Dependency\FooBar);
      $this->assertNull($instance->che);
      
      // creat che...
      $che = $this->container->findInstance('Che');
      
      // now che should be there if I get a new instance...
      $instance = $this->container->getNewInstance('Montage\Test\Fixtures\Dependency\FooBar');
      $this->assertTrue($instance->che instanceof \Che);
      
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
