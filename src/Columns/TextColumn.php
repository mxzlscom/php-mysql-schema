<?php declare(strict_types=1);


namespace Mengx\MysqlSchema\Columns;


class TextColumn
{
    use ColumnTrait;

    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->nullable = true;
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