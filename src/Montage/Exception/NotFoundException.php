<?php
/**
 *  thrown for 404 HTTP errors 
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-14-11
 *  @package montage
 *  @subpackage exceptions  
 ******************************************************************************/      
namespace Montage\Exception;

class NotFoundException extends HttpException {

  public function __construct($msg = ''){
  
    parent::__construct(404,$msg);
  
  }//method
  
}//class
