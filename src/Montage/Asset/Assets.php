<?php
/**
 *  handle a group of assets (basically, this wraps a bunch of Asset instances)
 * 
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 9-22-11
 *  @package montage
 *  @subpackage Asset 
 ******************************************************************************/
namespace Montage\Asset;

use Montage\Path;
use IteratorAggregate;

abstract class Assets implements Assetable,IteratorAggregate {

  /**
   *  holds where the paths in the {@link $path_list} will be moved to
   *  
   *  @since  9-19-11   
   *  @see  setToPath()      
   *  @var  Path
   */
  protected $dest_path = null;
  
  /**
   *  appended to the dest_path to find the final resting place for the assets
   *
   *  @var  Path
   */
  protected $prefix_path = null;
  
  /**
   *  holds all the source paths that this intance will build its Asset list with
   *  
   *  @see  setSrcPaths(), addSrcPath()      
   *  @var  array
   */
  protected $src_path_list = array();
  
  /**
   *  holds all the Asset instances this class represents
   *
   *  @var  array an array of Asset instances
   */
  protected $asset_list = array();
  
  /**
   *  get the name of this asset
   *
   *  @return string
   */
  public function getName(){ return get_class($this); }//method
  
  /**
   *  get the assets this instance wraps
   *
   *  @return array
   */
  public function get(){ return $this->asset_list; }//method

  /**
   *  set a list of source paths for this instance to use
   *
   *  @param  array $path_list   
   */
  public function setSrcPaths(array $path_list){
  
    $this->src_path_list = array();
    foreach($path_list as $path){
      $this->addSrcPath($path);
    }//foreach
  
  }//method
  
  /**
   *  add the source $path to the source path list
   *
   *  @param  Path  $path
   */
  public function addSrcPath($path){
  
    $this->src_path_list[] = $this->normalizePath($path);
  
  }//method

  /**
   *  get the source paths this instance uses
   *
   *  @return array   
   */
  public function getSrcPaths(){
  
    // canary...
    if(!empty($this->src_path_list)){ return $this->src_path_list; }//if
  
    // get the file location of the parent class...
    $rclass = new \ReflectionClass($this);
    $file = new Path($rclass->getFileName());
    
    $src_path = $file->getSibling('#assets$#i',1);
    if($src_path === null){
    
      throw new \UnexpectedValueException('Could not find an assets folder and none was specified using setSrcPaths()');
    
    }//if
    
    $this->addSrcPath($src_path);
    
    return $this->src_path_list;
    
  }//method

  /**
   *  when {@link handle()} is called all the source assets will be transferred to this path
   *
   *  @param  string  $path
   */
  public function setDestPath($path){
  
    $this->dest_path = $this->normalizePath($path);
    
  }//method
  
  /**
   *  get the destination path
   *
   *  @return Path   
   */
  public function getDestPath(){
  
    // canary...
    if(empty($this->dest_path)){
      throw new \UnexpectedValueException('No destination path has been set, use setDestPath()');
    }//if
  
    return $this->dest_path;
  
  }//if
  
  /**
   *  before a file is transferred to the destination path, it is prefixed with this path
   *  
   *  basically, a file gets moved from source to: destination_path / prefix_path / source_file            
   *
   *  @param  string  $prefix_path   
   */
  public function setPrefixPath($prefix_path){
  
    $this->prefix_path = $this->normalizePath($prefix_path);
  
  }//method
  
  /**
   *  get the prefix path
   *
   *  @return Path
   */
  public function getPrefixPath(){ return $this->prefix_path; }//method
  
  /**
   *  this will handle the transfer of the source assets to their final resting place
   */
  public function handle(){
  
    $src_path_list = $this->getSrcPaths();
    $dest_path = $this->getDestPath();
    $prefix_path = $this->getPrefixPath();
  
    foreach($src_path_list as $src_path){
    
      foreach($this->getPathIterator($src_path) as $src_file){
      
        if($asset = $this->move($src_path,$src_file,$dest_path,$prefix_path)){

          $this->add($asset);

        }//if
        
      }//foreach
    
    }//foreach
  
  }//method

