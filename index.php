<?php

require_once(__DIR__.'/out_class.php');
require_once('C:\Projects\Sandbox\Montage\_active\Montage\Start.php');

use Montage\Core;

$core = new Core('dev',1,realpath(__DIR__.'/..'));
$response = $core->handle();
$response->handle();
