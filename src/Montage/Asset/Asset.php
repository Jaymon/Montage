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

abstract class Asset {

  /**
   *  holds where the paths in the {@link $path_list} will be moved to
   *  
   *  @since  9-19-11   
   *  @see  setToPath()      
   *  @var  Path
   */
  protected $dest_path = null;

  abstract public function getSrcPaths();

  public function setDestPath($path){
  
    $this->dest_path = $this->normalizePath($path);
  
  }//method
  
  public function getDestPath(){
  
    // canary...
    if(empty($this->dest_path)){
      throw new \UnexpectedValueException('no destination path has been set');
    }//if
  
    return $this->dest_path;
  
  }//if
  
  public function handle(){
  
    $dest_path = $this->getDestPath();
  
    $src_path_list = $this->getSrcPaths();
    foreach($src_path_list as $src_path){
    
      $src_path = $this->normalizePath($src_path);
    
      $this->move($src_path,$dest_path);
    
    
    
    
    
    }//foreach
  
  
  
  }//method

  protected function move(Path $src_path,Path $dest_path){
  
    foreach($src_path->createFileIterator() as $src_file){
    
      $src_file = $this->normalizePath($src_file);
      $relative_file = $src_file->getRelative($src_path);
      $fingerprint = $src_file->getFingerprint();
    
      $relative_path_info = pathinfo($relative_file);
      
      $public_path = $this->getPublicName($relative_file
      
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
