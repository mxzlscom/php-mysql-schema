<?php

namespace Mengx\MysqlSchema\Columns;

class JsonColumn
{
    use ColumnTrait;


    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function toCreateSql(): string
    {
        $sql = "`$this->name` $this->columnName";
        // 是否为null
        $sql .= $this->nullableSql();
        $sql .= $this->commentSql();
        return $sql;
    }

}