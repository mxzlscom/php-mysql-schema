<?php

namespace Mengx\MysqlSchema\Columns;

use Mengx\MysqlSchema\Table;

trait ColumnTrait
{

    protected string $comment;

    protected bool $nullable = false;

    protected bool $primary = false; // 是否是主键

    protected Table $table;

    protected string $columnName;


    protected string $name;

    public function setColumnName(string $columnName):self{
        $this->columnName = $columnName;
        return $this;
    }

    public function comment(string $comment):self{
        $this->comment = $comment;
        return $this;
    }

    public function setTable(Table $table):self{
        $this->table = $table;
        return $this;
    }


    abstract public function toCreateSql():string;

    public function getAddSql(): string
    {
        return 'ADD COLUMN '.$this->toCreateSql();
    }
    public function getChangeSql(): string
    {
        return "MODIFY COLUMN ".$this->toCreateSql();
    }

    public function getDropSql():string{
        return "DROP COLUMN `$this->name`";
    }


    protected function defaultSql():string{
        if(!is_null($this->default)){
            return " DEFAULT '{$this->default}'";
        }
        return '';
    }

    protected function commentSql():string{
        // 备注
        if(isset($this->comment)){
            return " COMMENT '{$this->comment}'";
        }
        return '';
    }

    protected function nullableSql():string{
        if($this->nullable){
            return ' NULL';
        }else{
            return ' NOT NULL';
        }
    }
}
