<?php declare(strict_types=1);


namespace Mengx\MysqlSchema\Columns;


class FloatColumn
{
    use ColumnTrait;
    use NumberColumnTrait;

    protected int $total;
    protected int $places;
    private ?float $default;

    public function __construct(string $name,int $total,int $places,?float $default)
    {
        $this->name = $name;
        $this->total = $total;
        $this->places = $places;
        $this->default = $default;
    }

    public function toCreateSql(): string
    {
        $sql = "`{$this->name}`";
        $sql .= " {$this->columnName}($this->total,$this->places)";
        // 判断是否可无符号
        if($this->unsigned){
            $sql .= ' unsigned';
        }
        // 判断可是否为空
        $sql .= $this->nullableSql();

        //默认值
        $sql .= $this->defaultSql();
        // 备注
        $sql .= $this->commentSql();
        return $sql;
    }

}
