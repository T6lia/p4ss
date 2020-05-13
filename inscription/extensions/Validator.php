<?php
class Validator
{
    /**
     * Checks a filename syntax.
     * @param name: the filename to check.
     * @param validExtensions: array of file extensions ('.pdf', '.word', ..).
     *                         may be empty or NULL.
     * @return: true if ok, false if error
     */
    static function isValidFileName($name, $validExtensions, &$msg)
    {
        $msg = '';

        $name = trim($name);
        if (strlen($name) < 3)  // at least 'x.y'
        {
            $msg = "Le nom de fichier est vide";
            return false;
        }

        // Check if file extension is valid :
        $ext = strrchr($name, '.');
        if ($ext === false)
        {
            $msg = sprintf("Le nom de fichier '%s' n'a pas d'extension", $name);
            return false;
        }

        if (!empty($validExtensions))
        {
            $ext = strrchr($name, '.');
            if ($ext === false)
            {
                $msg = sprintf("Le nom de fichier '%s' n'a pas d'extension", $name);
                return false;
            }
            if (!in_array($ext, $validExtensions))
            {
                $msg = sprintf("Format %s not supported, only %s", $ext, implode(', ', $validExtensions));
                return false;
            }
            $name = substr($name, 0, strlen($name) - strlen($ext));
        }

        // Check if file name uses only legal characters (FIXME: those are the ones legal for Windows):
        if (preg_match('/[^\\/:*?"<>|]+/', $name, $matches) == 0)
            $msg = "Le nom de fichier est incorrect ou inclut 1 ou plusieurs caractères illégaux";

        return $msg == '';
    }

    /**
     * Checks that $url is a syntactically valid http absolute url.
     * NB: The check performed here is really simplistic !!
     * @return: true if ok, false if error
     */
    static function isValidHttpAbsUrl($url, &$msg)
    {
        $msg = '';

        $r = (preg_match("/^http:\/\/[^\/]+([-_?=&0-9a-z\/\.])*$/i", $url) == 1);

        if (!$r)
            $msg = 'Adresse HTTP invalide, le format doit &ecirc;tre http://domaine.xxx/chemin/vers/fichier';
        return $r;
    }


    /**
     * Checks an e-mail address syntax.
     * @return: true if ok, false if error
     */
    static function isValidEmail($email, &$msg)
    {
        $msg = '';

        $r = (preg_match("/^[0-9a-z]([\-_\.]?[0-9a-z])*@[0-9a-z]([\-\.]?[0-9a-z])*\.[a-z]{2,4}$/i", $email) == 1);

        if (!$r)
            $msg = 'Adresse email incorrecte, le format doit &ecirc;tre compte@domaine.ext ';
        return $r;
    }


    static function isValidPhone($phone, &$msg) // very loose check !!
    {
        $msg = '';
        if (preg_match("/^[0-9\(\)\-\.\/ ]+$/", $phone) == 1)
            return true;
        $msg = 'Un no de téléphone doit &ecirc;tre composé uniquement de chiffres, espaces, et caractères ()-./ ';
        return false;
    }


    static function isValidName($name, &$msg) // first or last name
    {
        $msg = '';
        $r = (trim($name) != '');
        if (!$r)
            $msg = 'Le nom ne peut &ecirc;tre vide';
        return $r;
    }


    static function isValidCity($city, &$msg)
    {
        $msg = '';
        $r = (trim($city) != '');
        if (!$r)
            $msg = 'Saisissez une ville SVP';
        return $r;
    }


    static function isValidZip($zip, &$msg)
    {
        $msg = '';
        if (preg_match("/^[0-9]{5}(-[0-9]+)?$/", $zip) == 1)
            return true;
        $msg = 'Le code postal doit faire 5 chiffres + une extension optionnelle';
        return false;
    }


    /**
     * Converts a 12h hour with am/pm to a 24h hour.
     */
    static function hour12To24($hour12, $ampm)
    {
        $hour12 = (int)$hour12;
        if ($hour12 == 12)
            $hour12 = 0;
        return (strtolower($ampm) == 'am') ? $hour12 : $hour12 + 12;
    }

    static function isValidDate($month, $day, $year, &$msg)
    {
        $msg = '';
        $r = checkdate($month, $day, $year);
        if (!$r)
            $msg = 'Date invalide';
        return $r;
    }

    static function isValidSujetTravail($s) {
        $s = trim($s);
        if (strlen($s) > 150)
            return false;

        $filtered = '';
        for ($i=0; $i < strlen($s); ++$i) {
            $c = $s{$i};
            if (ctype_alpha($c) or ctype_digit($c))
                $filtered += $c;
        }
        return strlen($filtered) >= 1;
    }
}