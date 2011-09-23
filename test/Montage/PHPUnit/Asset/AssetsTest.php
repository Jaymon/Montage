<?php
namespace Montage\PHPUnit;
  
use PHPUnit\FrameworkTestCase;
use Montage\Asset\Assets;

class AssetsTest extends FrameworkTestCase {
  
  /**
   *  test adding a path
   */
  public function testAddPath(){
    
    $path = $this->getFixturePath('Asset');
    $to_path = $this->getTempPath('Asset');
    
    \out::e($to_path);
    \out::e($path);
    
    $assets = new Assets();
    $assets->setToPath($to_path);
    
    $assets->addPath($path);
  
  }//method

}//class

