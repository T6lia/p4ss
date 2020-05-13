<?php

/**
 * Created by PhpStorm.
 * Date: 25/04/2018
 * Time: 15:45
 */
class Date_Date
{

    /**
     *
     * Retourne le nombre de jour dans un mois donné pour une anné
     *
     * @param $numjour
     * @param $mois
     * @param $annee
     * @return int
     */
    public function calculNbreJourDsMois($numjour, $mois, $annee) {
        $jour = 1;
        $Nbjour = 0;
        /*
        $SeqJour = '';
        while (false != checkdate($mois, $jour++, $annee)) {
            $SeqJour .= date("l", strtotime($annee . '-' . $mois . '-' . $jour));
        }
        $Nbjour = substr_count($SeqJour, $numjour);
        // */
        //*
        while (checkdate($mois, $jour, $annee)) {
            if ($numjour == date('w', mktime(0, 0, 0, $mois, $jour, $annee))) {
                $Nbjour++;
            }
            $jour++;
        }
        //*/
        return $Nbjour;
    }

    /**
     * retourne le nombre de lundi dans un mois si lundi est marqué 2 avec php :-P
     *
     * @param $mois
     * @param $annee
     * @return int
     */
    public function getCountLundi($mois, $annee) {
        //*
        return (int) $this->calculNbreJourDsMois('2', $mois, $annee);
        //*/
        /*
        $firstday = date("w", mktime(0, 0, 0, $mois, 1, $annee)); 
        $lastday = date("t", mktime(0, 0, 0, $mois, 1, $annee)); 
	$count_weeks = 1 + ceil(($lastday-7+$firstday)/7);
	return $count_weeks;
        //*/
    }

    /**
     * retourne le nombre de semaines dans l'année
     * 
     * @param type $year
     * @return type
     */
    function getIsoWeeksInYear($year) {
        //*
        $date = new DateTime;
        $date->setISODate($year, 53);
        //var_dump($date);die;
        return ($date->format("W") === "53" ? 53 : 52);
        //*/
        /*
        return date('W', mktime(0,0,0,12,28,$year) );
        //*/
    }

    function getWeeksPerMonth($year) {
        $allWeeks = $this->getIsoWeeksInYear($year);
        $allFirstDays = [];
        
        for ($i = 0; $i < $allWeeks; $i++) {
            $allFirstDays[] = date('m/Y', strtotime($year . "W" . str_pad($i, 2, "0", STR_PAD_LEFT)));
        }
        $countWeek = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        foreach ($allFirstDays as $firstDay) {
            $theMonth = explode('/', $firstDay);
            for ($i = 1; $i < 13; $i++) {
                if ($theMonth[0] == $i) {
                    $countWeek[$i - 1]++;
                }
            }
        }
        return $countWeek;
    }
}
