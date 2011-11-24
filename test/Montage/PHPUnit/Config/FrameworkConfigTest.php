<?php
namespace Montage\PHPUnit;
  
use PHPUnit\FrameworkTestCase;
use Montage\Config\FrameworkConfig;
use Path;

class FrameworkConfigTest extends FrameworkTestCase {
  
  protected $config = null;
  
  /**
   *  this will be called before each test is run, so override if you want to do pre 
   *  individual test stuff
   */
  protected function setUp(){
  
    $this->config = new FrameworkConfig();
  
  }//method
  
  /**
   *  make sure implemented config files load properly
   */
  public function testLoadConfigFiles(){
  
    $path = $this->getFixturePath('Config');
    $this->config->addPath($path);
  
    foreach(array('php','ini') as $ext){
      
      $this->config->load(sprintf('config.%s',$ext));
      
      $field_map = $this->config->getFields();
      $this->assertFieldMap($field_map);
      
    }//foreach
  
  }//method
  
  /**
   *  since all the config files should have the same values, this just makes sure
   *  all the formats return the same values            
   */     
  protected function assertFieldMap(array $field_map){
  
    $this->assertCount(3,$field_map);
    $this->assertEquals('foo',$field_map['foo']);
    $this->assertEquals(array('b','a','r'),$field_map['bar']);
    $this->assertEquals(1234,$field_map['che']);
  
  }//method

}//class

