<?php
// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

require_once('RestJson.php');
$restJson = new RestJson('../services/myWs.ini');
$restJson->run();