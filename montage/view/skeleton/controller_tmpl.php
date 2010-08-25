<?php echo sprintf('%s?%s','<','php'); ?> 

<?php include('skeleton/docblock_tmpl.php'); ?> 
class <?php echo $this->getField('name',''); ?> extends montage_controller {

  /**
   *  controller specific initializationcode goes here
   */
  function start(){}//method
  
  /**
   *  this is the default controller method for this controller
   *  
   *  this method is called if this controller is activated but no other method is given
   *      
   *  @return boolean like all controller methods if true, then the template will be rendered, 
   *                  if false, then montage::getResponse()->get() will be used instead of the template
   */
  function handleIndex(){
    echo 'Hello World';
  }//method
  
  /**
   *  after calling the handle* method, this method is run
   */
  function stop(){}//method

}//class
