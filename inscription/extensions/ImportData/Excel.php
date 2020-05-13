<?php
/**
 * Created by PhpStorm.
 * User: Heri
 * Date: 02/02/2018
 * Time: 10:48
 */

/**
 * how to use
 * $importer = Apps::usePlugin('ImportData/Excel);
 * $importer->moveFile('test.xls', '/var/www/tmp/myDirectory');
 * $importer->initialize('test.xls');
 * $data = $importer->getData();
 *
 * Class ImportData_Excel
 */
class ImportData_Excel
{

    protected $_inputFile;

    protected $_absoluteImportDir;

    public function initialize($file, $absoluteDir)
    {
        $this->_inputFile = $file['name'];
        $this->_absoluteImportDir = $absoluteDir;
        move_uploaded_file($file["tmp_name"], $this->_absoluteImportDir."/" . $this->_inputFile);
    }

    /**
     * get data from XLS
     * @return mixed
     */
    public function getData()
    {
        return $this->_parseXlS();
    }

    /**
     * get data from XLSX
     * @return mixed
     */
    public function getDataX()
    {
        return $this->_parseXlSX();
    }

    /**
     * @return string
     */
    private function _parseXlS()
    {
        $data = new ImportData_Reader_XLS($this->_absoluteImportDir . "/" . $this->_inputFile);
        return $data->getSheets(0);
    }

	
	
    /**
     * @return mixed
     */
    private function _parseXlSX()
    {
        /**
         * I had to parse an XLSX spreadsheet (which should damn well have been a CSV!)
         * but the usual tools were hitting the memory limit pretty quick. I found that
         * manually parsing the XML worked pretty well. Note that this, most likely,
         * won't work if cells contain anything more than text or a number (so formulas,
         * graphs, etc ..., I don't know what'd happen).
         */
        @unlink($this->_absoluteImportDir);

        // Unzip
        $zip = new ZipArchive();
        $zip->open($this->_absoluteImportDir."/".$this->_inputFile);
        $zip->extractTo($this->_absoluteImportDir);

        // Open up shared strings & the first worksheet
        $strings = simplexml_load_file($this->_absoluteImportDir . '/xl/sharedStrings.xml');
        $sheet   = simplexml_load_file($this->_absoluteImportDir . '/xl/worksheets/sheet1.xml');

        // Parse the rows
        $headers = array();
        $xlrows = $sheet->sheetData->row;
        $row_cnt = 1;
        foreach ($xlrows as $xlrow) {
            $arr = array();

            // In each row, grab it's value
            foreach ($xlrow->c as $cell) {
                $cellid = preg_replace('/[^A-Z]/i', '', $cell['r']);
                $v = (string) $cell->v;

                // If it has a "t" (type?) of "s" (string?), use the value to look up string value
                if (isset($cell['t']) && $cell['t'] == 's') {
                    $s  = array();
                    $si = $strings->si[(int) $v];

                    // Register & alias the default namespace or you'll get empty results in the xpath query
                    $si->registerXPathNamespace('n', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                    // Cat together all of the 't' (text?) node values
                    foreach($si->xpath('.//n:t') as $t) {
                        $s[] = (string) $t;
                    }
                    $v = implode($s);
                }

                //$arr[] = $v;
                $arr[(string)$cellid] = $v;
            }

            if(count(array_filter($arr)) == 0) {
                continue;
            }

            // Assuming the first row are headers, stick them in the headers array
            if (count($headers) == 0) {
                $headers = $arr;
            } else {
                //Commons::dump($row);
                // Combine the row with the headers
                foreach ($headers as $arrkey => $title) {
                    //assign header name as key and add missing cells
                    $row[$row_cnt][$title] = (isset($arr[$arrkey])) ? $arr[$arrkey] : '';
                    //Commons::dump(array($row_cnt, $title, $arrkey));
                }
                /*} else {
                    // Combine the row with the headers - make sure we have the same column count
                    $values = array_pad($arr, count($headers), '');
                    $row    = array_combine($headers, $values);
    */
                /**
                 * Here, do whatever you like with the [header => value] assoc array in $row.
                 * It might be useful just to run this script without any code here, to watch
                 * memory usage simply iterating over your spreadsheet.
                 */
            }
            $row_cnt++;
        }
        @unlink($this->_absoluteImportDir);
        //@unlink($this->_inputFile);

        return $row;
    }
}