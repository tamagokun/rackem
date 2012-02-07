<?php
require "vendor/SplClassLoader.php";

$loader = new SplClassLoader('Rackem', __DIR__.'/lib');
$loader->register();