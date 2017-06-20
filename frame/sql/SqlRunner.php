<?php

namespace Dawndevil\Sql;

class SqlRunner{
    protected $conn;

    public function __construct($setting){
        $port     = $setting['port'];
        $host     = $setting['host'];
        $driver   = $setting['driver'];
        $username = $setting['username'];
        $password = $setting['password'];
        $database = $setting['database'];
        $this->conn = new \PDO("{$driver}:dbname={$database};host={$host};port={$port}", $username, $password);
    }

    

    public function runSelect(Builder $build){
        list($sql, $params) = Grammer::sqlSelect($build);

        $sth = $this->conn->prepare($sql);
        $sth->execute($params);
        $result = $sth->fetchAll(\PDO::FETCH_CLASS);

        return $result;
    }

    public function runInsert(Builder $build){
        list($sql, $params) = Grammer::sqlInsert($build);
        
        $sth = $this->conn->prepare($sql);
        $sth->execute($params);


        return $this->conn->lastInsertId();
    }

    public function runUpdate(Builder $build){
        list($sql, $params) = Grammer::sqlUpdate($build);

        $sth = $this->conn->prepare($sql);
        $success = $sth->execute($params);
        
        return $success;
    }

    public function runDelete(Builder $build){
        list($sql, $params) = Grammer::sqlDelete($build);

        $sth = $this->conn->prepare($sql);
        $success = $sth->execute($params);
        
        return $success;
    }
}


