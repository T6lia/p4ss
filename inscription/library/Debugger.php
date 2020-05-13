<?php

class Debugger
{    
    public static function dump($sMixedData)
    {
        echo "<pre>";
        print_r($sMixedData);
        echo "</pre>";
    }
}