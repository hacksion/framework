<?php
namespace TM;

class DB
{
    protected $dbh = null;

    protected $transaction = false;
    
    protected $fetch_class = false;/* default PDO::FETCH_ASSOC */

    public function __construct()
    {
        try {
            $this->dbh = new \PDO(
                DATABASE['TYPE'].':dbname='.DATABASE['NAME'].';
                host='.DATABASE['HOST'].';
                port='.DATABASE['PORT'],
                DATABASE['USER'],
                DATABASE['PASS']
            );
            $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->dbh->query('SET NAMES UTF8');
            //$this->dbh->query('SET group_concat_max_len = 65535');
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function __destruct()
    {
        $this->dbh = null;
    }

    public function setTransaction($boolean)
    {
        $this->transaction = $boolean;
    }

    public function setFetchClass($boolean)
    {
        $this->fetch_class = $boolean;
    }

    protected function execute($type, $sql, array $data)
    {
        try {
            $type = mb_strtolower($type);
            $result = 0;
            if ($this->transaction)$this->dbh->beginTransaction();
            $ret = $this->dbh->prepare($sql)->execute($data);
            if ($type == 'query') {
                $result = $this->fetch_class ?
                $sth->fetchAll(\PDO::FETCH_CLASS, 'stdClass') : $sth->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                if ($this->transaction) {
                    $ret ? $this->dbh->commit() : $this->dbh->rollBack();
                }
                $result = $type == 'insert' ? $this->dbh->lastInsertId():$sth->rowCount();
            }
            return $result;
        } catch (\PDOException $e) {
            if ($this->transaction)$this->dbh->rollBack();
            throw new \Exception($e->getMessage() . ' : ' . $sql);
        }
    }

    public function query(string $sql, array $data)
    {
        return $this->execute(__FUNCTION__, $sql, $data);
    }

    public function insert(string $table, array $records):int
    {
        $colmun = [];
        $placeholder = [];
        $data = [];
        $result = 0;
        foreach ($records as $key => $value) {
            $colmun[] = $key;
            $placeholder[] = '?';
            $data[] = $value;
        }
        if ($colmun && $data) {
            $result = $this->execute(__FUNCTION__, 'INSERT IGNORE INTO ' . $table . ' (' . (implode(',', $colmun)) . ') VALUE (' . (implode(',', $placeholder)) . ')', $data);
        }
        return $result;
    }

    public function update(string $table, array $condition, array $records):int
    {
        $column = [];
        $data = [];
        $where = [];
        $result = 0;
        foreach ($records as $key => $value) {
            $column[] = $key . '= ?';
            $data[] = $value;
        }
        foreach ($condition as $key => $value) {
            $where[] = $key . '= ?';
            $data[] = $value;
        }
        if ($column && $where && $data) {
            $result = $this->execute(__FUNCTION__, 'UPDATE ' . $table . ' SET ' . (implode(',', $column)) . ' WHERE ' . implode(' AND ', $where), $data);
        }
        return $result;
    }

    public function delete(string $table, array $condition, $add_condition=null):int
    {
        $result = 0;
        $where = [];
        $data = [];
        if ($table && $condition) {
            foreach ($condition as $key => $value) {
                $where[] = $key . '= ?';
                $data[] = $value;
            }
            $sql = 'DELETE FROM ' . $table . ' WHERE ' . implode(' AND ', $where);
            if($add_condition)$sql .= ' AND '.$add_condition;
            $result = $this->execute(__FUNCTION__, $sql, $data);
            $this->execute(__FUNCTION__, "ALTER TABLE $table auto_increment = 1", []);
        }
        return $result;
    }

    public function getUniqId(string $table_name, string $target_column, string $conditions):int
    {
        $result = 0;
        if ($table_name && $target_column) {
            $where_new = !empty($conditions) ? " WHERE $conditions" : '';
            $where_plus = !empty($conditions) ? $conditions .= " AND " : '';
            $sql = "SELECT IF(
                (SELECT count($target_column) FROM {$table_name}{$where_new}) = 0,1,(if((SELECT MIN($target_column)
                FROM {$table_name}{$where_new}) <> 1,1,MIN($target_column + 1)))) AS $target_column
                FROM $table_name
                WHERE $where_plus($target_column + 1) NOT IN (SELECT $target_column FROM {$table_name}{$where_new})";
            $uniqid = $this->execute('query', $sql, []);
            return $result = $uniqid[ 0 ]->$target_column;
        }
        return $result;
    }
}