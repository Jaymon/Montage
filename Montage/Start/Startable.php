<?php
/**
 *  all start classes should use this interface 
 *  
 *  any start class in Montage will call a method named handle(). This method is not
 *  defined in this interface because it is a magic method similar to the Controller
 *  handle* methods. Meaning you can pass in any objects you want to it (thus changing
 *  the signature, and making it impossible to be defined in an interface) and those
 *  objects will be automatically resolved and passed in for you.
 *  
 *  @example
 *    class DevStart implements Montage\Start\Startable {
 *      public function handle(Foo $foo,Bar $bar){}//method  
 *    }       
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-17-11
 *  @package montage
 *  @subpackage Start  
 ******************************************************************************/      
namespace Montage\Start;

interface Startable {}//interface
