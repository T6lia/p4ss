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
 * PHPExcel
 *
 * Copyright (c) 2006 - 2015 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    ##VERSION##, ##DATE##
 *
 * @author tahina.lalaina
 */
class ExportData_HtmlExcel extends ExportData
{

    public $encoding = 'UTF-8'; // encoding type to specify in file.
    // Note that you're on your own for making sure your data is actually encoded to this encoding
    public $title = 'Sheet1'; // title for Worksheet
    public $row = 1;
    private $objPHPExcel;
    private $solid;
    private $extension;
    private $type;
    private $sheet;

    public function __construct() {
        parent::__construct();
        require_once('Excel/htmlexcel/HtmlExcel.php');
        $this->objPHPExcel = new HtmlExcel();
        
        $css = "
.red {
	color: red;
}";

$numbers = '<table>
<tr>
	<td class="red">1</td>
	<td>2</td>
	<td>3</td>
</tr>
<tr>
	<td>4</td>
	<td class="red">5</td>
	<td>6</td>
</tr>
<tr>
	<td>7</td>
	<td>8</td>
	<td class="red">9</td>
</tr>
</table>';

$names = '<table>
  <tr>
    <th>First name</th>
    <th>Last name</th>
  </tr>
  <tr>
    <td>John</td>
    <td>Doe</td>
  </tr>
  <tr>
    <td>Jane</td>
    <td>Doe</td>
  </tr>
</table>';

$xls = new HtmlExcel();
$xls->setCss($css);
$xls->addSheet("Numbers", $numbers);
$xls->addSheet("Names", $names);
$xls->headers();
echo $xls->buildFile();
        
    }

    public function initialize($exportTo = "", $filename = "exportdata") {
        $this->filename = $filename;
        $this->sendHttpHeaders();
    }

    function sendHttpHeaders() {
        $extension = explode('.', $this->filename);
        $this->extension = $extension[count($extension) - 1];
        if ($this->extension == 'xls') {
            header("Content-Type: application/vnd.ms-excel; charset=" . $this->encoding);
            header("Content-Disposition: inline; filename=\"" . basename($this->filename) . "\"");
            $this->type = 'Excel5';
        } elseif ($this->extension == 'xlsx') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . basename($this->filename) . '"');
            $this->type = 'Excel2007';
        } else {
            header('Content-type: text/csv');
            header('Content-Disposition: attachment;filename="' . basename($this->filename) . '"');
            $this->type = 'CSV';
        }
        $this->sheet = $this->objPHPExcel->getActiveSheet();
    }

    public function addRow($row) {
        $this->generateRow($row);
    }

    public function addCells($array) {
        foreach ($array as $key => $value) {
            $this->sheet->setCellValue($key, $value);
            $this->sheet->getStyle($key)->getAlignment()->setWrapText(true);
        }
    }

    public function addFormule($array) {
        foreach ($array as $key => $value) {
            $this->sheet->setCellValue($key, $value, PHPExcel_Cell_DataType::TYPE_FORMULA);
        }
    }

    protected function generateRow($row) {
        $col = 0;
        foreach ($row as $value) {
            $this->sheet->setCellValueByColumnAndRow($col, $this->row, $value);
            $col++;
        }
        $this->row++;
    }

    public function merge($cells) {
        $this->sheet->mergeCells($cells);
    }

    public function merges($cells) {
        foreach ($cells as $item)
            $this->sheet->mergeCells($item);
    }

    public function finalize() {
        $this->sheet->setTitle($this->title);
        $this->objPHPExcel->setActiveSheetIndex(0);

        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, $this->type);
        $objWriter->save('php://output');
    }

    public function addImage($param = array()) {
        $objDrawing = new PHPExcel_Worksheet_Drawing();
        if (key_exists('name', $param))
            $objDrawing->setName($param['name']);
        if (key_exists('description', $param))
            $objDrawing->setDescription($param['description']);
        if (key_exists('path', $param))
            $objDrawing->setPath($param['path']);
        if (key_exists('col', $param))
            $objDrawing->setCoordinates($param['col']);
        $objDrawing->setOffsetX(5);
        $objDrawing->setOffsetY(5);
        $objDrawing->setWidth(100);
        $objDrawing->setHeight(35);
        $objDrawing->setWorksheet($this->objPHPExcel->getActiveSheet());
    }

    public function rotate($param) {
        foreach ($param as $item) {
            $this->objPHPExcel->getActiveSheet()->getStyle($item)->getAlignment()->setTextRotation(90);
        }
    }

    public function setBorder($param, $allCells = true, $color = '000000') {
        $type = $allCells ? 'allborders' : 'outline';
        foreach ($param as $key => $value) {
            $styleArray = array(
                'borders' => array(
                    $type => array(
                        'style' => $this->{$value},
                        'color' => array('rgb' => $color)
                    )
                )
            );
            $this->sheet->getStyle($key)->applyFromArray($styleArray);
        }
    }

    public function setDimension($aDimension) {
        foreach ($aDimension as $key => $value) {
            $this->sheet->getColumnDimension($key)->setWidth($value);
        }
    }

    public function setFontStyle($aParams) {
        foreach ($aParams as $key => $styleArray) {
            $this->sheet->getStyle($key)->applyFromArray($styleArray);
        }
    }

    public function freeze($cells) {
        foreach ($cells as $value) {
            $this->sheet->freezePane($value);
        }
    }

    public function setBg($aParams) {
        foreach ($aParams as $key => $value) {
            $this->sheet->getStyle($key)
                    ->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $this->sheet->getStyle($key)
                    ->getFill()->getStartColor()->setRGB($value);
        }
    }

    public function center($cells) {
        foreach ($cells as $value) {
            $style = array(
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                )
            );
            $this->sheet->getStyle($value)->applyFromArray($style);
        }
    }

}
