<?php

abstract class Model_Abstract
{

    public $_id; //table primary key
    protected $_tablename; //tablename
    protected $_data; //table cols

    public function getTablename() {
        return $this->_tablename;
    }

    /**
     * @param array $aDataRequest
     * @param string $sTableAlias
     *
     * @return array
     */
    public function buildStandardWhere($aDataRequest, $sTableAlias = 't')
    {
        $aWhere = [];
        $aParam = [];
        $aWhere[] = " 1 = 1 ";

        foreach ($aDataRequest as $key => $value) {
            if (!is_null($value)) {
                $aWhere[] = " $sTableAlias.$key = :$key ";
                $aParam[$key] = $value;
            }
        }

        return ['aWhere' => $aWhere, 'aParam' => $aParam];
    }

    protected function save() {
        // update
        if ($this->_data[$this->_id] != NULL) {
            $query = "UPDATE {$this->_tablename} SET ";

            $queryData = array();
            foreach ($this->_data as $key => $data) {
                $queryData[] = " $key = '$data'";
            }

            $query .= implode(',', $queryData);
            $query .= " WHERE {$this->_id} = :id";

            $stmt = Database::prepare($query);
            $stmt->bindParam(':id', $this->_data[$this->_id]);
            $stmt->execute();
            //save new
        } else {
            $keyData = [];
            $queryData = [];
            foreach ($this->_data as $key => $data) {
                $queryData[] = $data;
                $keyData[] = $key;
            }
            $query = "INSERT INTO {$this->_tablename} (" . implode(',', $keyData) . ") VALUES ('" . implode("','", $queryData) . "')";
            $keyData = [];
            $queryData = [];
            foreach ($this->_data as $key => $data) {
                $queryData[] = $data;
                $keyData[] = $key;
            }

            $stmt = Database::prepare($query);
            $stmt->execute();
        }

        return $this;
    }

    protected function delete($id, $table = NULL) {
        if ($table != NULL) {
            $this->_tablename = $table;
        }

        $query = "DELETE FROM {$this->_tablename} WHERE {$this->_id} = :id";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $this;
    }

    protected function load($id, $setData = true) {
        $query = "SELECT * FROM {$this->_tablename} WHERE {$this->_id} = :id";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch();

        if ($result[$this->_id] > 0) {
            if ($setData) {
                $this->_data = $result;
                return $this;
            } else {
                return $result;
            }
        } else {
            return FALSE;
        }
    }

