<?php

if(!class_exists("\\Rackem\Server")) require_once dirname(__DIR__).'/rackem.php';

return \Rackem::run("\Rackem\BasicWebServer");
