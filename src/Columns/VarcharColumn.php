<?php declare(strict_types=1);


namespace Mengx\MysqlSchema\Columns;


class VarcharColumn
{

    use ColumnTrait;

    private string $name;

    private int $length;

    private ?string $default;

    public function __construct(string $name,int $length = 255,?string $default = '')
    {
        $this->name = $name;
        $this->length = $length;
        $this->default = $default;
    }

    public function index():self{
        $this->table->index([$this->name],$this->name);
        return $this;
    }

    public function unique():self{
        $this->table->unique([$this->name],$this->name);
        return $this;
    }

    public function toCreateSql():string
    {
        $sql = "`{$this->name}`";
        $sql .= " {$this->columnName}({$this->length}) ";
        // 判断可是否为空
        $sql .= $this->nullableSql();
        //默认值
        $sql .= $this->defaultSql();
        // 备注
        $sql .= $this->commentSql();
        return $sql;
    }



    public function primary():self{
        $this->primary = true;
        $this->default = null;
        $this->table->primary([$this->name]);
        return $this;
    }




}

