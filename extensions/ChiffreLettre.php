<?php

/**
 * Description of ChiffreLettre
 *
 * @author tahina.lalaina
 */
class ChiffreLettre {

    private $lang = 'fr';

    public function convert($chiffre) {
        $fmt = new NumberFormatter($this->lang, NumberFormatter::SPELLOUT);
        return $fmt->format($chiffre);
    }
    
     private function setConfig($config) {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }

}
