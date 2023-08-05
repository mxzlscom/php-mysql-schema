<?php declare(strict_types=1);


namespace Mengx\MysqlSchema\Columns;


class IntegerColumn
{
    use ColumnTrait;

    private ?int $default;

    private bool $autoIncrement = false;

    private bool $unsigned = false;

    public function __construct(string $name,?int $default = 0)
    {
        $this->name = $name;
        $this->default = $default;
    }

    // 自增
    public function autoIncrement():self{
        $this->autoIncrement = true;
        return $this;
    }
    // 无符号
    public function unsigned():self{
        $this->unsigned = true;
        return $this;
    }


    public function toCreateSql(): string
    {
        $sql = "`{$this->name}`";
        $sql .= " {$this->columnName}";
        // 判断是否可无符号
        if($this->unsigned){
            $sql .= ' unsigned';
        }
        // 判断可是否为空
        $sql .= $this->nullableSql();
        // 是否自增
        if($this->autoIncrement){
            $sql .= " AUTO_INCREMENT";
        }
        //默认值
        $sql .= $this->defaultSql();
        // 备注
        $sql .= $this->commentSql();
        return $sql;
    }


    public function index():self{
        $this->table->index([$this->name],$this->name);
        return $this;
    }
    public function primary():self{
        $this->primary = true;
        $this->default = null;
        $this->table->primary([$this->name]);
        return $this;
    }


}