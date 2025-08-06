<?php

include_once "App/iran.php";


spl_autoload_register(function ($class) {
    $class_file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';

    if (!(file_exists($class_file) && is_readable($class_file))) {
        die("کلاس $class پیدا نشد در مسیر $class_file.<br>");
    }

    require_once $class_file;
});