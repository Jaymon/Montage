<?php
/**
 *  handle assets
 *
 *  how I'm imagining assets working: it will go through and compile the list of all assets
 *  and move them to the web/assets folder and keep them in memory, then there will be a 
 *  __toString method that will output the js and css at the top of page, so anything can
 *  be overriden, http assets can be added, etc.
 *  
 *  @link http://guides.rubyonrails.org/asset_pipeline.html
 *  
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-28-10
 *  @package montage
 ******************************************************************************/
namespace Montage\Asset;

use Montage\Path;
use Montage\Field\Field;

class Asset extends Field {

  const TYPE_STYLESHEET = 1;
  const TYPE_JAVASCRIPT = 2;
  const TYPE_IMAGE = 3;
  
  public function __construct(Path $src_path,Path $public_path,$url){
  
    $this->setField('src_path',$src_path);
    $this->setField('public_path',$public_path);
    $this->setField('url',$url);
  
  }//method
  
  public function __toString(){
  
    return $this->outType();

  }//method
  
  protected function outType(){
  
    $ret_str = '';
    $extension = $this->public_path->getExtension();
    $ext = mb_strtolower($extension);
    
    switch($ext){
    
      case 'css':
      
        $ret_str = $this->outStylesheet();
        break;
        
      case 'js':
      
        $ret_str = $this->outJavascript();
        break;
    
      default:
    
        $ret_str = $this->outImage();
        break;
    
    }//switch
  
    return $ret_str;
  
  }//method
  
  protected function outStylesheet(){
  
    return sprintf(
      '<link rel="stylesheet" href="%s" type="text/css" media="%s">',
      $this->getField('url'),
      $this->getFiel('media','screen, projection')
    );
  
  }//method
  
  protected function outJavascript(){
  
    return sprintf(
      '<script type="text/javascript" src="%s"></script>',
      $this->getField('url')
    );
  
  }//method
  
  protected function outImage(){
  
    return sprintf(
      '<img src="%s" title="%s" alt="%s">',
      $this->getField('url'),
      $this->getField('title',''),
      $this->getField('alt','')
    );
  
  }//method

}//class