  /**
   *  moves the $src_file, found via $src_path, to $dest_path.$prefix_path 
   *
   *  @param  Path  $src_path one of the source paths that was used to find $src_file
   *  @param  Path  $src_file the file being moved
   *  @param  Path  $dest_path  the destination path
   *  @param  Path  $prefix_path  the prefix that will be appended to $destination_path before $src_file is moved
   *  @return Asset   
   */
  protected function move(Path $src_path,Path $src_file,Path $dest_path,Path $prefix_path = null){
  
    $ret_asset = null;
  
    $fingerprint = $src_file->getFingerprint();
    
    $relative_file = $this->normalizePath($src_file->getRelative($src_path));

    $public_filename = $relative_file->getFilename().'-'.$fingerprint;
      
    if($extension = $this->getSrcExtension($src_file)){
    
      $public_filename .= '.'.$extension;
    
    }//if
    
    $public_file = new Path($dest_path,$relative_file->getPath(),$public_filename);
    
    $ret_asset = new Asset(
      $relative_file,
      $src_file,
      $public_file,
      $this->getUrl($prefix_path,$public_file)
    );
  
    if(!$public_file->isFile()){
    
      // clear all old paths that might exist (just to keep the directory semi clear)...
      $kill_path = $public_file->getParent();
      $kill_path->clear(sprintf('#%s-[^\.]+\.%s#i',$relative_file->getFilename(),$this->getExtension()));
      
      // copy the file to the new public location...
      $public_file->setFrom($src_file);
    
    }//if

    return $ret_asset;
  
  }//method

  /**
   *  required for the IteratorAggregate interface
   *
   *  @return \Traversable
   */
  public function getIterator(){
  
    $ret_iterator = new \AppendIterator();
  
    $src_path_list = $this->getSrcPaths();
    foreach($src_path_list as $src_path){
    
      $iterator = $this->getPathIterator($src_path);
      
      $ret_iterator->append($iterator);
    
    }//foreach
  
    return $ret_iterator;
  
  }//method
  
  /**
   *  output all the assets that this instance wraps
   *
   *  @return string   
   */
  public function __toString(){
  
    $ret_str = '';
    $assets_iterator = new \RecursiveIteratorIterator(
      new \RecursiveArrayIterator($this->get())
    );
    foreach($assets_iterator as $asset){
    
      $ret_str .= $asset->__toString().PHP_EOL;
      
    }//foreach
  
    return $ret_str;
  
  }//method
  
  /**
   *  add an Asset to this instance
   *  
   *  @param  Assetable $asset  any object that implements Assetable
   */
  public function add(Assetable $asset){
  
    $this->asset_list[$asset->getName()] = $asset;
  
  }//method
  
  /**
   *  get the regex that will be used to iterate through the source paths
   *
   *  @return string   
   */
  protected function getIteratorRegex(){
    
    $regex = '';
    if($ext = $this->getExtension()){ $regex = sprintf('#\.%s#i',$ext); }//if
    return $regex;
    
  }//method
  
  /**
   *  get the path iterator that will be used to do the actual source path iteration
   *
   *  @param  Path  $path a path to iterate, usually a source path   
   */        
  protected function getPathIterator(Path $path){
  
    $regex = $this->getIteratorRegex();
    return $path->createFileIterator($regex);
  
  }//method
  
  /**
   *  get the extension of the $file
   *
   *  @param  Path  $file
   *  @return string      
   */
  protected function getSrcExtension(Path $file){ return $file->getExtension(); }//method
  
  /**
   *  get the url that the asset will use
   *
   *  @param  Path  $path
   *  @return string  a url   
   */
  protected function getUrl(Path $path){
  
    $dest_path = $this->getDestPath();
    
    $ret_str = $path->getRelative($dest_path);
    
    // we need url separators...
    $ret_str = str_replace('\\','/',$ret_str);
    
    if($ret_str[0] !== '/'){
    
      $ret_str = '/'.$ret_str;
    
    }//if
  
    return $ret_str;
  
  }//method
  
  /**
   *  normalize the passed in path
   *
   *  @param  string  $path
   *  @return Path   
   */
  protected function normalizePath($path){
  
    // canary...
    if(empty($path)){
      throw new \UnexpectedValueException('$path is empty');
    }//if
    if(!($path instanceof Path)){
      $path = new Path($path);
    }//if
    
    return $path;
  
  }//method
  
}//class
