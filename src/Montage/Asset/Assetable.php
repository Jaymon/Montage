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

}//class