    public function setData($key, $data = null) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->_data[$k] = $v;
            }
        } else {
            $this->_data[$key] = $data;
        }

        return $this;
    }

    public function getData($key = null) {
        if ($key != null) {
            return $this->_data[$key];
        } else {
            return $this->_data;
        }
    }

    public function paginate($offset = 0, $limit = 10, $filters = array(), $orders = array(), $ordered = true, $where = array()) {
        if (count($where) == 0) {
            $where = array("1=1");
        }
        $values = array();

        foreach ($filters as $key => $filter) {
            if ($filter[0] != '') {
                $operateur = $filter[1];
                if ($filter[1] == 'LIKE') {
                    $value = "%$filter[0]%";
                } else {
                    $value = "$filter[0]";
                }
                $where[] = " $key $operateur :$key ";
                $values[":$key"] = $value;
            }
        }

        $aOrder = array();
//        $sOrder;
        foreach ($orders as $key => $type) {
            if (!is_null($type)) {
                $type = $type == "ASC" || $type == "DESC" ? $type : "ASC";
                $aOrder[] = " $key $type ";
            }
        }
        $sOrder = "";
        if (!empty($aOrder)) {
            $sOrder = " ORDER BY " . implode(',', $aOrder);
        } elseif ($ordered) {
            $sOrder = "ORDER BY code DESC";
        }

        $where = implode(' AND ', $where);
        $query = "SELECT * "
                . "FROM " . $this->_tablename . " "
                . "WHERE $where "
                . " $sOrder "
                . "LIMIT {$limit} OFFSET {$offset} ";
        $stmt = Database::prepare($query);
//		var_dump($query);die;
        if (!empty($values)) {
            $stmt->execute($values);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(2);
    }

    public function countData($filters = array()) {
        $where = array("1=1");
        $values = array();

        foreach ($filters as $key => $filter) {
            if ($filter[0] != '') {
                $operateur = $filter[1];
                if ($filter[1] == 'LIKE') {
                    $value = "%$filter[0]%";
                } else {
                    $value = "$filter[0]";
                }
                $where[] = " $key $operateur :$key ";
                $values[":$key"] = $value;
            }
        }

        $where = implode(' AND ', $where);

        $query = "SELECT count(*) number "
                . "FROM " . $this->_tablename
                . " WHERE $where ";
        $stmt = Database::prepare($query);
        if (!empty($values)) {
            $stmt->execute($values);
        } else {
            $stmt->execute();
        }

        return $stmt->fetch(7);
    }

    public function paginateQuery($query, $param, $offset = 0, $limit = 10, $orders = array(), $ordered = true) {
        $values = array();
        foreach ($param as $key => $value) {
            $values[":$key"] = $value;
        }

        $aOrder = array();
        $sOrder = "";

//        if ($ordered) {
        foreach ($orders as $key => $type) {
            if (!is_null($type)) {
                $type = $type == "ASC" || $type == "DESC" ? $type : "ASC";
                $aOrder[] = " $key $type ";
            }
        }
//        }

        if (!empty($aOrder)) {
            $sOrder = 'ORDER BY ' . implode(',', $aOrder);
        } else if ($ordered) {
            $sOrder = "ORDER BY  code DESC";
        }

        $query .= " $sOrder "
                . "LIMIT {$limit} OFFSET {$offset} ";

        $stmt = Database::prepare($query);

        if (!empty($values)) {
            $stmt->execute($values);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(2);
    }

    public function countDataQuery($queryCount, $param) {
        $values = array();
        foreach ($param as $key => $value) {
            $values[":$key"] = $value;
        }

        $stmt = Database::prepare($queryCount);
        if (!empty($values)) {
            $stmt->execute($values);
        } else {
            $stmt->execute();
        }

        return $stmt->fetch(7);
    }

    /**
     * @param array $param
     * @param bool $one
     * @param bool $object
     * @param array $column
     *
     * @return array|mixed
     */
    public function getBy(array $param = array(), $one = FALSE, $object = FALSE, array $column = []) {

        $sColumn = empty($column) ? "*" : implode(",", $column);

        $aWhere = array();
        $values = array();
        // return un object ou array
        $code = $object ? 5 : 2;
        if (!empty($param)) {
            foreach ($param as $key => $value) {
                $aWhere[] = " $key = :$key ";
                $values[":$key"] = $value;
            }
            $sWhere = implode(' AND ', $aWhere);

            $query = "SELECT $sColumn "
                    . "FROM " . $this->_tablename . " "
                    . "WHERE $sWhere ";
        } else {
            $query = "SELECT $sColumn "
                    . "FROM " . $this->_tablename;
            $stmt = Database::prepare($query);
        }

        $stmt = Database::prepare($query);
        $stmt->execute($values);

        if (!$one) { //retourne plusieur resultat
            return $stmt->fetchAll($code);
        } else { //retourne un seul resultat
            return $stmt->fetch($code);
        }
    }

    public function get($id) {
        $query = "SELECT * FROM {$this->_tablename} WHERE code = :id ";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(2);
    }

    public function checkBy($var, $type) {
        $query = "SELECT $type FROM " . $this->_tablename . " WHERE $type = :type";
        $stmt = Database::prepare($query);
        $stmt->execute(array('type' => $var));
        return $stmt->fetch(6);
    }

    public function getAllByParams($colonnes = array()) {
        $query = "SELECT " . implode(',', $colonnes) . " FROM " . $this->_tablename;
        $stmt = Database::prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->_tablename;
        $stmt = Database::prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS);
    }

    function getIsoWeeksInYear($year) {
        $date = new DateTime;
        $date->setISODate($year, 53);
        return ($date->format("W") === "53" ? 53 : 52);
    }

    public function incrementChar($char, $number) {
        for ($i = 0; $i < $number; $i++) {
            $char++;
        }
        return $char;
    }

    /**
     * @param $id
     * @return bool
     */
    public function remove($id) {
        $sQuery = "DELETE FROM {$this->_tablename} WHERE {$this->_id} = :id";
        $oStmt = Database::prepare($sQuery);
        $oStmt->bindParam(':id', $id);

        return $oStmt->execute();
    }

    /**
     * Methode pour supprimer une/des ligne(s) avec un/des condition(s)
     *
     * @param array $cond
     * @return bool
     */
    public function removeBy(array $cond) {
        /* $op = ($andGlue)?'AND':'OR'; */

        $aWhere = array();
        $values = array();

        foreach ($cond as $key => $value) {
            $aWhere[] = " $key = :$key ";
            $values[":$key"] = $value;
        }

        $sWhere = implode(' AND ', $aWhere);

        $sQuery = "DELETE "
                . "FROM " . $this->_tablename . " "
                . "WHERE $sWhere "
        ;

        $oStmt = Database::prepare($sQuery);

        return $oStmt->execute($values);
    }

    /**
     * @param array $data
     * @param array $column
     * @param bool $update
     * @param string $updateColumn
     */
    public function multipleInsert(array $data, array $column, $update = false, $updateColumn = '') {
        $sColumn = "(" . implode(',', $column) . ")";

        $aValues = [];

        $aParam = [];

        foreach ($data as $key => $value) {
            $aLigne = [];
            foreach ($value as $cle => $item) {
                $aLigne[] = ':' . $cle . $key;
                $aParam[':' . $cle . $key] = $item;
            }

            $aValues[] = '(' . implode(',', $aLigne) . ') ';
        }

        $sValues = implode(" , ", $aValues);

        $sql = "INSERT INTO $this->_tablename " . $sColumn . " VALUES " . $sValues;

        if ($update) {
            $sql .= " ON DUPLICATE KEY UPDATE ";

            $updateCol = "";

            if ($updateColumn === '') {
                foreach ($column as $value) {
                    $updateCol = $updateCol . "$value = VALUES($value) ";
                }
            } else {
                $updateCol = $updateCol . "$updateColumn = VALUES($updateColumn) ";
            }
            $sql .= $updateCol;
        }

        $stmt = Database::prepare($sql);

        $stmt->execute($aParam);
    }

    /**
     * @param array $data Ce paramètre doit être un tableau associatif dont les clés corespondent au column de la table
     *
     * @return int
     */
    public function insertOne(array $data) {
        $column = [];
        $values = [];
        $aParam = [];
        foreach ($data as $key => $item) {
            $column[] = $key;
            $values[] = ":$key";
            $aParam[":$key"] = $item;
        }

        $sColumn = "(" . implode(',', $column) . ")";
        $sValues = '(' . implode(',', $values) . ') ';

        $sql = "INSERT INTO $this->_tablename " . $sColumn . " VALUES " . $sValues;

        $stmt = Database::prepare($sql);

        $stmt->execute($aParam);
        
        return Database::lastInsertId($this->_tablename, $this->_id);
    }

    /**
     * Methode permettant de modifier suivant une condition défini
     *
     * @param array $data ceci est un associative array dont chaque clé corespond à une column de la table
     * @param array $condition elle correspond à la condition de modification
     *
     * exemple: $this->>updateBy(
     */
    public function updateBy(array $data, array $condition) {
        $aAssign = [];
        $aParam = [];
        $aWhere = [];

        foreach ($data as $key => $value) {
            $cle = array_key_exists($key, $condition) ? 'edit' . $key : $key;
            $aAssign[] = $key . ' = :' . $cle;
            $aParam[':' . $cle] = $value;
        }

        foreach ($condition as $key => $value) {
            $aWhere[] = $key . ' = :' . $key;
            $aParam[':' . $key] = $value;
        }

        $sAssign = implode(' , ', $aAssign);
        $sWhere = implode(' AND ', $aWhere);

        $sql = "UPDATE $this->_tablename SET " . $sAssign . " WHERE " . $sWhere;
        
        $stmt = Database::prepare($sql);

        $stmt->execute($aParam);
    }

    /**
     * Build column from multiple rows
     *
     * @param array $rows
     *
     * @return array
     */
    public function buildColumnFromMultipleRow(array $rows) {
        $row = current($rows);

        $column = [];

        foreach ($row as $key => $value) {
            $column[] = $key;
        }

        return $column;
    }

    /**
     * @param string $sField column to use as where condition
     * @param array $aData    data where we search the $sField in
     * @param string $sTable   table name
     * @param array $aColumn    column to output
     * @return array
     */
    public function findIn($sField, $aData, $aColumn = array(), $sTable = '') {
        $sTable = $sTable ? $sTable : $this->_tablename;
        $sColumn = empty($aColumn) ? '*' : implode(', ', $aColumn);

        $aParams = array();
        $aValue = array();
        foreach ($aData as $key => $data) {
            $sKey = $sField . '_' . $key;
            $aParams[$sKey] = $data;
            $aValue[] = ':'.$sKey;
        }

        $sQuery = "SELECT $sColumn FROM $sTable WHERE $sField IN ("
            . implode(',', $aValue) . ")";

        $stmt = Database::prepare($sQuery);
        $stmt->execute($aParams);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function multipleUpdateIn($data, $aIn, $column = 'id') {
        $aAssign = [];
        $aParam = [];
        $sIn = implode(',', $aIn);

        foreach ($data as $key => $value) {
            $aAssign[] = $key . ' = :' . $key;
            $aParam[':' . $key] = $value;
        }

        $sAssign = implode(' , ', $aAssign);

        $sql = <<<SQL
UPDATE $this->_tablename SET $sAssign WHERE $column IN ($sIn)
SQL;
        $stmt = Database::prepare($sql);

        $stmt->execute($aParam);
    }

    public function getLikeBy($aFilter, $mColumn = null){
        if (is_array($mColumn)) {
            $sColumn = empty($mColumn) ? '*' : implode(', ', $mColumn);
        } elseif (is_string($mColumn)) {
            $sColumn = '' === $mColumn ? '*' : $mColumn;
        } else {
            $sColumn = '*';
        }
        $aParams = array();
        $aWhere = array();
        foreach ($aFilter as $field => $data) {
            $aWhere[] = " LOWER($field) LIKE :$field";
            $aParams[$field] = '%'.$data.'%';
        }

        $sWhere = empty($aWhere) ? '' : 'WHERE ' . implode(' AND ', $aWhere);

        $sQuery = "SELECT $sColumn FROM {$this->_tablename} $sWhere";

        $oStmt = Database::prepare($sQuery);
        $oStmt->execute($aParams);

        return $oStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countIN($aIn, $column = 'id') {
        $sIn = implode(',', $aIn);
        $sql = <<<SQL
SELECT COUNT($this->_id) FROM $this->_tablename
WHERE $column IN ($sIn)
SQL;
        $oStmt = Database::prepare($sql);
        $oStmt->execute();

        return $oStmt->fetch(PDO::FETCH_COLUMN);
    }

    /**
     * Multiple insert update or insert data in database
     *
     * @param $aData
     * @param $updateColumn
     */
    public function multipleInsertUpdate($aData, $updateColumn) {
        $curentValue = current($aData);
        $column = array_keys($curentValue);

        $this->multipleInsert($aData, $column, true, $updateColumn);
    }
}
