<?php echo '<'.'?'.'php'; ?>

/**
 *  this is the bootstrap file for phpunit   
 *  
 *  @package    test
 *  @subpackage PHPUnit
 *  @author     <?php echo $this->getField('author'); ?>
 ******************************************************************************/

// include the framework...
<?php foreach($this->getField('framework_path_list',array()) as $framework_path): ?>
include_once('<?php echo $framework_path; ?>');
<?php endforeach; ?>

include("E:\Projects\sandbox\montage\RSS\_active\web\out_class.php");

// create and activate the framework...
$framework = new <?php echo $this->getField('framework_class_name'); ?>(
  'test',
  1,
  '<?php echo $this->getField('app_path'); ?>'
);
$framework->activate();

// set the static instance that any children can use...
\PHPUnit\FrameworkTestCase::setFramework($framework);

// hack to get out class to load if it is available... 
class_exists('out');

// get rid of any variables so PHPUnit doesn't try to save them and add them to $GLOBALS... 
unset($framework);
unset($app_path);
