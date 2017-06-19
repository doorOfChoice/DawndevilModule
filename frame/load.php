<?php

$files = \scandir(__DIR__);

function recursive_load($files, $root){
    $files = array_diff($files, ['.', '..']);
    foreach($files as $file){
        $file = $root . '/' . $file;
        if(is_dir($file)){
            recursive_load(\scandir($file), $root . '/' . basename($file));
        }else{
            require_once $file;
        }
    }
}

recursive_load($files, __DIR__);