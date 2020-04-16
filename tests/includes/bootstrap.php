<?php

// This file is called relative to Snappy root.

// Ensure Snappy handles a web request, and doesn't create files
define('SNAPPY_TESTING', true);

// Replace separators on Windows for test passing
define('SNAPPY_ABS_PATH', str_replace("\\", '/', realpath(__DIR__ . '/../..')));

define('SNAPPY_INITIATOR', SNAPPY_ABS_PATH . '/snappy.php');

// Spoof web server environment variables for testing
$_SERVER['DOCUMENT_ROOT'] = SNAPPY_ABS_PATH;
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 8000;
$_SERVER['REQUEST_URI'] = '/';

// Composer
if (file_exists('vendor/autoload.php'))
	$loader = include_once 'vendor/autoload.php';

// Get the framework
require 'snappy.php';

// Init Snappy with a test config
\Snappy::init([
	'plugins' => [
		'example',
		'test'
	]
]);