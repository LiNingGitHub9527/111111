<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class BaseModel extends Model
{
    public function batchInsert(Array $data)
    {
        return $this->getConnection()->table($this->getTable())->insert($data);
    }

    public function batchDelete($field, $values)
    {
        if (is_array($values)) {
            return $this->getConnection()->table($this->getTable())->whereIn($field, $values)->delete();
        } else {
            return $this->getConnection()->table($this->getTable())->where($field, $values)->delete();
        }
    }

    public function batchSoftDelete($field, $values)
    {
        if (is_array($values)) {
            return $this->getConnection()->table($this->getTable())->whereIn($field, $values)->update([self::DELETED_AT => now()]);
        } else {
            return $this->getConnection()->table($this->getTable())->where($field, $values)->update([self::DELETED_AT => now()]);
        }
    }

    public function batchUpdate(array $values, $whereField, array $whereValues)
    {
        return $this->getConnection()->table($this->getTable())->whereIn($whereField, $whereValues)->update($values);
    }

    public function batchInsertOrUpdate($data, $columns, $updateGiveUp = [], $updateExclude = [])
    {
        if (empty($data)) {
            return [
                'insertNum' => 0,
                'updateNum' => 0
            ];
        }

        $table = $this->getTable();

        $updateExclude = array_merge(['created_at'], $updateExclude);

        $sql = 'insert into '. $table . ' (';
        foreach ($columns as $column) {
            $sql .= $column . ',';
        }
        $sql = trim($sql, ',');
        $sql .= ') values ';

        foreach ($data as $v) {
            $sql .= '(';
            foreach ($columns as $column) {
                $val = '';
                if (isset($v[$column])) {
                    $val = $v[$column];
                    $val = addslashes($val);
                }
                $sql .= "'" . $val . "',";
            }
            $sql = trim($sql, ',');
            $sql .= '),';
        }
        $sql = trim($sql, ',');
        $sql .= ' on duplicate key update ';

        foreach ($columns as $column) {
            if (in_array($column, $updateExclude)) {
                continue;
            }
            $sql .= $column . ' = ';
            if (!empty($updateGiveUp) && isset($updateGiveUp[$column])) {
                $sql .= 'IF(' . $updateGiveUp[$column] . ',' . $column . ',values (' . $column . ')),';
            } else {
                $sql .= 'values (' . $column . '),';
            }
        }
        $sql = trim($sql, ',');
        $sql .= ';';

        $columnsNum = count($data);
        $retNum = DB::update(DB::raw($sql));
        $updateNum = $retNum - $columnsNum;
        $insertNum = $columnsNum - $updateNum;
        return [
            'insertNum' => $insertNum,
            'updateNum' => $updateNum
        ];
    }
}
