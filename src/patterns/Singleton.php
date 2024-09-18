<?php

namespace Patterns;

abstract class Singleton {    

    private static $instances = [];

    public static function get_instance() {
        $class = get_called_class(); 
        if ( ! isset( self::$instances[ $class ] ) ) {
            self::$instances[ $class ] = new $class();
        }
        return self::$instances[ $class ];
    }

    protected function __construct() {}

    protected function __clone() {}
   
}
