<?php
/**
 *  handle an asset  
 * 
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 9-23-11
 *  @package montage
 ******************************************************************************/
namespace Montage\Asset;

use Montage\Path;

abstract class Assets {

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
  protected $relative_path = null;
  
  protected $src_path_list = array();
  
  protected $asset_map = array();

  abstract public function getExtension();
  
  public function getAssets(){ return $asset_map; }//method

  public function setSrcPaths(array $path_list){
  
    $this->src_path_list = $path_list;
  
  }//method
  
  public function addSrcPath($path){
  
    $this->src_path_list[] = $path;
  
  }//method

  public function getSrcPaths(){
  
    // canary...
    if(!empty($this->src_path_list)){ return $this->src_path_list; }//if
  
    $rclass = new \ReflectionClass($this);
    $file = new Path($rclass->getFileName());
    
    $src_path = $file->getSibling('#assets$#i',1);
    if($src_path === null){
    
      throw new \UnexpectedValueException('Could not find an assets folder and none was specified using setSrcPaths()');
    
    }//if
    
    $this->src_path_list = array((string)$src_path);
    return $this->src_path_list;
    
  }//method

  public function setDestPath($path,$relative_path = ''){
  
    $this->dest_path = $this->normalizePath($path);
    
    if(!empty($relative_path)){
    
      $this->relative_path = $this->normalizePath($relative_path);
      
    }//if
  
  }//method
  
  public function getDestPath(){
  
    // canary...
    if(empty($this->dest_path)){
      throw new \UnexpectedValueException('No destination path has been set, use setDestPath()');
    }//if
  
    return $this->dest_path;
  
  }//if
  
  public function handle(){
  
    $dest_path = $this->getDestPath();
  
    $src_path_list = $this->getSrcPaths();
    foreach($src_path_list as $src_path){
    
      $src_path = $this->normalizePath($src_path);
    
      if($asset_map = $this->move($src_path,$dest_path,$this->relative_path)){
      
        $this->asset_map = array_merge($this->asset_map,$asset_map);
      
      }//if
    
    }//foreach
  
  }//method

  protected function move(Path $src_path,Path $dest_path,Path $relative_path = null){
  
    $ret_map = array();
  
    foreach($src_path->createFileIterator() as $src_file){
    
      $src_file = $this->normalizePath($src_file);
      $fingerprint = $src_file->getFingerprint();
      
      $relative_file = $this->normalizePath($src_file->getRelative($src_path));

      $public_filename = $relative_file->getFilename().'-'.$fingerprint;
        
      if($extension = $relative_file->getExtension()){
      
        $public_filename .= '.'.$extension;
      
      }//if
      
      $public_path = new Path($dest_path,$relative_path,$relative_file->getPath(),$public_filename);
    
      $key = $this->getKey($relative_file);
      $ret_map[$key] = new Asset(
        $src_path,
        $public_path,
        $this->getUrl($public_path)
      );
      
      $ret_map[$key]['url'] = $this->getUrl($public_path);
      $ret_map[$key]['dest_path'] = $public_path;
      $ret_map[$key]['src_path'] = $src_file;
      $ret_map[$key]['type'] = $this->getType($extension);
    
      if(!$public_path->isFile()){
      
        // clear all old paths that might exist (just to keep the directory semi clear)...
        $kill_path = $public_path->getParent();
        $kill_path->clear(sprintf('#%s-[^\.]+#i',$relative_file->getFilename()));
        
        // copy the file to the new public location...
        $public_path->copyFrom($src_file);
      
      }//if
      
    }//foreach
  
    \out::e($ret_map);
    return $ret_map;
  
  }//method

  /**
   *  if the class should check more than one place for the template, add the alternate
   *  paths using this, setTemplate will then go through all the paths checking
   *  to see if the file exists in any path
   *  
   *  we render the template files using the include_paths so you can set other template files
   *  in the actual template and include them without having to actually know the path, it's "all
   *  for your convenience" programming
   *  
   *  @param  string  $path the template path to add to the include paths
   */
  public function addPath($path){
  
    // canary...
    if(empty($this->to_path)){
      throw new \UnexpectedValueException('your trying to add a path when there is no $to_path set');
    }//if
  
    $path = $this->normalizePath($path);
    
    foreach($path->createFileIterator() as $file){
    
      $relative_path = $path->getRelative($file);
      $fingerprint = md5_file((string)$file);
    
      $relative_path_info = pathinfo($relative_path);
      
      $public_path = $relative_path_info['dirname']
        .DIRECTORY_SEPARATOR.
        $relative_path_info['filename']
        .'-'.
        $fingerprint;
        
      if(isset($relative_path_info['extension'])){
      
        $public_path .= '.'.$relative_path_info['extension'];
      
      }//if
      
      $public_path = new Path($this->to_path,$public_path);
    
      if(!$public_path->isFile()){
      
        // clear all old paths that might exist (just to keep the directory semi clear)...
        $kill_path = $public_path->getParent();
        $kill_path->clear(sprintf('#%s-[^\.]+#i',$relative_path_info['filename']));
      
        // copy the file to the new public location...
        $public_path->copyFrom($file);
      
      }//if
      
      ///$this->setPath($relative_path,$public_path);
      
    }//foreach
    
    $this->path_list[] = $path;
    return $this;
  
  }//method
  
  public function addPaths(array $path_list){
  
    foreach($path_list as $path){ $this->addPath($path); }//foreach
    
    return $this;
  
  }//method
  
  protected function getKey($filename){
  
    // all directory separators should be url separators...
    $ret_str = str_replace('\\','/',$filename);
    
    // everything should be uppsercase...
    $ret_str = mb_strtoupper($ret_str);
    
    return $ret_str;
  
  }//method
  
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
