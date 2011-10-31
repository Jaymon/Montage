<?php

$base = realpath(__DIR__.'/../src');
require_once($base.'/Crypt.php');

class CryptTest extends \PHPUnit_Framework_TestCase {

  public function testAll(){
  
    $text = 'this is my plain text';
    $key = 'this is the password';
    
    $c = new Crypt();
    
    $cipher = $c->encrypt($key,$text);
    
    $plain = $c->decrypt($key,$cipher);
    
    $this->assertSame($text,$plain);
    
  }//method
  
}//class
