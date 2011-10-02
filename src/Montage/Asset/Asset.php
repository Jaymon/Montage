<?php
/**
 *  represent one Asset file, usually a css or js file
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 9-21-11
 *  @package montage
 *  @subpackage Asset 
 ******************************************************************************/
namespace Montage\Asset;

use Path;
use Montage\Field\Field;

class Asset extends Field implements Assetable {

  // const TYPE_STYLESHEET = 1;
  // const TYPE_JAVASCRIPT = 2;
  // const TYPE_IMAGE = 3;
  
  /**
   *  construct an instance
   *  
   *  @param  string  $name the name of this Asset   
   *  @param  Path  $src_file where the asset originated from
   *  @param  Path  $public_file  where the asset's final resting place is
   *  @param  string  $url  the url that can be used in an html document
   */
  public function __construct($name,Path $src_file,Path $public_file,$url){
  
    $this->setName($name);
    $this->setField('src_file',$src_file);
    $this->setField('public_file',$public_file);
    $this->setField('url',$url);
  
  }//method
  
  /**
   *  output the asset
   *  
   *  @return string
   */
  public function __toString(){ return $this->render(); }//method
  
  /**
   *  render a particular asset
   *  
   *  @param  string  $name the name of the asset      
   *  @return string
   */
  public function render($name = ''){
  
    $ret_str = '';
    $extension = $this->getField('public_file')->getExtension();
    $ext = mb_strtolower($extension);
    
    switch($ext){
    
      case 'css':
      
        $ret_str = $this->renderStylesheet();
        break;
        
      case 'js':
      
        $ret_str = $this->renderJavascript();
        break;
    
      default:
    
        $ret_str = $this->renderImage();
        break;
    
    }//switch
  
    return $ret_str;
  
  }//method
  
  /**
   * 
   *
   */        
  public function getExtension(){ return $this->getField('public_file')->getExtension(); }//method
  
  /**
   *  get a key for the file name
   *
   *  @return string
   */
  public function getName(){ return $this->getField('name'); }//method
  
  /**
   *  normalize and set the asset name
   *  
   *  @param  string  $name
   */
  protected function setName($name){
  
    $name = new Path($name);
    $parent = $name->getParent();
    $ret_str = new Path($parent,$name->getFilename());
    
    // all directory separators should be url separators...
    $ret_str = str_replace('\\','/',$ret_str);
    
    // everything should be uppsercase...
    $ret_str = mb_strtolower($ret_str);
    
    $this->setField('name',$ret_str);
  
  }//method
  
  /**
   *  get a css stylesheet html block
   *  
   *  @return string
   */
  protected function renderStylesheet(){
  
    return sprintf(
      '<link rel="stylesheet" href="%s" type="text/css" media="%s">',
      $this->getField('url'),
      $this->getField('media','screen, projection')
    );
  
  }//method
  
  /**
   *  get a javascript html block
   *  
   *  @return string
   */
  protected function renderJavascript(){
  
    return sprintf(
      '<script type="text/javascript" src="%s"></script>',
      $this->getField('url')
    );
  
  }//method
  
  /**
   *  get an image html block
   *  
   *  @return string
   */
  protected function renderImage(){
  
    return sprintf(
      '<img src="%s" title="%s" alt="%s">',
      $this->getField('url'),
      $this->getField('title',''),
      $this->getField('alt','')
    );
  
  }//method

}//class
