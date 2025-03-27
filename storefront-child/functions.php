<?php

/**
 * подключение файлов
 */
require_once 'inc/classes.php';
require_once 'inc/ajax.php';

/**
 * инициализация общего класса
 */
if (class_exists('Register')) {
    $register = new Register();
}
