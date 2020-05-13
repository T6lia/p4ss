<?php


class Comptabilite_ComptabiliteModel extends Model_Abstract
{
    protected $_tablename = 'users_mouvement';
    protected $id = 'id';

    /**
     * @param $data
     * @return bool
     * Creer le mouvement
     */
    public function create($data) {

        $query = " INSERT INTO {$this->_tablename} (mouvement, valeur, mode, id_user, date_mouvement) 
                  VALUES (:mouvement, :valeur, :mode, :id_user, :date_mouvement) ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':mouvement', $data['mouvement']);
        $stmt->bindParam(':valeur', $data['valeur']);
        $stmt->bindParam(':mode', $data['mode']);
        $stmt->bindParam(':id_user', $data['id_user']);
        $stmt->bindParam(':date_mouvement', $data['date_mouvement']);

        return $stmt->execute();

    }

    /**
     * @param $idAppli
     * @return array
     * Recupere toutes les mouvements pour un utilisateur
     */
    public function getMouvementDetail($idAppli) {

        $query = "SELECT * FROM {$this->_tablename} WHERE id_user =:id_user ";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id_user', $idAppli);
        $stmt->execute();

        return $stmt->fetchAll(2);
    }

    /**
     * @param $iAppli
     * @return array
     * recupere le credit (depense de l'utilisateur)
     */
    public function getCredit($idAppli) {

        $query = "SELECT SUM(valeur) as credit FROM {$this->_tablename} WHERE mode = 'credit' AND id_user =:id_user ";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id_user', $idAppli);
        $stmt->execute();

        return $stmt->fetch(2);
    }

    /**
     * @param $idAppli
     * @return mixed
     * Recupere le debit
     */
    public function getDebit($idAppli) {

        $query = "SELECT SUM(valeur) as debit FROM {$this->_tablename} WHERE mode = 'debit' AND id_user =:id_user ";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id_user', $idAppli);
        $stmt->execute();

        return $stmt->fetch(2);
    }

    /**
     * @param $iAppli
     * @return array
     * recupere le credit (depense de l'utilisateur)
     */
    public function getAllCredit($idAppli) {

        $query = "SELECT mouvement, valeur, date_mouvement FROM {$this->_tablename} WHERE mode = 'credit' AND id_user =:id_user ";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id_user', $idAppli);
        $stmt->execute();

        return $stmt->fetchAll(2);
    }

    /**
     * @param $idAppli
     * @return mixed
     * Recupere le debit
     */
    public function getAllDebit($idAppli) {

        $query = "SELECT mouvement, valeur, date_mouvement FROM {$this->_tablename} WHERE mode = 'debit' AND id_user =:id_user ";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id_user', $idAppli);
        $stmt->execute();

        return $stmt->fetchAll(2);
    }

    public function debitDirect($idAppli) {
        
        $query = "SELECT DISTINCT * 
                  FROM users_tree parent 
                  JOIN users_tree child ON child.id_parent = parent.id
                  WHERE parent.id =:id
                  ORDER BY parent.username ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $idAppli);
        $stmt->execute();
        $direct = $stmt->fetchAll(2);

        if (empty($direct)) {
            return "";
        } else {

            $resultFinal = $this->networkDebit($direct);
            $dataReturn = [
                'direct' => count($direct),
                'reste' => count($resultFinal)
            ];
            return $dataReturn;

        }
    }

    private function getArrayIdChild(array $result) {
        $idArray = [];
        foreach ($result as $item) {
            $idArray[] = "'" . $item['id'] . "'";
        }
        return implode(', ', $idArray);
    }

    private function sqlChild($idChild) {

        $query = "SELECT ut.id, ut.username, ut.id_parent, ut.username_parent
                       FROM users_tree ut WHERE ut.id_parent IN ($idChild)";

        $stmt = Database::prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(2);
    }

    private function networkDebit($direct) {

        $result = [];
        $res = [];
        $bfinal = [];
        $resLast = [];
        $final = [];
        $finalAdd = [];
        $finalBdd = [];
        $finalCdd = [];
        $finalDdd = [];

        if (!empty($direct)) {

            $idChild = $this->getArrayIdChild($direct);
            $result = $this->sqlChild($idChild);
            if (empty($result)) {

                return $resultFinal = array_merge($direct, $result);
            }
            $id = $this->getArrayIdChild($result);
            $res = $this->sqlChild($id);
            if (empty($res)) {
                return $resultFinal = array_merge($direct, $result, $res);
            }
            $idFin = $this->getArrayIdChild($res);
            $bfinal = $this->sqlChild($idFin);
            if (empty($bfinal)) {
                return $resultFinal = array_merge($direct, $result, $res, $bfinal);
            }
            $idbfinal = $this->getArrayIdChild($bfinal);
            $resLast = $this->sqlChild($idbfinal);
            if (empty($resLast)) {
                return $resultFinal = array_merge($direct, $result, $res, $bfinal, $resLast);
            }
            $idLast = $this->getArrayIdChild($resLast);
            $final = $this->sqlChild($idLast);
            if (empty($final)) {
                return $resultFinal = array_merge($direct, $result, $res, $bfinal, $resLast, $final);
            }
            $idA = $this->getArrayIdChild($final);
            $finalAdd = $this->sqlChild($idA);
            if (empty($finalAdd)) {
                return $resultFinal = array_merge($direct, $result, $res, $bfinal, $resLast, $final, $finalAdd);
            }
            $idB = $this->getArrayIdChild($finalAdd);
            $finalBdd = $this->sqlChild($idB);
            if(empty($finalBdd)) {
                return $resultFinal = array_merge(
                    $direct,
                    $result,
                    $res,
                    $bfinal,
                    $resLast,
                    $final,
                    $finalAdd,
                    $finalBdd
                );
            }
            $idC = $this->getArrayIdChild($finalBdd);
            $finalCdd = $this->sqlChild($idC);
            if (empty($finalCdd)) {
                return $resultFinal = array_merge(
                    $direct,
                    $result,
                    $res,
                    $bfinal,
                    $resLast,
                    $final,
                    $finalAdd,
                    $finalBdd,
                    $finalCdd
                );
            }

            $idD = $this->getArrayIdChild($finalCdd);
            $finalDdd = $this->sqlChild($idD);
        }

        $resultFinal = array_merge(
            $direct,
            $result,
            $res,
            $bfinal,
            $resLast,
            $final,
            $finalAdd,
            $finalBdd,
            $finalCdd,
            $finalDdd
        );
        return $resultFinal;
    }

}