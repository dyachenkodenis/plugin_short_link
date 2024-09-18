<?php
/*
Plugin Name: Short Links Plugin
Description: Плагин для создания коротких ссылок 
Version: 1.0
Author: ...
*/


if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
}

App\ShortLinksPlugin::get_instance();
