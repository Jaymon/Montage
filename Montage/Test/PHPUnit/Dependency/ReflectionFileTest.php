<?php
namespace Montage\Test\PHPUnit;

use ReflectionClass;
use Montage\Dependency\ReflectionFile;
use out;

require_once('out_class.php');
  
require_once(__DIR__.'/../Test.php');
require_once(__DIR__.'/../../../Path.php');

require_once(__DIR__.'/../../../Dependency/Reflection.php');
require_once(__DIR__.'/../../../Dependency/ReflectionFile.php');

class ClassesTest extends Test {

  public function testFindClasses(){
  
    $c = new Classes();
    
    $c->findClasses(
      '<'.'?php
      
      namespace foo {
      
        class foo extends \bang\boom\pow,che\bar {}
        
      }
      
      namespace bar {
      
        use foo;
      
        class bar implements \Serializable,\Countable {}
      
      }
      
      ?'.'>'
    );
    return;
    
    
    $c->findClasses(
      '<'.'?php
      
      use che;
      
      class foo extends \bang\boom\pow,che\bar {}
      
      ?'.'>'
    );
    return;
    
    $c->findClasses(
      '<'.'?php
      
      use che;
      
      class foo extends \bang\boom\pow {}
      
      ?'.'>'
    );
    return;
    
    $c->findClasses(
      '<'.'?php
      
      use che;
      
      class foo extends che\bar {}
      
      ?'.'>'
    );
    return;
    
    $c->findClasses(
      '<'.'?php
      
      class foo extends bar {}
      
      ?'.'>'
    );
    return;
    
    $c->findClasses(
      '<'.'?php
      
      use Montage\Classes as foo,foo\bar as baz;
      
      ?'.'>'
    );
    return;
    
    $c->findClasses(
      '<'.'?php
      
      use Montage\Classes as foo;
      
      ?'.'>'
    );
    return;
    
    $c->findClasses(
      '<'.'?php
      
      use Montage\Classes,foo\bar;
      
      ?'.'>'
    );
    
    return;
    
    
    $c->findClasses(
      '<'.'?php
      
      use Montage\Classes;
      
      ?'.'>'
    );
    
    return;
    
    $c->findClasses(
      '<'.'?php
      namespace happy;
      
      use \Montage\Classes;
      use out;
      use \Montage\Path as Foo;
      
      class Bar extends Foo implements \Countable {}//class
      
      ?'.'>'
    );
    
    // use \foo as F,\Bar as B;
  
  
  }//method

}//class
