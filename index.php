<?php

require_once(__DIR__.'/out_class.php');
require_once('C:\Projects\Montage\_active\Montage\Start.php');

$core = new Montage\Core('dev',1,realpath(__DIR__.'/..'));
$response = $core->handle();
$response->handle();
