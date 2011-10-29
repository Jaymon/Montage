<?php

$base = realpath(__DIR__.'/../src');
require_once($base.'/Crypt.php');

class CryptTest extends \PHPUnit_Framework_TestCase {

  public function testAll(){
  
    $text = 'this is my plain text';
    $key = 'this is the password';
    
    $c = new Crypt($key,$text);
    
    $plain1 = $c->decrypt();
    $cipher1 = $c->encrypt();
    \out::e($plain1,$cipher1);
    
    \out::e($c->info());
  
    
  }//method
  
}//class
