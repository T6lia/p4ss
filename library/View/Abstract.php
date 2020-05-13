<?php

abstract class View_Abstract
{

    protected $_tVars = array();

    protected $_tHeaders = array();

    protected $_sBody;

    public function addVar($mKey, $sValue = NULL)
    {
        if (!is_array($mKey)) {
            $this->_tVars[$mKey] = $sValue;
        } else {
            foreach ($mKey as $stKey => $stValue) {
                $this->_tVars[$stKey] = $stValue;
            }
        }

        return $this;
    }

    public function getVar($sKey)
    {
        return $this->_tVars[$sKey];
    }

    public function getVars()
    {
        return $this->_tVars;
    }

    public function setBody($sValue)
    {
        $this->_sBody = $sValue;
        return $this;
    }


}
