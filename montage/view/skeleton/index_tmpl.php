<?php echo sprintf('%s?%s','<','php'); ?> 

<?php include('skeleton/docblock_tmpl.php'); ?> 

// set required constants...
define('MONTAGE_CONTROLLER','<?php echo $this->getField('controller',''); ?>');
define('MONTAGE_DEBUG',true);
define('MONTAGE_ENVIRONMENT','<?php echo $this->getField('environment',''); ?>');
define('MONTAGE_APP_PATH','<?php echo $this->getField('montage_app_path',''); ?>');

// handle the request...
require(join(DIRECTORY_SEPARATOR,array('<?php echo $this->getField('montage_path',''); ?>','start.php')));
