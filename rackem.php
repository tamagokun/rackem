<?php
if(!class_exists("ClassLoader")) require "vendor/ClassLoader.php";

$loader = new ClassLoader(array(__DIR__.'/lib'));
$loader->register();
