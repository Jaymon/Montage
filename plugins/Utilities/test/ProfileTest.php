<?php

$base = realpath(__DIR__.'/../src');
require_once($base.'/Profile.php');

require_once('/vagrant/public/out_class.php');

class ProfileTest extends \PHPUnit_Framework_TestCase {

  /**
   *  @since  11-7-11
   */
  public function testProfile(){

    $p = new Profile();
    $p->start('foo');
    $p->start('bar');
    $p->start('foo');
    $p->stop();
    $p->stop();
    $p->stop();
    \out::i($p);
  
  }//method
  
}//class
