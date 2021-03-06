<?php
namespace Montage\PHPUnit;
  
use PHPUnit\FrameworkTestCase;

use Path;
use Montage\Asset\Assets;
use Montage\Asset\Asset;
use Montage\Asset\FrameworkAssets;

class AssetsTest extends FrameworkTestCase {
  
  /**
   *  test that dependencies are resolved correctly
   *  
   *  @since  12-29-11
   */
  public function testDependencies(){
  
    $dest_path = $this->getTempPath('Asset');
    $foo_path = $this->getFixturePath('Asset','Foo','assets');
    $che_path = $this->getFixturePath('Asset','Che','assets');
  
    $framework_assets = new FrameworkAssets();
    $framework_assets->setDestPath($dest_path);
    $framework_assets->setSrcPaths(array($che_path,$foo_path));
  
    $che_assets = new CheAssets();
    $che_assets->addSrcPath($che_path);
    $framework_assets->add($che_assets);
    
    $foo_assets = new FooAssets();
    $foo_assets->addSrcPath($foo_path);
    $framework_assets->add($foo_assets);
    
    $framework_assets->handle();
    
    ///$str = $framework_assets->render('css');
    ///\out::e($framework_assets->get());
    ///\out::i($framework_assets);
    
    $assets_map = $framework_assets->get();
    $assets_map = $assets_map['css'];
    $assets_list = array_keys($assets_map);
    $this->assertEquals('Foo',$assets_list[0]);
    $this->assertEquals('Che',$assets_list[1]);
  
  }//method
  
  /**
   *  make sure Asset rendering works
   *
   *  @since  10-1-11
   */
  public function testRender(){
  
    $dest_path = $this->getTempPath('Asset');
    $src_path = $this->getFixturePath('Asset','Che','assets');
    
    $assets = new FooAssets();
    $assets->addSrcPath($src_path);
    $assets->setDestPath($dest_path);
    $assets->handle();
    
    $str = $assets->render('1');
    $this->assertNotEmpty($str);
    
    $framework_assets = new FrameworkAssets();
    $framework_assets->setDestPath($dest_path);
    $framework_assets->setSrcPaths(array($src_path));
    $framework_assets->add($assets);
    
    $framework_assets->handle();
    
    $str = $framework_assets->render('css');
    $this->assertNotEmpty($str);
  
    $str = $framework_assets->render('1');
    $this->assertNotEmpty($str);
  
  }//method
  
  public function testAssets(){
  
    $dest_path = $this->getTempPath('Asset');
    $src_path = $this->getFixturePath('Asset','Che','assets');
    $prefix_path = new Path('bart','lisa','homer');
    
    $assets = new FooAssets();
    $assets->addSrcPath($src_path);
    
    $framework_assets = new FrameworkAssets();
    $framework_assets->setDestPath($dest_path);
    $framework_assets->setPrefixPath($prefix_path);
    $framework_assets->setSrcPaths(array($src_path));
    $framework_assets->add($assets);
    
    $framework_assets->handle();
    
    $framework_asset_map = $framework_assets->get();
  
    $this->assertEquals(1,count($framework_asset_map['css']));
    
    foreach(new \FlattenArrayIterator($framework_asset_map) as $asset){
    
      $asset_map = $asset->get();
      foreach($asset_map as $a){
      
        $this->assertRegexp('#/bart/lisa/homer#i',$a->getField('url'));
      
      }//foreach
    
    }//foreach
  
  }//method
  
  /**
   *  test adding a path
   */
  public function testAddPath(){
    
    $dest_path = $this->getTempPath('Asset');
    $foo_src_path = $this->getFixturePath('Asset','Foo','assets');
    $bar_src_path = $this->getFixturePath('Asset','Bar','assets');
    $che_src_path = $this->getFixturePath('Asset','Che','assets');
    
    $foo_assets = new FooAssets();
    $foo_assets->addSrcPath($foo_src_path);
    
    $bar_assets = new BarAssets();
    $bar_assets->addSrcPath($bar_src_path);
    
    $framework_assets = new FrameworkAssets();
    $framework_assets->setDestPath($dest_path);
    $framework_assets->setPrefixPath('assets');
    $framework_assets->setSrcPaths(array($foo_src_path,$bar_src_path,$che_src_path));
    $framework_assets->add($foo_assets);
    $framework_assets->add($bar_assets);
    
    $framework_assets->handle();
    
    $asset_map = $framework_assets->get();
    
    $path = new Path($this->getFixturePath('Asset'));
    
    $this->assertEquals(8,count($asset_map['css']));
    $this->assertEquals(2,count($asset_map['js']));
    
  }//method

}//class

class FooAssets extends Assets {

  public function getName(){ return 'Foo'; }//method

  public function getExtension(){ return 'css'; }//method

}//class

class BarAssets extends Assets {

  public function getExtension(){ return 'js'; }//method

}//class

class CheAssets extends Assets {

  public function getName(){ return 'Che'; }//method

  public function getDependencies(){ return array('Foo'); }//method

  public function getExtension(){ return 'css'; }//method

}//class
