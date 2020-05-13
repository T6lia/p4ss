<?php
class  Database
{
    
    private static function connect()
    {
        $ini =  APPS_PATH . "/configs/database.ini" ;
        $parse = parse_ini_file ( $ini , true ) ;

        $driver = $parse [ "db_driver" ] ;
        $dsn = "${driver}:" ;
        $user = $parse [ "db_user" ] ;
        $password = $parse [ "db_password" ] ;
        $options = $parse [ "db_options" ] ;
        $attributes = $parse [ "db_attributes" ] ;

        foreach ( $parse [ "dsn" ] as $k => $v ) {
            $dsn .= "${k}=${v};" ;
        }
        $db = new PDO( $dsn, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")  ) ;
        
        //$db->exec("SET CHARACTER SET " . $parse [ "db_character_set" ]);

        foreach ( $attributes as $k => $v ) {
            $db->setAttribute ( constant ( "PDO::{$k}" )
                , constant ( "PDO::{$v}" ) ) ;
        }     
        
        $db->exec("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

        return $db;
    }

    public static function prepare ( $statement ) {
        $db = Database::connect();        
        return $db->prepare( $statement );                
    }

    public static function lastInsertId($tablename, $primaryKey = 'id')
    {
        $db = Database::connect();
        $stmt = $db->prepare( "SELECT MAX($primaryKey) as MID FROM " . $tablename );
        $stmt -> execute ( ) ;
        $row = $stmt->fetchObject();

         return (int)$row->MID;
    }
}