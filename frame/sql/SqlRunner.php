<?php

namespace Frame\Sql;

class SqlRunner{
    protected $conn;

    protected static $need_params = [
        'where', 'insert', 'update'
    ];

    public function __construct($setting){
        $port     = $setting['port'];
        $host     = $setting['host'];
        $driver   = $setting['driver'];
        $username = $setting['username'];
        $password = $setting['password'];
        $database = $setting['database'];
        $this->conn = new \PDO("{$driver}:dbname={$database};host={$host};port={$port}", $username, $password);
    }

    protected function updateParam($keyword, $params, Builder $build){
        if(\in_array($keyword, self::$need_params)){
            $attr = 'get' . ucfirst($keyword);
            if($keyword == 'where'){
                $params = array_merge($params, $build->getArrParams($keyword, 2));
            }else{
                $params = array_merge($params, array_values($build->$attr()));
            }
        }

        return $params;
    }    

    public function runSelect(Builder $build){
        $sql = '';
        $params = [];
        foreach($build->getSelect() as $key => $value){
            $method = 'compile' . \ucfirst($key);
            $sql .= Grammer::$method($build);
            $params = $this->updateParam($key, $params, $build);
        }
        $sth = $this->conn->prepare($sql);
        $sth->execute($params);
        $result = $sth->fetchAll(\PDO::FETCH_CLASS);

        return $result;
    }

    public function runInsert(Builder $build){
        $sql = Grammer::compileInsert($build);
        $params = array_values($build->getInsert());

        $sth = $this->conn->prepare($sql);
        $sth->execute($params);

        var_dump($sql);

        return $this->conn->lastInsertId();
    }

    public function runUpdate(Builder $build){
        $sql = '';
        $params = [];

        $sql .= Grammer::compileUpdate($build);
        $sql .= Grammer::compileWhere($build);

        $params = $this->updateParam('update', $params, $build);
        $params = $this->updateParam('where', $params, $build);

        $sth = $this->conn->prepare($sql);
        $success = $sth->execute($params);
        
        return $success;
    }

    public function runDelete(Builder $build){
        $sql = "DELETE FROM `{$build->getTable()}` ";
        $params = [];
        
        $sql .= Grammer::compileWhere($build);
        $params = $this->updateParam('where', $params, $build);

        $sth = $this->conn->prepare($sql);
        $success = $sth->execute($params);
        
        return $success;
    }
}


