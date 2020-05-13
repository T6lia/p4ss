<?php


class Affilie_AffilieModel extends Model_Abstract
{
    protected $_tablename = 'users_tree';
    protected $_relatedTable = 'users_passvola';

    /**
     * @param $data
     * @return bool
     * Sauvegarder les id du parent et des enfants
     */
    public function saveTree($data) {

        $query = "INSERT INTO {$this->_tablename} (id, id_parent, username, username_parent) 
                 VALUES (:id, :id_parent, :username, :username_parent) ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':id_parent', $data['id_parent']);
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':username_parent', $data['username_parent']);

        return $stmt->execute();
    }

    public function savePackForParent($data) {

        $query = "UPDATE {$this->_relatedTable}  SET pack =:pack WHERE id_appli =:id ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':pack', $data['pack']);

        return $stmt->execute();
    }

    /**
     * @param $data
     * @return array
     * Recupere l'arborescence du membre
     */
    public function getAllMembers($data) {

        $query = "SELECT * FROM {$this->_tablename} tree 
                  WHERE tree.id_parent =:id ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $data['id']);
        $stmt->execute();

        $direct = $stmt->fetchAll(2);

        $finalQuery = "SELECT * FROM {$this->_tablename} ";
        $stmtF = Database::prepare($finalQuery);
        $stmtF->execute();

        $result = [];
        foreach ($direct as $item) {

            $childQuery = "SELECT * FROM {$this->_tablename} tree WHERE id_parent = ".$item['id'];
            $stmt = Database::prepare($childQuery);
            $stmt->execute();
            $result = $stmt->fetchAll(2);

        }

        $tree = array_merge($direct, $result);

        $test = $stmtF->fetchAll(2);
        $final = [];
        foreach ($test as $item) {

            $final[] = $item;
        }

        $final = array_map(
            function ($element) use ($tree) {
                //$element['id'] = $tree;
                //$element['username'] = $element['username'] == '' ? '' : $element['username'];
                //$element['id_parent'] = $element['id_parent'] == '' ? '' : $element['id_parent'];
                //$element['username'] = $element['id_parent'] == '' ? '' : $element['id_parent'];
                return $element;

        }, $final
        );
        return $final;
    }

    public function getAllMembersUser($data) {

        $query = "SELECT DISTINCT * 
                  FROM users_tree parent 
                  JOIN users_tree child ON child.id_parent = parent.id
                  WHERE parent.id =:id ";

        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $data['id']);
        $stmt->execute();
        $direct = $stmt->fetchAll(2);

        if (empty($direct)) {

            return "";

        } else {

            return $this->networkUser($direct);
        }
    }

    private function getArrayIdChild(array $result) {

        if (empty($result)) {

            return "";
        }

        $idArray = [];
        foreach ($result as $item) {
            $idArray[] = "'" . $item['id'] . "'";
        }
        return implode(', ', $idArray);
    }

    private function sqlChild($idChild) {

        if (empty($idChild)) {

            return "";

        } else {

            $query = "SELECT ut.id, ut.username, ut.id_parent, ut.username_parent
                  FROM users_tree ut WHERE ut.id_parent IN (". $idChild.") ";

            $stmt = Database::prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(2);
        }
    }

    private function networkUser($direct) {

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

                $resultFinal = array_merge($direct, $result);
                return $this->formatDataReturn($direct, $resultFinal);
            }
            $id = $this->getArrayIdChild($result);
            $res = $this->sqlChild($id);
            if (empty($res)) {

                $resultFinal = array_merge($direct, $result, $res);
                return $this->formatDataReturn($direct, $resultFinal);
            }
            $idFin = $this->getArrayIdChild($res);
            $bfinal = $this->sqlChild($idFin);
            if (empty($bfinal)) {

                $resultFinal = array_merge($direct, $result, $res, $bfinal);
                return $this->formatDataReturn($direct, $resultFinal);
            }
            $idbfinal = $this->getArrayIdChild($bfinal);
            $resLast = $this->sqlChild($idbfinal);
            if (empty($resLast)) {

                $resultFinal = array_merge($direct, $result, $res, $bfinal, $resLast);
                return $this->formatDataReturn($direct, $resultFinal);
            }
            $idLast = $this->getArrayIdChild($resLast);
            $final = $this->sqlChild($idLast);
            if (empty($final)) {

                $resultFinal = array_merge($direct, $result, $res, $bfinal, $resLast, $final);
                return $this->formatDataReturn($direct, $resultFinal);
            }
            $idA = $this->getArrayIdChild($final);
            $finalAdd = $this->sqlChild($idA);
            if (empty($finalAdd)) {

                $resultFinal = array_merge($direct, $result, $res, $bfinal, $resLast, $final, $finalAdd);
                return $this->formatDataReturn($direct, $resultFinal);
            }
            $idB = $this->getArrayIdChild($finalAdd);
            $finalBdd = $this->sqlChild($idB);
            if(empty($finalBdd)) {
                $resultFinal = array_merge(
                    $direct,
                    $result,
                    $res,
                    $bfinal,
                    $resLast,
                    $final,
                    $finalAdd,
                    $finalBdd
                );
                return $this->formatDataReturn($direct, $resultFinal);
            }
            $idC = $this->getArrayIdChild($finalBdd);
            $finalCdd = $this->sqlChild($idC);
            if (empty($finalCdd)) {

                $resultFinal = array_merge(
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
                return $this->formatDataReturn($direct, $resultFinal);
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

        return $this->formatDataReturn($direct, $resultFinal);
    }

    private function formatDataReturn($direct, $finalResult) {

        $dataReturn = [
            'direct' => $direct,
            'result_final' => $finalResult
        ];
        return $dataReturn;
    }
}