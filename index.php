<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */

include(__DIR__.'/core/App.php');

$app = new App('dummy');

$app->run();

//others $app->out;
//	$app->error;
//	$app->print... etc.

?>
