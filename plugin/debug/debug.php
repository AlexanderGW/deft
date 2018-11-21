<?php

// Append debugging
if (SNAPPY_DEBUG) {
	Filter::add('documentBody', function($content) {
		$content .= Snappy::capture('template.debug');

		return $content;
	});
}