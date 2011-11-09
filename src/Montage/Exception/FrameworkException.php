<?php
/**
 *  base class for all framework exceptions
 *  
 *  honestly, this is here to make it easier to weed out framework exceptions in
 *  try/catch blocks   
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 11-9-11
 *  @package montage
 *  @subpackage exception
 ******************************************************************************/      
namespace Montage\Exception;

use Exception;

class FrameworkException extends Exception {}//class
