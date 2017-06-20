<?php

namespace Dawndevil\Sql;

/*
|语法解析
|依赖于Builder
*/

class Grammer{
    protected static $need_params = [
        'where', 'insert', 'update'
    ];

    protected static function rs_set($sql, $params = []){
        return [$sql, $params];
    }

    //编译select语法
    public static function compileSelect(Builder $build){
        
        $select = $build->getSelect('select');

        if($select == [])
            return self::rs_set(''); 

        $columns = \implode(',', array_map('static::wrapSelectParam', $select));
        
        $sql = "SELECT {$columns} FROM `{$build->getTable()}` ";

        return self::rs_set($sql);
    }
    //编译order by语法
    public static function compileOrder(Builder $build){

        $order = $build->getSelect('order');
        
        if($order == [])
            return self::rs_set('');

        $columns = \implode(',', array_map('static::wrapOrderParam', $order));
        
        $sql = "ORDER BY {$columns} ";

        return self::rs_set($sql);
    }

    //编译where语法
    public static function compileWhere(Builder $build){
        
        $where = $build->getSelect('where');
        
        if($where == [])
           return self::rs_set('');

        $columns = \implode('AND', array_map('static::wrapWhereParam', $where));

        $sql = "WHERE {$columns} ";

        return self::rs_set($sql, $build->getArrParams('where', 2));
    }

    //编译limit语法
    public static function compileLimit(Builder $build){
        
        $limit = $build->getSelect('limit');
        
        if($limit == [])
           return self::rs_set('');

        $columns = \implode(',', $limit);
        
        $sql = "LIMIT {$columns} ";

        return self::rs_set($sql);
    }

    //编译join语法
    public static function compileJoin(Builder $build){
        $join = $build->getSelect('join');
        
        if($join == [])
            return self::rs_set('');

        $columns = \implode(' ', array_map('static::wrapJoinParam', $join));
        
        $sql = $columns;
        
        return self::rs_set($sql);
    }

    //编译Union语句
    public static function compileUnion(Builder $build){
        $unions = $build->getSelect('union');
        
        if($unions == [])
            return self::rs_set('');

        $sql = '';
        $params = [];
        foreach($unions as $union){
            list($sq, $param) = 
            self::sqlGenerator([
                'select', 'where'
            ], $union);  
            $sql .= "UNION {$sq} ";
            $params = array_merge($params, $param);
        }
        return self::rs_set($sql, $params);
    }

    //编译插入语句
    public static function compileInsert(Builder $build){
        $insert = $build->getInsert();
        
        if($insert == [])
            return self::rs_set('');
        
        $keys = \implode(',', array_map('static::wrapKeyParam', array_keys($insert)));
        $values = \implode(',', array_fill(0, count($insert), '?'));
        
        $sql = "INSERT INTO `{$build->getTable()}`({$keys}) VALUES({$values})";

        return self::rs_set($sql, \array_values($insert));
    }

    public static function compileUpdate(Builder $build){
        $update = $build->getUpdate();
       
        if($update == [])
            return self::rs_set('');

        $columns = \implode(',', array_map('static::wrapInsertParam', array_keys($update)));
       
        $sql = "UPDATE `{$build->getTable()}` SET $columns ";

        return self::rs_set($sql, \array_values($update));
    }

    //完整语句生成器
    public static function sqlGenerator($array = [], Builder $build){
        $sql = '';
        $params = [];
        foreach($array as $value){
            $method = 'compile' . \ucfirst($value);
            list($sq, $param) = self::$method($build);
            $sql .= $sq;
            $params = array_merge($params, $param);
        }

        return self::rs_set($sql, $params);
    }

    //生成查询的完整语句
    public static function sqlSelect(Builder $build){
        list($sql, $params) = 
            self::sqlGenerator(
                array_keys($build->getSelect()), $build);

        return self::rs_set($sql, $params);
    }

    //生成插入的完整语句
    public static function sqlInsert(Builder $build){
        list($sql, $params) = self::sqlGenerator(
            ['insert'],
            $build
        );

        return self::rs_set($sql, $params);
    }

    //生成更新的完整语句
    public static function sqlUpdate(Builder $build){
        list($sql, $params) = self::sqlGenerator(
            ['update', 'where'],
            $build
        );

        return self::rs_set($sql, $params);
    }

    //生成删除的完整语句
    public static function sqlDelete(Builder $build){
        list($sql, $params) = self::sqlGenerator(
            ['delete'],
            $build
        );

        return self::rs_set($sql, $params);
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
