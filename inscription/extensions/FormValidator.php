<?php

/**
 * Description of FormValidator
 *
 * @author tahina.lalaina
 */
class FormValidator {

    private $isValid = TRUE;
    private $errorMessage = array();

    public function validate($data, $validator) {
        foreach ($data as $key => $value) {
            $DataValidator = key_exists($key, $validator) ? $validator[$key] : false;
            $error = array();
            if ($DataValidator) {
                foreach ($DataValidator as $ItemValidator) {
                    if (is_array($ItemValidator)) {
                        $method = key($ItemValidator);
                        if (method_exists($this, $method)) {
                            $check = $this->{$method}($value, $ItemValidator[$method]);
                            if ($check !== TRUE) {
                                $this->isValid = FALSE;
                                $error[] = $check;
                            }
                        }
                    } else {
                        if (method_exists($this, $ItemValidator)) {
                            $check = $this->{$ItemValidator}($value);
                            if ($check !== TRUE) {
                                $this->isValid = FALSE;
                                $error[] = $check;
                            }
                        }
                    }
                }
            }
            if (!empty($error))
                $this->errorMessage[$key] = implode(' ,', $error);
        }
    }

    private function required($value) {
        if ($value != null && $value != '')
            return true;
        else
            return "Champ obligatoire";
    }

    private function text($value) {
        if ($value != null && $value != '')
            return true;
        else
            return "Texte invalide";
    }

    private function numeric($value) {
        if (is_numeric($value))
            return TRUE;
        else
            return "Nombre invalide";
    }

    private function minlength($value, $param) {
        if (strlen($value) >= $param)
            return TRUE;
        else
            return "minimun $param caractères";
    }

    private function maxlength($value, $param) {
        if (strlen($value) <= $param)
            return TRUE;
        else
            return "maximum $param caractères";
    }

    
    public function isValid() {
        return $this->isValid;
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }

}
