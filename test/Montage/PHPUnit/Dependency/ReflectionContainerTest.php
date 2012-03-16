<?php
/**
 *  this class doesn't use any of the PHPUnit Montage plugin stuff because all
 *  that stuff relies on the Container working withut problems, so you can't test
 *  the container using it because if the container doesn't work, then the test
 *  won't load   
 *
 ******************************************************************************/
namespace Montage\PHPUnit {
  
  $base = realpath(__DIR__.'/../../../../');
  
  require_once($base.'/plugins/Utilities/src/Path.php');
  
  require_once($base.'/src/Montage/Field/GetFieldable.php');
  require_once($base.'/src/Montage/Field/SetFieldable.php');
  require_once($base.'/src/Montage/Field/Fieldable.php');
  require_once($base.'/src/Montage/Field/Field.php');
  
  require_once($base.'/src/Montage/Cache/Cacheable.php');
  require_once($base.'/src/Montage/Cache/Cache.php');
  require_once($base.'/src/Montage/Cache/ObjectCache.php');
  require_once($base.'/src/Montage/Cache/PHPCache.php');
  
  require_once($base.'/src/Montage/Reflection/ReflectionFile.php');
  require_once($base.'/src/Montage/Reflection/ReflectionFramework.php');
  require_once($base.'/src/Montage/Reflection/ReflectionDocBlock.php');
  
  require_once($base.'/src/Montage/Annotation/Annotation.php');
  require_once($base.'/src/Montage/Annotation/ParamAnnotation.php');
  
  require_once($base.'/src/Montage/Dependency/Containable.php');
  require_once($base.'/src/Montage/Dependency/Container.php');
  require_once($base.'/src/Montage/Dependency/ReflectionContainer.php');
  
  require_once('out_class.php');
  
  ///use PHPUnit\FrameworkTestCase;
  use Montage\Reflection\ReflectionFramework;
  use Montage\Dependency\ReflectionContainer;
  
  class ContainerTest extends \PHPUnit_Framework_TestCase {
  
    protected $reflection = null;
  
    protected $container = null;
  
    public function setUp(){
    
      $this->reflection = new ReflectionFramework();
      $this->reflection->addFile(__FILE__);
      
      $this->container = new ReflectionContainer($this->reflection);
      
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
      $this->assertFalse($instance->che instanceof \Che);
      $this->assertTrue($instance->bong instanceof \Bong);
      
    }//method
    
