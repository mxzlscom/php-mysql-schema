<?php declare(strict_types=1);


namespace Mengx\MysqlSchema\Columns;


class TimestampColumn
{
    use ColumnTrait;

    private bool $defaultCurrent;

    public function __construct(string $name,$defaultCurrent = false)
    {
        $this->name = $name;
        $this->defaultCurrent = $defaultCurrent;
        $this->columnName = 'timestamp';
    }

    public function toCreateSql(): string
    {
        $sql = "`$this->name` $this->columnName";
        // 是否为null
        $sql .= $this->nullableSql();
        // 默认值
        if($this->defaultCurrent){
            $sql .= ' DEFAULT CURRENT_TIMESTAMP';
        }
        $sql .= $this->commentSql();
        return $sql;
    }


}