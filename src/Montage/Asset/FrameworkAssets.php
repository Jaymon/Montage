<?php
/**
 *  handle framework assets
 *  
 *  this class is different than most classes that will extend Assets in that it is
 *  designed for the framework to use to automatically find and include assets, so you
 *  should only override it if you want to mess with that functionality, otherwise, you
 *  should always extend Assets     
 * 
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 9-23-11
 *  @package montage
 *  @subpackage Asset 
 ******************************************************************************/
namespace Montage\Asset;

use Path;
use FlattenArrayIterator;

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
   *  hold asset dependencies until they are resolved
   *  
   *  @since  12-29-11   
   *  @see  handle(), add()      
   *  @var  array
   */
  protected $asset_dependency_map = array();
  
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
  
    $iterator = new FlattenArrayIterator($this->get());
  
    foreach($iterator as $assets){

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
    
    parent::handle();
    
    // error out if there are missing dependencies
    if(!empty($this->asset_dependency_map)){
    
      $e_msg = 'There are assets with missing dependencies:';
    
      foreach($this->asset_dependency_map as $dependency_name => $dependent_asset){
      
        $e_msg .= sprintf(' [%s depends on %s]',$dependency_asset->getName(),$dependency_name);
      
      }//foreach
    
      throw new \UnexpectedValueException($e_msg);
    
    }//if
    
  }//method
  
  /**
   *  since render() doesn't allow non $name passing in, this method is overrided to
   *  not do anything   
   *
   *  @return string   
   */
  public function __toString(){ return ''; }//method
  
  /**
   *  render a particular asset
   *  
   *  @example
   *    echo $this->render('css'); // render all css assets
   *    echo $this->render('js'); // render all js assets
   *    echo $this->render('specific/asset/name'); // render the specific/asset/name         
   *      
   *  @param  string  $name the name of the asset      
   *  @return string
   */
  public function render($name = ''){
  
    // canary...
    if(empty($name)){
      throw new \InvalidArgumentException(
        sprintf('%s expects an asset $name in order to render that specific asset.',get_class($this))
      );
    }//if
  
    $ret_str = '';
    $asset_map = $this->get();
    
    if(isset($asset_map[$name])){
    
      foreach($asset_map[$name] as $asset){
      
        $ret_str .= $asset->render().PHP_EOL;
      
      }//foreach
    
    }else{
    
      foreach(new \FlattenArrayIterator($asset_map) as $asset_name => $asset){
      
        if($asset instanceof Assets){
        
          if($asset->hasName($name)){
        
            $ret_str .= $asset->render($name).PHP_EOL;
            break;
          
          }//if
        
        }else{
        
          if($name == $asset_name){
          
            $ret_str .= $asset->render().PHP_EOL;
            break;
          
          }//if
        
        }//if/else
      
      }//foreach
    
    }//if/else

    return $ret_str;
  
  }//method
  
  /**
   *  wrap parent to allow grouping by extension (handy for {@link render()})
   *  
   *  normally, assets are sorted by [name] but this sorts the assets by [ext][name]
   *  so you can easily call something like $this->render('css') to render all the css
   *  Assets      
   *      
   *  @see  parent::add()
   */
  public function add(Assetable $asset){
  
    $name = $asset->getName();
    $ext = $asset->getExtension();
    $cleared = true;
    
    if(!isset($this->asset_list[$ext])){ $this->asset_list[$ext] = array(); }//if
    
    // make sure this asset has all its dependencies cleared before adding it to the list
    if($dependencies = $asset->getDependencies()){
    
      foreach($dependencies as $dependency_name){
      
        if(!isset($this->asset_list[$ext][$dependency_name])){
        
          if(!isset($this->asset_dependency_map[$dependency_name])){
          
            $this->asset_dependency_map[$dependency_name] = array();
            
          }//if
          
          $this->asset_dependency_map[$dependency_name][$name] = $asset;
          $cleared = false;
        
        }//if
      
      }//foreach
    
    }//if

    if($cleared){
    
      $this->asset_list[$ext][$name] = $asset;
      
      // add any dependencies that rely on this asset
      if(isset($this->asset_dependency_map[$name])){
      
        $this->asset_list[$ext] = array_merge(
          $this->asset_list[$ext],
          $this->asset_dependency_map[$name]
        );
        
        unset($this->asset_dependency_map[$name]);
      
      }//if
      
    }//if
    
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
