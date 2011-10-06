<?php

$base = realpath(__DIR__.'/../src');
require_once($base.'/ParseOpt.php');

class ParseOptTest extends \PHPUnit_Framework_TestCase {

  public function testParseLong(){
  
    $argv = array();
    $argv[] = '--one=this is a string';
    
    $po = new ParseOpt($argv);
    $this->assertTrue($po->isField('one','this is a string'));
    
    $argv[] = '--two=happy are we';
    
    $po = new ParseOpt($argv);
    $this->assertTrue($po->isField('one','this is a string'));
    $this->assertTrue($po->isField('two','happy are we'));
    
    $this->assertEmpty($po->getList());
    
  }//method
  
  public function testParseShort(){
  
    $argv = array();
    $argv[] = '-p';
    $argv[] = 'blah';
    
    $po = new ParseOpt($argv);
    
    $this->assertTrue($po->isField('p','blah'));
    
    $argv[] = '-a';
    $argv[] = 'one two three';
    
    $po = new ParseOpt($argv);
    $this->assertTrue($po->isField('p','blah'));
    $this->assertTrue($po->isField('a','one two three'));
    
    $this->assertEmpty($po->getList());
    
  }//method
  
  public function testParseList(){
  
    $argv = array();
    $argv[] = 'one';
    $argv[] = 'two';
    
    $po = new ParseOpt($argv);
    
    $this->assertEmpty($po->getFields());
    $this->assertEquals(2,count($po->getList()));
    
  }//method
  
  public function testRequiredFailed(){
  
    $this->setExpectedException('\InvalidArgumentException');
  
    $argv = array();
    $argv[] = 'one';
    $argv[] = 'two';
    
    $po = new ParseOpt($argv,array('blah' => null));
    
  }//method
  
  public function testRequiredSucceed(){
  
    ///$this->setExpectedException('\InvalidArgumentException');
  
    $argv = array();
    $argv[] = 'one';
    $argv[] = 'two';
    
    $po = new ParseOpt($argv,array('blah' => 'foo'));
    
    $this->assertTrue($po->isField('blah','foo'));
    
  }//method
  
}//class
