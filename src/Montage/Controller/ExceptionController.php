<?php
/**
 *  handle exceptions 
 *  
 *  this class drops all dependencies from {@link \Montage\Controller\Controller} because
 *  if could be called before all dependencies are sorted, which would cause a fatal error
 *  since the dependencies can't be found.
 *  
 *  If you need to have dependencies, I would recommend using the setClassName(ClassName $var)
 *  syntax in your child classes as that will allow the dependencies to be passed in if they
 *  are available, but ignored if they're not, though you still might get fatal errors if a 
 *  needed class file cannot be found
 *  
 *  Fatal errors are common if this controller is called before the AutoLoader has been
 *  created.
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 2-19-10
 *  @package montage 
 *  @subpackage Controller 
 ******************************************************************************/
namespace Montage\Controller;

use Montage\Controller\Controller;
use Montage\Exception\StopException;

class ExceptionController extends Controller {

  /**
   *  set to false to have DIC ignore this dependency
   *     
   *  @var  \Montage\Request\Request
   */
  public $request = false;
  
  /**
   *  set to false to have DIC ignore this dependency
   *     
   *  @var  \Montage\Response\Response
   */
  public $response = false;
  
  /**
   *  set to false to have DIC ignore this dependency
   *     
   *  @var  \Montage\Url
   */
  public $url = false;

  /**
   *  overrides the parent to get rid of the dependencies since this could be
   *  called before all dependencies are sorted, which would cause a fatal error
   *  since the dependencies can't be found
   */
  public function __construct(){}//method

  /**
   *  catch-all stray errors
   *  
   *  if the thrown exception doesn't have its own handler method then it will be
   *  sent here                 
   *
   *  @param  \Exception  $e  the thrown exception   
   */
  public function handleIndex(\Exception $e){
  
    $title = sprintf('Exception handled by %s',__METHOD__);
  
    if(strncasecmp(PHP_SAPI, 'cli', 3) === 0){
    
      echo $title,PHP_EOL,PHP_EOL;
      echo $e; // CLI
    
    }else{
    
      echo $title,'<br><br>';
      echo nl2br($e); // html
    
    }//if/else
     
    return false;
  
  }//method

}//class
