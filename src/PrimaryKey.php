<?php declare(strict_types=1);


namespace Mengx\MysqlSchema;


class PrimaryKey
{

    private array $columns;
    public function __construct(array $columns)
    {
        ksort($columns);
        $this->columns = $columns;
    }

    public function toSql():string{
        $sql = "PRIMARY KEY (";
        $columns = [];
        foreach ($this->columns as $column){
            $columns[] = "`{$column}`";
        }
        $sql .= implode(',',$columns);
        $sql .=') USING BTREE'; // 使用的算法判断
        return $sql;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
