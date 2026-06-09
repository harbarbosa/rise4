$path = 'c:\laragon\www\rise4\index.php'
if (-not (Test-Path $path)) { throw 'index.php not found' }
@'
<?php
require __DIR__ . '/app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
