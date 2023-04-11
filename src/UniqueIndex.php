<?php declare(strict_types=1);


namespace Mengx\MysqlSchema;


class UniqueIndex
{
    private string $name;

    private array $columns;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function __construct(array $columns,string $name)
    {
        ksort($columns);
        if(!$name){
            $name = implode('_',$columns);
        }
        $this->name = $name;
        $this->columns = $columns;
    }

    public function toSql():string{
        $sql = "UNIQUE INDEX `$this->name` (";
        $columns = [];
        foreach ($this->columns as $column){
            $columns[] = "`$column`";
        }
        $sql .= implode(',',$columns);
        $sql .= ") USING BTREE";
        return $sql;
    }
    public function getAddSql():string{
        return 'ADD '.$this->toSql();
    }

    public function getDropSql():string{
        return "DROP INDEX `$this->name`";
    }

}
