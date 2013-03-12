<?php echo '<'.'?'.'php'; ?> 
/**
 * 
 * 
 * @version 0.1
 * @author 
 * @since <?php echo date('Y-m-d'); ?> 
 ******************************************************************************/
namespace Controller;

<?php foreach($this->getField('use_class_names', array()) as $use_class_name): ?>
use <?php echo $use_class_name; ?>;
<?php endforeach; ?>

class <?php echo $this->getField('class_name').$this->getField('class_postfix'); ?> extends <?php echo $this->getField('parent_class_name'); ?> {

  public function preHandle(){
  
  }//method

  public function handleDefault(array $params = array()){

  }//method

}//class

