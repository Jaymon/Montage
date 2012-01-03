<?php
/**
 *  interface for building an Asset
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 9-30-11
 *  @package montage
 *  @subpackage Asset 
 ******************************************************************************/
namespace Montage\Asset;

interface Assetable {
  
  /**
   *  a list of dependency names that this asset relies on  
   *   
   *  @since  12-29-11
   *  @return array a list of dependendencies that need to be rendered before this instance   
   */
  public function getDependencies();
  
  /**
   *  get the name of this asset
   *
   *  @return string
   */
  public function getName();
  
  /**
   *  get the extension of the asset, or the extension of the group of assets
   *  
   *  each asset, or group of assets, will have an extension (usually css or js) that
   *  represents the file(s) of the asset   
   *
   *  @return string   
   */
  public function getExtension();
  
  /**
   *  render a particular asset
   *  
   *  @param  string  $name the name of the asset      
   *  @return string
   */
  public function render($name = '');

}//class
