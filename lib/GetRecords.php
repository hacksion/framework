<?php
namespace TM;

class GetRecords extends DB
{
    private $conditions = [];
    private $conditions_or = [];
    private $conditions_group_or = [];
    private $like_column = [];
    private $like_value = '';
    private $limit = false;
    private $page_num = 0;
    private $records_num = 50;
    private $order_by = '';
    private $order_plus = '';
    private $order_sort = 'ASC';
    private $records_count = 0;
    private $all_records = '';
    private $sort_type = [ 'ASC', 'DESC' ];
    private $sql_value = '';
    private $csv_sql_value = '';

    /* set */
    public function setSqlValue($value)
    {
        $this->sql_value = $value;
    }
    public function setCsvSqlValue($value)
    {
        $this->csv_sql_value = $value;
    }
    public function setPageNum($value = 0)
    {
        $this->page_num = $value;
        return $this;
    }
    public function setRecordsNum($value = 50)
    {
        $this->records_num = $value;
        return $this;
    }
    public function setConditions(array $array):?object
    {
        $this->conditions = $array;
        return $this;
    }
    public function setConditionsOR(array $array):?object
    {
        $this->conditions_or = $array;
        return $this;
    }
    public function setConditionsGOR(array $array):?object
    {
        $this->conditions_group_or = $array;
        return $this;
    }
    public function setLikeColumn(array $array):?object
    {
        $this->like_column = $array;
        return $this;
    }
    public function setLikeValue(string $value):?object
    {
        $this->like_value = $value;
        return $this;
    }
    public function setLimit($bool = false)
    {
        $this->limit = $bool;
        return $this;
    }
    public function setOrderBy(string $value):?object
    {
        $this->order_by = $value;
        return $this;
    }
    public function setOrderPlus(string $value):?object
    {
        $this->order_plus = $value;
        return $this;
    }
    public function setOrderSort(string $value):?object
    {
        $this->order_sort = $value && in_array(strtoupper($value), $this->sort_type) ? strtoupper($value) : 'ASC';
        return $this;
    }

    /* get */
    public function getSqlValue()
    {
        return $this->sql_value;
    }
    public function getCsvSqlValue()
    {
        return $this->csv_sql_value;
    }
    public function getAllRecords()
    {
        return $this->all_records;
    }
    public function getRecordsCount():int
    {
        return $this->records_count;
    }
    //実行
    public function exec(string $sql, array $data, string $csv_sql = '')
    {
        $set_gor = false;
        if ($this->conditions) {
            $sql .= ' WHERE '.implode(' AND ', $this->conditions);
            $csv_sql .= ' WHERE '.implode(' AND ', $this->conditions);
        }
        if ($this->conditions_or) {
            $sql .= empty($this->conditions) ? ' WHERE ' : '';
            $or = '(' . implode(' OR ', $this->conditions_or) . ')';
            $sql .= $this->conditions ? ' AND ' . $or : $or;
            $csv_sql .= empty($this->conditions) ? ' WHERE ' : '';
            $csv_sql .= $this->conditions ? ' AND ' . $or : $or;
        }
        if ($this->conditions_group_or) {
            $gor = '';
            foreach($this->conditions_group_or as $key => $column){
                if(is_array($column) && !empty($column)){
                    $set_gor = true;
                    $gor .= '(' . implode(' OR ', $column) . ') AND ';
                }
            }
            if($gor && $set_gor){
                $sql .= $set_gor && empty($this->conditions) && empty($this->conditions_or) ? ' WHERE ' : '';
                $gor = substr($gor, 0, -5);
                $sql .= ($this->conditions || $this->conditions_or) && $gor ? ' AND ' . $gor : $gor;
                $csv_sql .= empty($this->conditions) && empty($this->conditions_or) ? ' WHERE ' : '';
                $csv_sql .= $this->conditions || $this->conditions_or ? ' AND ' . $gor : $gor;
            }
        }
        if ($this->like_value && $this->like_column) {
            $sql .= empty($this->conditions) && empty($this->conditions_or) && empty($set_gor) ? ' WHERE ' : '';
            $target = [];
            foreach ($this->like_column as $wt) {
                $target[] = $wt . ' COLLATE utf8mb4_unicode_ci LIKE "%' . trim($this->like_value) . '%"';
            }
            $like = '(' . implode(' OR ', $target) . ')';
            $sql .= $this->conditions || $this->conditions_or || $set_gor ? ' AND ' . $like : $like;
            $csv_sql .= empty($this->conditions) && empty($this->conditions_or) && empty($set_gor) ? ' WHERE ' : '';
            $csv_sql .= $this->conditions || $this->conditions_or || $set_gor ? ' AND ' . $like : $like;
        }
        $sql .= $this->order_by ? ' ORDER BY ' . $this->order_by . ' ' . $this->order_sort: '';
        $sql .= $this->order_plus && $this->order_by ? ',' . $this->order_plus : '';
        $csv_sql .= $this->order_by ? ' ORDER BY ' . $this->order_by . ' ' . $this->order_sort: '';
        $csv_sql .= $this->order_plus && $this->order_by ? ',' . $this->order_plus : '';

        $this->all_records = $this->execute('query', $sql, $data);
        $this->records_count = count($this->all_records);
        if($this->limit){
            $sql .= ' LIMIT ' . ($this->page_num * $this->records_num) . ',' . $this->records_num;
        }
        $this->setSqlValue($sql);
        $this->setCsvSqlValue($csv_sql);
        return $this->execute('query', $sql, $data);
    }
}