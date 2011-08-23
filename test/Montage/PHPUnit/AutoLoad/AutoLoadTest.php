<?php
namespace Montage\PHPUnit;
  
use PHPUnit\FrameworkTestCase;
use Montage\AutoLoad\Autoloader as MontageAutoLoader;

class AutoLoadTest extends FrameworkTestCase {
  
  /**
   *  make sure autoload registers and unregisters correctly
   */
  public function testRegisterUnregister(){
  
    $al_orig_count = count(spl_autoload_functions());
  
    $al = new AutoLoader();
    $al->register();
    
    $al_count = count(spl_autoload_functions());
    $this->assertSame(($al_orig_count + 1),$al_count);
    
    $al->unregister();
    
    $al_count = count(spl_autoload_functions());
    $this->assertSame($al_orig_count,$al_count);
    
    $al->register();
    
    $al_count = count(spl_autoload_functions());
    $this->assertSame(($al_orig_count + 1),$al_count);
    
    $al_map = array();
    $al_map[0] = $al;
    
    $al_map[0]->unregister();
    
    $al_count = count(spl_autoload_functions());
    $this->assertSame($al_orig_count,$al_count);
    
    ///\out::e(spl_autoload_functions());
  
  }//method
  
}//class

class Autoloader extends MontageAutoLoader {

  /**
   *  this is what will do the actual loading of each autoloader
   *  
   *  @param  string  $class_name
   */
  public function handle($class_name){
  
    
  
  
  }//method

}//class
