<?php

// This file is called relative to Snappy root.

// Ensure Snappy handles a web request
define('SNAPPY_TESTING', true);

$_SERVER['REQUEST_URI'] = 'http://localhost:8123/';

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