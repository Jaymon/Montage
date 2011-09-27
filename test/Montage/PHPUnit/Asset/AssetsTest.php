<?php
namespace Montage\PHPUnit;
  
use PHPUnit\FrameworkTestCase;
use Montage\Asset\Assets;

class AssetsTest extends FrameworkTestCase {
  
  /**
   *  test adding a path
   */
  public function testAddPath(){
    
    $src_path = $this->getFixturePath('Asset');
    $dest_path = $this->getTempPath('Asset');
    
    \out::e($dest_path);
    \out::e($src_path);
    
    $assets = new Assets();
    $assets->setSrcPaths(array($src_path));
    $assets->setDestPath($dest_path,'assets');
    
    $assets->handle();
  
  }//method

}//class