    /**
     *  setter injection should add dependencies through public parameters
     *
     *  @since  6-15-11
     */
    public function testParamInjection(){
    
      $instance = $this->container->getInstance('Montage\Test\Fixtures\Dependency\ParamInjected');

      $this->assertTrue($instance instanceof \Montage\Test\Fixtures\Dependency\ParamInjected);
      $this->assertTrue($instance->che instanceof \Che);
      $this->assertTrue(\Montage\Test\Fixtures\Dependency\ParamInjected::$bong instanceof \Bong);
      $this->assertNull($instance->che2);
      $this->assertTrue($instance->c instanceof \Montage\Test\Fixtures\Dependency\C);
      
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
    
    /**
     *  test to make sure the correct class is returned through multiple inheritance
     *
     *  @since  5-29-11     
     */
    public function testCorrectChoice(){
    
      ///\out::i($this->container->getInstance('MONTAGE\DEPENDENCY\REFLECTION'));
    
      $class_name = $this->container->getClassName('\B');
      $this->assertEquals('AA',$class_name);
      
      $class_name = $this->container->getClassName('\A');
      $this->assertEquals('AA',$class_name);
      
      $class_name = $this->container->getClassName('\C');
      $this->assertEquals('AA',$class_name);
      
      $class_name = $this->container->getClassName('\AA');
      $this->assertEquals('AA',$class_name);
    
    }//method
    
    public function testOnCreate(){
    
      $this->container->onCreate(
        '\A',
        function($container,array $params = array()){
        
          $params['bar'] = 2;
          return $params;
        
        }
      );
      
      $instance = $this->container->getInstance('\B',array('foo' => 1));
      $this->assertType('\AA',$instance);
      $this->assertSame(1,$instance->foo);
      $this->assertSame(2,$instance->bar);
    
    }//method
    
    /**
     *  makes sure the container is caching set* and inject* methods properly
     *
     *  @since  9-7-11     
     */
    public function testInjectCache(){
    
      $instance = $this->container->getInstance('Montage\Test\Fixtures\Dependency\FooBar');      
      
      $class_map = $this->reflection->getClass('Montage\Test\Fixtures\Dependency\FooBar');
      
      $this->assertArrayHasKey('info',$class_map);
      $this->assertArrayHasKey('created',$class_map['info']);
      $this->assertEquals(2,count($class_map['info']['created']));
    
    }//method
    
    /**
     *  makes sure the container is caching set* and inject* methods properly from
     *  inherited classes     
     *
     *  @since  9-7-11     
     */
    public function testInjectCache2(){
    
      $instance = $this->container->getInstance('Montage\Test\Fixtures\Dependency\BarBaz');      
      
      $class_map = $this->reflection->getClass('Montage\Test\Fixtures\Dependency\BarBaz');
      
      $this->assertEquals(4,count($class_map['info']['created']));
      
      $method_name_list = array('setFoo','injectBar','setChe','injectBong');
      foreach($class_map['info']['created'] as $map){
      
        $this->assertTrue(in_array($map['name'],$map));
      
      }//foreach
      
    }//method
    
    /**
     *  make sure static params get created before the class is created     
     *
     *  @since  1-12-12
     */
    public function testStaticParamInject(){
    
      $instance = $this->container->getInstance(
        '\\Montage\\Test\\Fixtures\\Dependency\\ParamStaticInjection'
      );
      
      $this->assertTrue($instance instanceof \Montage\Test\Fixtures\Dependency\ParamStaticInjection);
      $this->assertTrue($instance->che instanceof \Che);
      $this->assertTrue(\Montage\Test\Fixtures\Dependency\ParamStaticInjection::$bong instanceof \Bong);
    
    }//method
  
  }//class
  
}//namespace

namespace Montage\Test\Fixtures\Dependency {

  class ParamStaticInjection {
  
    /**
     *  @var  \Bong this is static for no reason
     */
    public static $bong = null;
  
    /**
     *  @var  \Che
     */
    public $che = null;
    
    public function __construct(){
  
      if(!(self::$bong instanceof \Bong)){
      
        throw new \RuntimeException('bong should be created');
      
      }//if  
    
    }//method
  
  }//class

  class ParamInjected {
  
    /**
     *  @var  \Bong this is static for no reason
     */
    public static $bong = null;
  
    /**
     *  @var  \Che
     */
    public $che = null;
    
    /**
     *  this should fail the param injection
     *         
     *  @var  string|\Che
     */
    public $che2 = null;
    
    /**
     *  @var  string  a string value that isn't a class
     */
    public $not_a_class = null;
  
    /**
     *  this should get the full namespaced C class
     *     
     *  @var  C
     */
    public $c = null;
  
  }//class

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
    public $bong = null;
  
    public function setChe(\Che $che){ $this->che = $che; }//method
    public function injectBong(\Bong $bong){ $this->bong = $bong; }//method
  
  }//class
  
  class FooBar2 {
  
    public $che = null;
    public $bong = null;
  
    public function setChe(\Che $che){ $this->che = $che; }//method
    public function injectBong(\Bong $bong){ $this->bong = $bong; }//method
  
  }//class
  
  class BarBaz extends FooBar2 {
  
    public $foo = null;
    public $bar = null;
  
    public function setFoo(Foo $v){ $this->foo = $v; }//method
    public function injectBar(Bar $v){ $this->bar = $v; }//method
  
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
  
  class Bong {
  
    public function __construct(){}//method
  
  }//class
  
  class A {}//method
  class B extends A {}//method
  class C extends B {}//method
  
  class AA extends C {
  
    public $foo = 0;
    public $bar = 0;
  
    public function __construct($foo,$bar){
    
      $this->foo = $foo;
      $this->bar = $bar;
    
    }//method
    
  }//method
  
}//namespace
