<?php

namespace Frame\Sql;

/*
|语法解析
|依赖于Builder
*/

class Grammer{
    //编译select语法
    public static function compileSelect(Builder $build){
        
        $select = $build->getSelect('select');
        
        $columns = \implode(',', array_map('static::wrapSelectParam', $select));
        
        $sql = "SELECT {$columns} FROM `{$build->getTable()}` ";

        return $select == [] ? '' : $sql;
    }
    //编译order by语法
    public static function compileOrder(Builder $build){

        $order = $build->getSelect('order');
        $columns = \implode(',', array_map('static::wrapOrderParam', $order));
        
        $sql = "ORDER BY {$columns} ";

        return $order == [] ? '' : $sql;
    }
    //编译where语法
    public static function compileWhere(Builder $build){
        
        $where = $build->getSelect('where');
        $columns = \implode('AND', array_map('static::wrapWhereParam', $where));

        $sql = "WHERE {$columns} ";
        return $where == [] ? '' : $sql;
    }
    //编译limit语法
    public static function compileLimit(Builder $build){
        
        $limit = $build->getSelect('limit');
        $columns = \implode(',', $limit);
        
        $sql = "LIMIT {$columns} ";
        return $limit == [] ? '' : $sql;
    }

    //编译join语法
    public static function compileJoin(Builder $build){
        $join = $build->getSelect('join');
        $columns = \implode(' ', array_map('static::wrapJoinParam', $join));
        $sql = $columns;
        return $columns == [] ? '' : $sql;
    }

    public static function compileInsert(Builder $build){
        $insert = $build->getInsert();
        $keys = \implode(',', array_map('static::wrapKeyParam', array_keys($insert)));
        $values = \implode(',', array_fill(0, count($insert), '?'));
        $sql = "INSERT INTO `{$build->getTable()}`({$keys}) VALUES({$values})";

        return $insert == [] ? '' : $sql;
    }

    public static function compileUpdate(Builder $build){
        $update = $build->getUpdate();
        $columns = \implode(',', array_map('static::wrapInsertParam', array_keys($update)));
        $sql = "UPDATE `{$build->getTable()}` SET $columns ";

        return $update == [] ? '' : $sql;
    }

    /*
    |将所有表和键加上`符号
    |针对`key`.value, 和 `key` as new, 进行转意
    */
    protected static function wrapKeyParam($key){
        $col = trim($key);
        //判断`question`.id这种情况
        if($index = stripos($key, '.')){
            $col = trim(\substr($key, 0, $index));
        //判断`question` as qs这种情况
        }else if($index = stripos($key, ' ')){
            $col = \substr($key, 0, $index);
        }
        return "`$col`" .($index ? substr($key, $index) : '');
    }

    /*
    |对函数表达式不进行`转义, 否则进行key转义
    */
    protected static function wrapValueParam($value){
        if(\preg_match_all("/\(\)/", $value)){
            return $value;
        }

        return self::wrapKeyParam($value);
    }

    //封装select字符串
    protected static function wrapSelectParam($key){
        return self::wrapKeyParam($key);
    }

    //封装join字符串
    protected static function wrapJoinParam($array){
        $left = self::wrapKeyParam($array[2]);
        $right = self::wrapKeyParam($array[4]);
        return "{$array[0]} JOIN  `{$array[1]}` ON  $left{$array[3]}$right ";
    }
    //封装order by字符串
    protected static function wrapOrderParam($array){
        $attr = self::wrapValueParam($array[0]);
        return " $attr {$array[1]} ";
    }
    
    protected static function wrapWhereParam($array){
        return " `{$array[0]}`{$array[1]}? ";
    }

    protected static function wrapInsertParam($k){
        $key = self::wrapKeyParam($k);
        return "$key=? ";
    }
}
