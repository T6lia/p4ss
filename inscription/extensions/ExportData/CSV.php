<?php

/**
 * how to use
  // 'browser' tells the library to stream the data directly to the browser.
  // other options are 'file' or 'string'
  // 'test.xls' is the filename that the browser will use when attempting to
  // save the download

  $exporter = Apps::usePlugin('ExportData/Excel);
  $exporter->initialize('browser', 'test.xls'); // starts streaming data to web browser
  // pass addRow() an array and it converts it to Excel XML format and sends
  // it to the browser
  $exporter->addRow(array("This", "is", "a", "test"));
  $exporter->addRow(array(1, 2, 3, "123-456-7890"));
  // doesn't care how many columns you give it
  $exporter->addRow(array("foo"));
  $exporter->finalize(); // writes the footer, flushes remaining data to browser.
  exit(); // all done
 */

/**
 * ExportDataCSV - Exports to CSV (comma separated value) format.
 */
class ExportData_CSV extends ExportData
{

    public function initialize($exportTo = "browser", $filename = "exportdata") {
        parent::initialize($exportTo, $filename);
        $this->output = fopen('php://output', 'w');
    }

    public function addRow($row) {
        $line = array_map(function ($text) {
            return Text::adjustChar($text);
        }, $row);
        
//        echo implode(';', $line)."\n";
        fputcsv($this->output, $line, ';','"');
    }

    public function sendHttpHeaders() {
        header("Content-type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=" . basename($this->filename));
    }

    public function forcedownload() {
        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=" . basename($this->filename));

        readfile($this->filename);
        exit();
    }

    protected function generateRow($row) {
        
    }

}
