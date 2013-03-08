<?php

$base = realpath(__DIR__.'/../src');
require_once($base.'/Profile.php');

///require_once('/vagrant/public/out_class.php');

class ProfileTest extends \PHPUnit_Framework_TestCase {

  public function testProfile(){

    $p = new Profile();
    $p->start('foo');
    $p->start('bar');
    $p->start('foo');
    $p->stop(); // stop foo 2
    $p->stop(); // stop bar
    $p->start('che');
    $p->stop(); // stop che 1
    $p->stop(); // stop foo 1

    $map = $p->get();
    $this->assertTrue(isset($map['foo']['children']['bar']['children']['foo']));
    $this->assertTrue(isset($map['foo']['children']['che']));
  
  }//method
  
}//class
