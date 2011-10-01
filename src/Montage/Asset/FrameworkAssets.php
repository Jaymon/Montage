<?php
/**
 *  handle framework assets
 *  
 *  this class is different than most classes that will extend Assets in that it is
 *  designed for the framework to use to automatically find and include assets, so you
 *  should only override it if you want to mess with that functionality, otherwise, you
 *  should always extend Assets     
 * 
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 9-23-11
 *  @package montage
 *  @subpackage Asset 
 ******************************************************************************/
namespace Montage\Asset;

use Montage\Path;

class FrameworkAssets extends Assets {
  
  /**
   *  this will hold all the paths from the assets that are added (see {@link add()})
   *  before {@link handle()} is called
   *  
   *  this is here so the same asset doesn't get included more than once         
   *
   *  @var  array
   */
  protected $assets_path_list = array();
  
  /**
   *  this particular object doesn't have a specific asset type but this is required by parent
   *
   *  @return string   
   */
  public function getExtension(){ return ''; }//method
  
  /**
   *  this will handle the transfer of the source assets to their final resting place
   *  
   *  this will also handle autodiscovery of assets throughout the App codebase      
   */
  public function handle(){
  
    $dest_path = $this->getDestPath();
    $prefix_path = $this->getPrefixPath();
  
    foreach($this->get() as $assets_list){
    
      foreach($assets_list as $assets){
      
        if($assets instanceof Assets){
        
          $assets->setDestPath($dest_path);
          $assets->setPrefixPath($prefix_path);
          
          $assets->handle();
  
          foreach($assets->get() as $asset){
        
            if($asset_path = (string)$asset->getField('src_file','')){
          
              $this->assets_path_list[] = $asset_path;
              
            }//if
          
          }//foreach
          
        }else{
  
          if($asset_path = (string)$assets->getField('src_file','')){
          
            $this->assets_path_list[] = $asset_path;
            
          }//if
        
        }//if/else
        
      }//foreach
    
    }//foreach
    
    parent::handle();
    
  }//method
  
  /**
   *  render the assets of a particular extension
   *  
   *  @param  string  $extension  usually something like css or js      
   *  @return string
   */
  public function render($extension){
  
    $ret_str = '';
    $asset_map = $this->get();
    
    if(isset($asset_map[$extension])){
    
      foreach($asset_map[$extension] as $asset){
      
        $ret_str .= $asset->__toString();
      
      }//foreach
    
    }//if
    
    return $ret_str;
  
  }//method
  
  /**
   *  wrap parent to allow grouping by extension (handy for {@link render()})
   *  
   *  @see  parent::add()
   */
  public function add(Assetable $asset){
  
    $ext = $asset->getExtension();
    if(!isset($this->asset_list[$ext])){
    
      $this->asset_list[$ext] = array();
    
    }//if
    
    $this->asset_list[$ext][$asset->getName()] = $asset;
    
  }//method
  
  /**
   *  wrap parent to filter the iterator by only files that haven't been seen before
   *
   *  @param  Path  $path
   *  @return Traversable   
   */
  protected function getPathIterator(Path $path){
  
    $assets_path_list = $this->assets_path_list;
  
    $iterator = parent::getPathIterator($path);
    return new \CallbackFilterIterator(
      $iterator,
      function($current,$key,$iterator) use ($assets_path_list){
      
        return !in_array((string)$current,$assets_path_list);
      
      }
    );
  
  }//method
  
}//class
