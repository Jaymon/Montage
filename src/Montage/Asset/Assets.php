<?php
/**
 *  handle a group of assets (basically, this wraps a bunch of Asset instances)
 * 
 *  if you have a library (let's call it Foo) that consists of 5 css files, then this
 *  class would represent the Foo library, and there would be 5 Asset class instances
 *  internal to this class that represent each individual css file   
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 9-22-11
 *  @package montage
 *  @subpackage Asset 
 ******************************************************************************/
namespace Montage\Asset;

use Path;
use IteratorAggregate;
use FlattenArrayIterator;

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
   *  get the dependencies that these assets rely on in order to function properly  
   *   
   *  @since  12-29-11
   *  @return array a list of dependendencies that need to be rendered before this instance   
   */
  public function getDependencies(){ return array(); }//method
  
  /**
   *  get the name of this asset
   *
   *  @note by default, this will just be the class name. But in plugins and modular code
   *  it would probably be wise to override this method to have a better name so 
   *  handling dependencies will be easier      
   *      
   *  @return string
   */
  public function getName(){
  
    $ret_str = get_class($this);
    if($ret_str[0] !== '\\'){ $ret_str = '\\'.$ret_str; }//if
    return $ret_str;
    
  }//method
  
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
  
    if(empty($prefix_path)){
    
      $this->prefix_path = null;
    
    }else{
  
      $this->prefix_path = $this->normalizePath($prefix_path);
      
    }//if/else
  
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
  
    foreach($src_path_list as $src_path){
    
      foreach($this->getPathIterator($src_path) as $src_file){
      
        if($asset = $this->move($src_path,$src_file,$dest_path)){

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
   *  @return Asset   
   */
  protected function move(Path $src_path,Path $src_file,Path $dest_path){
  
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
      $this->getUrl($public_file)
    );
  
    if(!$public_file->isFile()){
    
      // clear all old paths that might exist (just to keep the directory semi clear)...
      $kill_path = $public_file->getParent();
      $regex = sprintf('#%s-[^\.]+\.%s#i',$relative_file->getFilename(),$extension);
      $kill_path->clear($regex);
      
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
  
  public function hasName($name){
  
    $asset_map = $this->get();
    return isset($asset_map[$name]);
  
  }//method
  
  /**
   *  render a particular asset
   *  
   *  @param  string  $name the name of the asset      
   *  @return string
   */
  public function render($name = ''){

    $ret_str = '';
    if(empty($name)){
      $ret_str = $this->renderAll();
    }else{
      $ret_str = $this->renderName($name);
    }//if/else

    return $ret_str;
  
  }//method

  /**
   * render all the assets
   *
   * @see render()
   * @return  string
   */
  public function renderAll(){
    $ret_str = '';
    $assets_iterator = new FlattenArrayIterator($this->get());
    foreach($assets_iterator as $asset){
    
      $ret_str .= $asset->render().PHP_EOL;
      
    }//foreach
  
    return $ret_str;

  }//method

  /**
   * render just the asset at name
   *
   * @see render()
   * @param string  $name the name of the asset to render
   * @return  string
   */
  public function renderName($name){
    $ret_str = '';
    $asset_map = $this->get();
    if(isset($asset_map[$name])){
    
      $ret_str = $asset_map[$name]->render().PHP_EOL;
    
    }//if

    return $ret_str;

  }//method
  
  /**
   *  output all the assets that this instance wraps
   *
   *  @return string   
   */
  public function __toString(){ return $this->render(); }//method
  
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
    $prefix_path = $this->getPrefixPath();
    
    $ret_str = new Path($prefix_path,$path->getRelative($dest_path));
    
    // we need url separators...
    $ret_str = str_replace('\\','/',(string)$ret_str);
    
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
