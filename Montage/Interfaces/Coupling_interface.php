<?php
/**
 *  
 *  http://en.wikipedia.org/wiki/Coupling_%28computer_science%29
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-24-11
 *  @package montage
 *  @subpackage interface  
 ******************************************************************************/
namespace Montage\Interfaces;

use Montage\Coupler;

interface Coupling {

  public function setCoupler(Coupling $coupler);
  
  public function getCoupler();

}//method
