<?php echo sprintf('%s?%s','<','php'); ?> 

<?php include('skeleton/docblock_tmpl.php'); ?> 
class <?php echo $this->getField('name',''); ?> extends montage_start {
  
  /**
   *  configuration code goes in this method, called everytime app is run
   */
  protected function start(){}//method
  
  /**
   *  if you want to do some closing down stuff, put it here      
   */
  public function stop(){}//method

}//class
