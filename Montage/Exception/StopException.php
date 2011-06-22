<?php
/**
 *  thrown when you just want to halt processing but it isn't an error state
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-15-11
 *  @package montage
 *  @subpackage exceptions
 ******************************************************************************/      
namespace Montage\Exception;

use Exception;

class StopException extends Exception {}//class
