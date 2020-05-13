<?php
/**
 * Created by PhpStorm.
 * User: Heri
 * Date: 02/02/2018
 * Time: 10:48
 */
/**
 * how to use
 * $importer = Apps::usePlugin('ImportData/CSV);
 * $importer->initialize('test.csv');
 * $data = $importer->getData();
 *
 * Class ImportData_CSV
 */
class ImportData_CSV
{

    protected $_inputFile;
    protected $_tablename;

    public function initialize($file)
    {
        $this->_inputFile = $file;
    }

    public function getData()
    {
        /*$handle     = fopen($this->_inputFile, "r");
        $result = array();
        while (!feof($handle))
        {
            // On recupere toute la ligne
            $uneLigne = addslashes(fgets($handle));
            //On met dans un tableau les differentes valeurs trouvés (ici séparées par un ';')
            $tableauValeurs = explode(';', $uneLigne);
            $result[] = $tableauValeurs;
        }

        return $result;*/
        return $this->_parseXlS($this->_inputFile);
    }

    private function getFileDelimiter($file, $checkLines = 2){
        $file = new SplFileObject($file);
        $delimiters = array(
            ',',
            '\t',
            ';',
            '|',
            ':'
        );
        $results = array();
        $i = 0;
        while($file->valid() && $i <= $checkLines){
            $line = $file->fgets();
            foreach ($delimiters as $delimiter){
                $regExp = '/['.$delimiter.']/';
                $fields = preg_split($regExp, $line);
                if(count($fields) > 1){
                    if(!empty($results[$delimiter])){
                        $results[$delimiter]++;
                    } else {
                        $results[$delimiter] = 1;
                    }
                }
            }
            $i++;
        }
        $results = array_keys($results, max($results));
        return $results[0];
    }

    private function _parseXlS()
    {
        header('Content-Type: text/html; charset=iso-8859-1');
        $row  = array();
        $handle     = fopen($this->_inputFile, "r");
        if(empty($handle) === false) {
            while(($data = fgetcsv($handle, 1000, $this->getFileDelimiter($this->_inputFile))) !== FALSE){
                $donnee = [];
                foreach($data as $value) {
                    $donnee[] =  trim($value);
                }
                $row[] = $donnee;
            }
            fclose($handle);
        }
        return $row;
    }
}