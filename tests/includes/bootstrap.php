<?php

// This file is called relative to Snappy root.

// Ensure Snappy handles a web request
define('SNAPPY_TESTING', true);

define('SNAPPY_ABS_PATH', realpath(__DIR__ . '/../..'));

define('SNAPPY_INITIATOR', SNAPPY_ABS_PATH . DIRECTORY_SEPARATOR . 'snappy.php');

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
		'example'
	],
	'token_timeout' => 1
]);