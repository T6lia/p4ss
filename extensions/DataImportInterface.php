<?php
/**
 * Created by PhpStorm.
 * User: houlder
 * Date: 8/29/18
 * Time: 11:39 AM
 */

interface DataImportInterface
{
    public function verifFile($fileName);

    public function getData($datas, $debut, $fin);
}