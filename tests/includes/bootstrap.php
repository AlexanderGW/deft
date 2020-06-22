<?php

// This file is called relative to Deft root.

// Ensure Deft handles a web request, and doesn't create files
define('DEFT_TESTING', true);

// Replace separators on Windows for test passing
define('DEFT_ABS_PATH', str_replace("\\", '/', realpath(__DIR__ . '/../..')));

define('DEFT_INITIATOR', DEFT_ABS_PATH . '/deft.php');

// Spoof web server environment variables for testing
$_SERVER['DOCUMENT_ROOT'] = DEFT_ABS_PATH;
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 8000;
$_SERVER['REQUEST_URI'] = '/';

// Composer
if (file_exists('vendor/autoload.php'))
	$loader = include_once 'vendor/autoload.php';

// Get the framework
require 'deft.php';

// Init Deft with a test config
\Deft::init([
	'plugins' => [
		'example',
		'test'
	]
]);