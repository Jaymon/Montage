<?php
/**
 *  the base class for any config objects that are used to configure stuff
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-17-11
 *  @package montage
 *  @subpackage Config 
 ******************************************************************************/     
namespace Montage\Config;

use Montage\Field;
use Montage\Config\Configurable;

abstract class Config extends Field implements Configurable {

}//class
