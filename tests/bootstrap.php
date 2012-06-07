<?php

spl_autoload_register(function($class)
{
	$dir  = dirname(__DIR__);
	$file = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';

	if (file_exists($src = $dir.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.$file)) {
		require $src;
	} elseif (file_exists($tests = $dir.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.$file)) {
		require $tests;
	}
});