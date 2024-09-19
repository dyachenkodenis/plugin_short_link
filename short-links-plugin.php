<?php
/*
Plugin Name: Short Links Plugin
Description: Plugin for create short links
Version: 1.0
Author: Denis
*/


if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
}

App\ShortLinksPlugin::get_instance();
