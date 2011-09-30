<?php
namespace Montage\PHPUnit;
  
use PHPUnit\FrameworkTestCase;

use Montage\Asset\Assets;
use Montage\Asset\Asset;
use Montage\Asset\FrameworkAssets;

class AssetsTest extends FrameworkTestCase {
  
  /**
   *  test adding a path
   */
  public function testAddPath(){
    
    $foo_src_path = $this->getFixturePath('Asset','Foo','assets');
    $bar_src_path = $this->getFixturePath('Asset','Bar','assets');
    
    $foo_assets = new FooAssets();
    $foo_assets->addSrcPath($foo_src_path);
    
    $bar_assets = new BarAssets();
    $bar_assets->addSrcPath($bar_src_path);
    
    $framework_assets = new FrameworkAssets();
    $framework_assets->setSrcPaths(array($foo_src_path,$bar_src_path)
    
    
    \out::e($foo_src_path);
    
    return;
    
    $src_path = $this->getFixturePath('Asset');
    
    
    $src_path = $this->getFixturePath('Asset');
    $dest_path = $this->getTempPath('Asset');
    
    ///\out::e($src_path);
    ///\out::e($dest_path);
    
    $assets = new Assets();
    $assets->setSrcPaths(array($src_path));
    $assets->setDestPath($dest_path,'blah');
    
    $assets->handle();
  
  }//method

}//class

class FooAssets extends Assets {

  public function getExtension(){ return 'css'; }//method

}//class

class BarAssets extends Assets {

  public function getExtension(){ return 'js'; }//method

}//class
