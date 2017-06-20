<?php

namespace Dawndevil\Sql;


class Builder{
    //表名
    protected $table;

    //Dawndevil\Sql\SqlRunner
    protected $conn;
    
    //查询器绑定顺序
    protected $select = [
        'select' => ['*'],
        'join'   => [],
        'where'  => [],
        'order'  => [],
        'union'  => [],
        'limit'  => []
    ];

    //插入绑定器
    protected $insert = [];
    
    //更新绑定器
    protected $update = [];
    
    //删除绑定器
    protected $delete = [];
    


    public function __construct($table, $conn){
        $this->table   = $table;
        $this->conn    = $conn;
    }


    /*
    |把where的参数统一封装为一个模式
    |[[key1, operation, key2],...]
    */
    protected function wrapWhere($args = []){
        $new_args = [];

        if(count($args) === count($args, 1)){
            switch(count($args)){
                case 2 : $new_args = [[$args[0], '=', $args[1]]]; break;
                case 3 : $new_args = [[$args[0], $args[1], $args[2]]]; break;
                default:  throw new \Exception('where params wrong');
            }
        
        }else{
            foreach($args as $v){
                switch(count($v)){
                    case 2 : $new_args[] = [$v[0], '=', $v[1]]; break;
                    case 3 : $new_args[] = [$v[0], $v[1], $v[2]]; break;
                }
            }
        }

        return $new_args;
    }
    /*
    |把order by的参数统一封装为一个模式
    |[[name, order],...]
    */
    protected function wrapOrder($args = []){
        $new_args = [];

        if(count($args) === count($args, 1)){
            switch(count($args)){
                case 1:  $new_args = [[$args[0], 'desc']];break;
                case 2:  $new_args = [[$args[0], $args[1]]];break;
                default: throw new \Exception('order by params wrong');
            }
        }else{
            foreach($args as $v){
               if(count($v) === 2){
                    $new_args[] = [$v[0], $v[1]];
               }else{
                    throw new \Exception('order by params wrong');
               }
            }
        }

        return $new_args;
    }

    /*
    |把join的参数统一封装为一个模式
    |[[left|right|inner, table, key1, operation, key2],...]
    */
    protected function wrapJoin($args, $way){
        $new_args = [];
        if(count($args) === count($args, 1)){
            if(count($args) === 4){
                $new_args[] = [$way];
                $new_args[0] = array_merge($new_args[0], $args);
            }else{
                throw new \Exception('join param wrong!');
            }
        }else{
            foreach($args as $v){
                if(count($v) === 4){
                    $new_args[] = [$way, $v[0], $v[1], $v[2], $v[3]];
                }else{
                    throw new \Exception('join param wrong!');
                }
            }
        }

        return $new_args;
    }



    public function select($args = ['*']){
        $this->select['select'] = \is_array($args) ? $args : \func_get_args();

        return $this;
    }

    public function where($args = []){
        $args = \is_array($args) ? $args :  \func_get_args();
        $new_args = $this->wrapWhere($args);
        $this->select['where'] = array_merge($this->select['where'], $new_args);
        
        return $this;
    }

    public function orderBy($args = []){
        $args = \is_array($args) ? $args :  \func_get_args();
        $new_args = $this->wrapOrder($args);
        $this->select['order'] = array_merge($this->select['order'], $new_args);

        return $this;
    }

    public function limit($limit, $offset = 0){
        $this->select['limit'] = [$offset, $limit];

        return $this;
    }

    protected function join($way = 'inner', $args){
        $new_args = $this->wrapJoin($args, $way);
        $this->select['join'] = array_merge($this->select['join'], $new_args);
        var_dump($this->select['join'][0]);
        return $this;
    }

    public function innerJoin($args = []){
        return $this->join('INNER', \is_array($args) ? $args : func_get_args());
    }

    public function leftJoin($args = []){
        return $this->join('LEFT', \is_array($args) ? $args : func_get_args());
    }

    public function rightJoin($args = []){
        return $this->join('RIGHT', \is_array($args) ? $args : func_get_args());
    }

    public function insert($args = []){
        if(count($args) != count($args, 1))
            throw new \Exception('insert param is wrong');
        
        $this->insert = $args;
        
        return $this->conn->runInsert($this);
    }

    public function union(Builder $build){
        if($build != NULL)
            $this->select['union'][] = $build;
        
        return $this;    
    }

    public function update($args = []){
        if(count($args) != count($args, 1))
            throw new \Exception('update param is wrong');
        
        $this->update = $args;

        return $this->conn->runUpdate($this);
    }

    public function delete(){
        return $this->conn->runDelete($this);
    }

    //获取select查询构造器里的元素
    public function getSelect($key = NULL){
        if(is_null($key)){
            return $this->select;
        }

        return isset($this->select[$key]) ? $this->select[$key] : NULL;
    }

    //获取插入构造器的内容
    public function getInsert(){
        return $this->insert;
    }

    //获取更新构造器的内容
    public function getUpdate(){
        return $this->update;
    }

    /*
    |获取二维数组中每个一维数组固定位置上的元素
    |$arrays Array
    |$postion Int
    */
    public function getArrParams($arrays = [], $postion){
        if(!is_array($arrays)){
            $arrays = isset($this->select[$arrays]) ? $this->select[$arrays] : [];
        }
        $params = [];
        if(count($arrays) != count($arrays, 1)){
            foreach($arrays as $array){
                $params[] = $array[$postion];
            }
        }

        return $params;
    }

    public function getTable(){
        return $this->table;
    }

    //开始查询，并返回结果
    public function get(){
        $result = $this->conn->runSelect($this);

        return $result;
    }

}
