<?php
spl_autoload_register(function(string $class): void {
	if (strpos($class, 'Rhymix\\Modules\\Attendance\\') !== 0) return;
	$relative = str_replace(['Rhymix\\Modules\\Attendance\\', '\\'], ['', '/'], $class);
	$file = __DIR__ . '/' . $relative . '.php';
	if (file_exists($file)) require_once $file;
});

class attendance extends \Rhymix\Modules\Attendance\Controllers\Install {}
