<?php

/**
 *  Start the project
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 2-19-10
 *  @package montage
 ******************************************************************************/

$start_path = join(DIRECTORY_SEPARATOR,array(dirname(__FILE__),'..','..','montage','start.php'));

// @todo  get rid of this...
$out_path = realpath(join(DIRECTORY_SEPARATOR,array(dirname(__FILE__),'..','..','montage','plugins','model','out_class.php')));
require($out_path);

require($start_path);
