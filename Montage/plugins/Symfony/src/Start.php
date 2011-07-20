<?php
/**
 *  start the mingo plugin
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-22-11
 *  @package montage
 *  @subpackage mingo
 ******************************************************************************/
Namespace Symfony;

use Montage\Start\Start as MontageStart;

class Start extends MontageStart {

  public function handle(StdAutoLoader $sal){
    
    \out::i($sal);
    
  }//method
  
}//class
