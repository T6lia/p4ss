<?php



/**
 * how to use
 * $pdf = Apps::usePlugin('ExportData/PDF);
 * $pdf->initialize($html, $config);
 * $pdf->generate();
 *
 * @author tahina.lalaina
 * 
 * Class ExportData_PDF
 */
class ExportData_PDF {

    protected $_inputFile;
    /**
     * @var TCPDF
     */
    private $pdf;
    private $content = "";
    private $contents = [];
    //Les paramètre suivant sont modifiable depuis config
    private $author = "";
    private $title = "EXPORT PDF";
    private $logo = '../../../../themes/images/logo-pdf.png';
    private $logoWidth = '30';
    private $headerTitle = '';
    private $font = 'dejavusans';
    private $style = '';
    private $fontSize = 11;
    private $orientation = 'P';
    private $format = 'A4';
    private $encoding = 'UTF-8';
    private $margin_left = 15;
    private $margin_right = 15;
    private $margin_top = 25;

    public function initialize($content, $config = array()) {
        $this->setConfig($config);
        require_once('tcpdf/plugin/tcpdf_include.php');
        $this->configPDF();
        if (is_array($content)) {
            $this->contents = $content;
        } else {
            $this->content = $content;
        }
    }

    public function generate() {
        if (!empty($this->contents)) {
            return $this->generateMultiple();
        }
        return $this->generateOne();
    }

    private function setDocumentName() {
        return preg_replace('#^.([^.a-z0-9]+)#', '-', $this->title) . '.pdf';
    }

    private function setConfig($config) {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }

    private function configPDF() {

        require_once('tcpdf/tcpdf.php');
// create new PDF document
        $this->pdf = new TCPDF($this->orientation, PDF_UNIT, $this->format, true, $this->encoding, false);
        $this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
// set document information
        
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor($this->author);
        $this->pdf->SetTitle($this->title);
        $this->pdf->SetHeaderData($this->logo, $this->logoWidth, $this->headerTitle, $this->headerTitle,array(0,0,0),array(255,255,255));
// set margins
        $this->pdf->SetMargins($this->margin_left, $this->margin_top, $this->margin_right);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
// set auto page breaks
        $this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $this->pdf->SetFont($this->font, $this->style, $this->fontSize, '', true);
        //turn off the footer (false)
        $this->pdf->setPrintFooter(false);
    }

    public function generateMultiple()
    {
        foreach ($this->contents as $contenue) {
            $this->pdf->AddPage();
            $this->pdf->writeHTML($contenue,true, false, true, false, '');
        }

        ob_end_clean();
        return $this->pdf->Output($this->setDocumentName(), 'I');
    }

    public function generateOne()
    {
        $this->pdf->AddPage();
        $this->pdf->writeHTMLCell(0, 0, '', '', $this->content, 0, 1, 0, true, '', true);
        
        ob_end_clean();
        return $this->pdf->Output($this->setDocumentName(), 'I');
    }
}

