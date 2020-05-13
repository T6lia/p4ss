<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require 'PHPExcel.php';

class Excel_PHPExportStandard {

	/**
	 * Variable pour verifier si l'aotosize est activer ou non
	 * @var type boolean
	 */
	private $autoSize = true;

	/**
	 * Variable de stockage des css pour les cellul cible
	 * @var type array()
	 */
	private $fontStyleHeader = array();

	/**
	 * Comptage des ranger pour l'insertion des donnée csv,xls,xlsx
	 * @var type integer
	 */
	private $rowCount = 0;

	/**
	 * Stockage du chemin du fichier temporaire dans la disqu dur
	 * @var type String
	 */
	private $path = "";

	/**
	 * Stockage courente de l'objet PHPExcel instentié l'ors de la processus de l'export
	 * @var type Object
	 */
	public $oPHPExcel;

	/**
	 * Liste des alphabet colonne du fichier excel
	 * @var type array(
	 */
	private $alphabet = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

	/**
	 * Constructeur de depart de l'extension:
	 * 			1 -Assignation de l'Objet new PHPExcel() dans $this->oPHPExcel
	 */
	public function __construct() {
		$this->oPHPExcel = new PHPExcel();
	}

	//-------------------------------------------PUBLIC FUNCTION---------------------------

    /**
     * Fonction public pour l'activation des css font du header
     * @param array $style array()
     */
	public function setHeaderFontStyle($style = array()) {
		$this->fontStyleHeader = $style;
	}

    /**
     * Fonction public pour la creation de tous les contenues
     * @param array $row
     * @throws PHPExcel_Exception
     */
	public function addRowsX($row = array()) {
		$this->assigneRows($row);
	}

    /**
     * Fonction public pour la creation de tous les contenues
     * @param array $row
     */
	public function addRowX($row = array()) {
		$this->assigneRow($row);
	}

    /**
     * Fonction public pour la creation d'un header
     * @param array $row
     * @throws PHPExcel_Exception
     */
	public function addRowsHeaderX($row = array()) {
		$this->assigneRowsHeader($row);
	}

    /**
     * Initialisation des information du fichier a créer
     * @param type $filePath String
     * @param type $fileName String
     * @throws PHPExcel_Exception
     */
	public function initialize($filePath , $fileName) {
		$this->mangeTempDir($filePath);
		$this->path = $filePath . $fileName;
		$this->oPHPExcel->setActiveSheetIndex(0);
	}

    /**
     * Finalisation du fichier créer et activation du download
     * @throws PHPExcel_Writer_Exception
     */
	public function finalize() {
		$objWriter = new PHPExcel_Writer_Excel2007($this->oPHPExcel);
		$objWriter->save($this->path);
		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary");
		header("Content-disposition: attachment; filename=\"" . basename($this->path) . "\"");
		readfile($this->path);
	}

    /**
     * Autorisation ou non de l'option autosize des celluls
     * @param bool $autoSizer boolean
     */
	public function isAutoSize($autoSizer = true) {
		$this->autoSize = $autoSizer;
	}

	//-------------------------------------------------------------

    /**
     * Fonction private pour la creation de tous les contenues
     * @param array $row
     * @throws PHPExcel_Exception
     */
	private function assigneRows($row = array()) {
		foreach ($row as $key => $datas) {
			$this->rowCount ++;
			foreach ($datas as $line => $data) {
				if (is_integer($line))
					$this->oPHPExcel
							->getActiveSheet()
							->SetCellValue($this->alphabet[$line] . $this->rowCount, $row[$key][$line]);
				$this->autoSize();
			}
		}
	}

    /**
     * Fonction private pour la creation d'un header
     * @param array $row
     * @throws PHPExcel_Exception
     */
	private function assigneRowsHeader($row = array()) {
		$this->rowCount ++;
		foreach ($row as $line => $data) {
			$this->oPHPExcel->getActiveSheet()->SetCellValue($this->alphabet[$line] . $this->rowCount, $data);
			if (count($this->fontStyleHeader)) {
				$this->oPHPExcel
						->getActiveSheet()
						->getStyle($this->alphabet[$line] . $this->rowCount)
						->applyFromArray($this->fontStyleHeader);
				$this->autoSize();
			}
		}
	}

	/**
	 * Gestion de création ou de netoyage du dossier temporaire pour la création du fichier excel
	 * @param $filePath String
	 */
	private function mangeTempDir($filePath) {
		if (!is_dir($filePath)) {

		    //var_dump($filePath); die;
			mkdir($filePath, 0700);
		} else {
			$files = glob($filePath . '/*'); //get all file names
			foreach ($files as $file) {
				if (is_file($file))
					unlink($file); //delete file
			}
		}
	}

    /**
     * Fonction autosize des celluls
     * @throws PHPExcel_Exception
     */
	private function autoSize() {
		if ($this->autoSize) {
			$sheet = $this->oPHPExcel->getActiveSheet();
			$cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(true);
			/** @var PHPExcel_Cell $cell */
			foreach ($cellIterator as $cell) {
				$sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
			}
		}
	}

	private function assigneRow($row = array()) {
		$this->rowCount ++;
		foreach ($row as $line => $data) {
				if (is_integer($line))
					$this->oPHPExcel
							->getActiveSheet()
							->SetCellValue($this->alphabet[$line] . $this->rowCount, $row[$line]);
				$this->autoSize();
			}
	}

}
