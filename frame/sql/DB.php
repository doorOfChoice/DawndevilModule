<?php

namespace Frame\Sql;

class DB{
    //Frame\Sql\SqlRunner
    protected static $conn = null;


    public static function table($table){
        if(\is_null(self::$conn)){
            $setting = \parse_ini_file(__DIR__ . '/../../config/database.ini');
            self::$conn = new SqlRunner($setting);
        }

        return new Builder($table, self::$conn);
    }
}