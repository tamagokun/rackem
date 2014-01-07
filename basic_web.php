<?php

if(!class_exists("\\Rackem\Server")) require_once __DIR__.'/rackem.php';

return \Rackem::run("\Rackem\BasicWebServer");
