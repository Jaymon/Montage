<?php


include('out_class.php');

spl_autoload_register(function($class_name){

  out::b('first');
  out::e($class_name);
  include(__DIR__.'/bar.php');
  return true;

});
spl_autoload_register(function($class_name){

  out::b('second');
  out::e($class_name);
  return false;

});

$class_name = 'foo\bar';
///$a = new \foo\bar();

///spl_autoload_call($class_name);
class_exists($class_name);

///$a = new ReflectionClass('\foo\bar');

///$a = new foo\bar();
