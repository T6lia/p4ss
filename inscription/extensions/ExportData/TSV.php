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
 * ExportDataTSV - Exports to TSV (tab separated value) format.
 */
class ExportData_TSV extends ExportData
{

    function generateRow($row)
    {
        foreach ($row as $key => $value) {
            // Escape inner quotes and wrap all contents in new quotes.
            // Note that we are using \" to escape double quote not ""
            $row[$key] = '"' . str_replace('"', '\"', $value) . '"';
        }
        return implode("\t", $row) . "\n";
    }

    function sendHttpHeaders()
    {
        header("Content-type: text/tab-separated-values");
        header("Content-Disposition: attachment; filename=" . basename($this->filename));
    }
}