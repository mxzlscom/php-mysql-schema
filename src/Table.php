<?php declare(strict_types=1);


namespace Mengx\MysqlSchema;


use Mengx\MysqlSchema\Columns\ColumnTrait;
use Mengx\MysqlSchema\Columns\FloatColumn;
use Mengx\MysqlSchema\Columns\IntegerColumn;
use Mengx\MysqlSchema\Columns\JsonColumn;
use Mengx\MysqlSchema\Columns\TextColumn;
use Mengx\MysqlSchema\Columns\TimestampColumn;
use Mengx\MysqlSchema\Columns\VarcharColumn;

class Table
{


    private string $name;


    private string $engine = 'InnoDB';

    /**
     * @return string
     */
    public function getEngine(): string
    {
        return $this->engine;
    }
    public function setEngine(string $engine){
        $this->engine = $engine;
    }
    /**
     * @var ColumnTrait[]
     */
    private array $columns = [];

    /**
     * @return ColumnTrait[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @var Index[]
     */
    private array $indexes = [];

    /**
     * @return Index[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }
    /**
     * @var UniqueIndex[]
     */
    private array $uniques = [];

    /**
     * @return UniqueIndex[]
     */
    public function getUniques(): array
    {
        return $this->uniques;
    }

    private PrimaryKey $primary;

    /**
     * @return PrimaryKey
     */
    public function getPrimary(): PrimaryKey
    {
        return $this->primary;
    }

    public function getName():string{
        return $this->name;
    }

    public function __construct(string $name)
    {
        $this->name = $name;
    }


    // 设置主键
    public function primary(array $columns){
        $this->primary = new PrimaryKey($columns);
    }

    // 这个应该是建立索引，而非普通字段
    public function id(string $name = 'id'):IntegerColumn{
        $this->columns[$name] = (new IntegerColumn($name))->setTable($this)->primary()->autoIncrement()->unsigned()->setColumnName('int');
        return $this->columns[$name];
    }
    public function bigId(string $name = 'id'):IntegerColumn{
        $this->columns[$name] = (new IntegerColumn($name))->setTable($this)->primary()->autoIncrement()->unsigned()->setColumnName('bigint');
        return $this->columns[$name];
    }

    public function int(string $name,?int $default=0):IntegerColumn{
        $this->columns[$name] =( new IntegerColumn($name,$default))->setTable($this)->setColumnName('int');
        return $this->columns[$name];
    }
    public function unsignedInt(string $name,?int $default=0):IntegerColumn{
        $this->columns[$name] =( new IntegerColumn($name,$default))->setTable($this)->unsigned()->setColumnName('int');
        return $this->columns[$name];
    }
    public function tinyint(string $name,?int $default=0):IntegerColumn{
        $this->columns[$name] =( new IntegerColumn($name,$default))->setTable($this)->setColumnName('tinyint');
        return $this->columns[$name];
    }
    public function unsignedTinyint(string $name,?int $default=0):IntegerColumn{
        $this->columns[$name] =( new IntegerColumn($name,$default))->setTable($this)->unsigned()->setColumnName('tinyint');
        return $this->columns[$name];
    }
    public function smallint(string $name,?int $default=0):IntegerColumn{
        $this->columns[$name] =( new IntegerColumn($name,$default))->setTable($this)->setColumnName('smallint');
        return $this->columns[$name];
    }
    public function unsignedSmallint(string $name,?int $default=0):IntegerColumn{
        $this->columns[$name] =( new IntegerColumn($name,$default))->setTable($this)->unsigned()->setColumnName('smallint');
        return $this->columns[$name];
    }
    public function bigint(string $name,?int $default = 0):IntegerColumn{
        $this->columns[$name] =( new IntegerColumn($name,$default))->setTable($this)->setColumnName('bigint');
        return $this->columns[$name];
    }
    public function unsignedBigint(string $name,?int $default = 0):IntegerColumn{
        $this->columns[$name] =( new IntegerColumn($name,$default))->setTable($this)->setColumnName('bigint')->unsigned();
        return $this->columns[$name];
    }


    public function float(string $name,int $total = 8,int $places = 2,?float $default = 0):FloatColumn{
        $this->columns[$name] =( new FloatColumn($name,$total,$places,$default))->setTable($this)->setColumnName('float');
        return $this->columns[$name];
    }
    public function decimal(string $name,int $total = 8,int $places = 2,?float $default = 0):FloatColumn{
        $this->columns[$name] =( new FloatColumn($name,$total,$places,$default))->setTable($this)->setColumnName('decimal');
        return $this->columns[$name];
    }

    public function varchar(string $name,int $length = 255,?string $default = ''):VarcharColumn{
        $this->columns[$name] =  (new VarcharColumn($name,$length,$default))->setTable($this)->setColumnName('varchar');
        return $this->columns[$name];
    }

    public function char(string $name,int $length):VarcharColumn{
        $this->columns[$name] =  (new VarcharColumn($name,$length))->setTable($this)->setColumnName('char');
        return $this->columns[$name];
    }

    public function text(string $name):TextColumn{
        $this->columns[$name] = (new TextColumn($name))->setTable($this)->setColumnName('text');
        return $this->columns[$name];
    }
    public function longtext(string $name):TextColumn{
        $this->columns[$name] = (new TextColumn($name))->setTable($this)->setColumnName('longtext');
        return $this->columns[$name];
    }

    public function json(string $name):JsonColumn{
        $this->columns[$name] = (new JsonColumn($name))->setTable($this)->setColumnName('json');
        return $this->columns[$name];
    }

    public function timestamp(string $name,$defaultCurrent = false):TimestampColumn{
        $this->columns[$name] = (new TimestampColumn($name,$defaultCurrent))->setTable($this);
        return $this->columns[$name];
    }


    // 分为 三种， 一种是 字段，一种是 普通索引，一种是唯一索引

    // 增加一条索引
    public function index(array $columns,string $name = ''){
        $index = new Index($columns,$name);
        if($name === ''){
            $name = $index->getName();
        }
        $this->indexes[$name] = $index;
    }
    // 增加唯一索引
    public function unique(array $columns,string $name = ''){
        $unique = new UniqueIndex($columns,$name);
        $name = $unique->getName();
        $this->uniques[$name] = $unique;
    }


    // 转换成创建表sql
    public function getCreateSql():string{
        // 对所有列转换
        $sql = "CREATE TABLE `{$this->name}`(";
        // 组装字段
        $columnsSql = [];
        foreach ($this->columns as $column){
            $columnsSql[] = $column->toCreateSql();
        }
        // 主键转 sql
        $columnsSql[] = $this->primary->toSql();
        // 唯一索引转
        foreach ($this->uniques as $unique){
            $columnsSql[] = $unique->toSql();
        }
        // 索引转sql
        foreach ($this->indexes as $index){
            $columnsSql[] = $index->toSql();
        }
        $sql .= implode(',',$columnsSql);
        $sql .= ');';
        return $sql;
    }


}
