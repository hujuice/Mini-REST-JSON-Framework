<?php
// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(__DIR__ . '/../library'),
    get_include_path(),
)));

require_once('RestJson.php');
$restJson = new RestJson('../members.ini');

// GO GO GO!!!
$restJson->run();