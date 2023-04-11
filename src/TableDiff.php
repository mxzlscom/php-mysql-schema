<?php declare(strict_types=1);


namespace Mengx\MysqlSchema;


use Mengx\MysqlSchema\Columns\ColumnTrait;

class TableDiff
{

    private Table $fromTable;
    private Table $toTable;

    private array $diffOptions;

    public function __construct(Table $fromTable,Table $toTable)
    {
        $this->fromTable = $fromTable;
        $this->toTable = $toTable;
    }

    // 获取差异
    public function getDiffOptions():array{
        // 对比 columns
        $fromColumns = $this->fromTable->getColumns();
        $toColumns = $this->toTable->getColumns();

        $this->diffOptions['table'] = $this->toTable->getName();

        // 比对差异
        foreach ($fromColumns as $name => $fromColumn){
            if(isset($toColumns[$name])){
                // 存在，并且不同
                if($toColumns[$name]->toCreateSql() !== $fromColumn->toCreateSql()){

//                    var_dump([
//                        'to sql' => $toColumns[$name]->toCreateSql(),
//                        'from sql' => $fromColumn->toCreateSql(),
//                    ]);
//                    exit;

                    $this->diffOptions['columns']['changes'][$name] = $toColumns[$name];
                }
            }else{
                //删除
                $this->diffOptions['columns']['removes'][$name] = $fromColumn;
            }
        }
        // 遍历 to
        foreach ($toColumns as $name => $toColumn){
            if(!isset($fromColumns[$name])){
                // 不存在，新增
                $this->diffOptions['columns']['adds'][$name] = $toColumn;
            }
        }

        // 先比对 primary key
        $fromPk = $this->fromTable->getPrimary();
        // pk
        if($fromPk->toSql() != $this->toTable->getPrimary()->toSql()){
            // primary id 改变
            $this->diffOptions['primary'] = $this->toTable->getPrimary();
        }
        // unique
        $fromUniques = $this->fromTable->getUniques();
        $toUniques = $this->toTable->getUniques();

        foreach ($fromUniques as $name => $fromUnique){
            if(isset($toUniques[$name])){
                // 存在，并且不同
                if($toUniques[$name]->toSql() !== $fromUnique->toSql()){
                    // 要修改的 uniques
                    $this->diffOptions['uniques']['changes'][$name] = $toUniques[$name];
                }
                // 相同就不用管了
            }else{
                // 不存在，则移除指定的 unique
                $this->diffOptions['uniques']['removes'][$name] = $fromUnique;
            }
        }
        // 遍历 to
        foreach ($toUniques as $name => $toUnique){
            // 如果不存在，则新增
            if(!isset($fromUniques[$name])){
                // 新增
                $this->diffOptions['uniques']['adds'][$name] = $toUnique;
            }
        }
        // index
        $fromIndexes = $this->fromTable->getIndexes();
        $toIndexes = $this->toTable->getIndexes();
        foreach ($fromIndexes as $name => $fromIndex){
            if(isset($toIndexes[$name])){
                // 存在，并且不同
                if($toIndexes[$name]->toSql() !== $fromIndex->toSql()){
                    // 要修改的 uniques
                    $this->diffOptions['indexes']['changes'][$name] = $toIndexes[$name];
                }
                // 相同就不用管了
            }else{
                // 不存在，则移除指定的 unique
                $this->diffOptions['indexes']['removes'][$name] = $fromIndex;
            }
        }
        // 遍历to
        foreach ($toIndexes as $name => $toIndex){
            if(!isset($fromIndexes[$name])){
                $this->diffOptions['indexes']['adds'][$name] = $toIndex;
            }
        }
        // 判断引擎是否一样
        if($this->fromTable->getEngine() !== $this->toTable->getEngine()){
            $this->diffOptions['engine'] = $this->toTable->getEngine();
        }

        return $this->diffOptions;
    }


    // 将差异转化成语句
    public function getAlterSql():string{
        $diffOptions = $this->getDiffOptions();

//        var_dump($diffOptions);

        // 遍历字段
        // 处理删除
        $columnsSql = [];
        /**
         * @var ColumnTrait $column
         */
        if(isset($diffOptions['columns']['removes'])){
            // 遍历删除
            foreach ($diffOptions['columns']['removes'] as $column){
                $columnsSql[] = $column->getDropSql();
            }
        }
        // 如果有追加
        if(isset($diffOptions['columns']['adds'])){
            foreach ($diffOptions['columns']['adds'] as $column){
                $columnsSql[] = $column->getAddSql();
            }
        }
        // 如果有改变
        if(isset($diffOptions['columns']['changes'])){
            foreach ($diffOptions['columns']['changes'] as $column){
                $columnsSql[] = $column->getChangeSql();
            }
        }

        // 处理主键
        if(isset($diffOptions['primary'])){
            // 删除主键，新增主键
            /**
             * @var $pk PrimaryKey
             */
            $pk = $diffOptions['primary'];
            $columnsSql[] = $this->dropPrimaryKeySql($pk->getColumns());
        }
        // 处理唯一索引
        if(isset($diffOptions['uniques']['removes'])){
            /**
             * @var $unique UniqueIndex
             */
            foreach ($diffOptions['uniques']['removes'] as $unique){
                $columnsSql[] = $unique->getDropSql();
            }
        }
        // 处理唯一索引修改
        if(isset($diffOptions['uniques']['changes'])){
            /**
             * @var $unique UniqueIndex
             */
            foreach ($diffOptions['uniques']['changes'] as $unique){
                $columnsSql[] = $unique->getDropSql();
                $columnsSql[] = $unique->getAddSql();
            }
        }
        // 处理唯一索引增加
        if(isset($diffOptions['uniques']['adds'])){
            /**
             * @var $unique UniqueIndex
             */
            foreach ($diffOptions['uniques']['adds'] as $unique){
                $columnsSql[] = $unique->getAddSql();
            }
        }
        // 处理普通索引
        if(isset($diffOptions['indexes']['removes'])){
            /**
             * @var $unique Index
             */
            foreach ($diffOptions['indexes']['removes'] as $unique){
                $columnsSql[] = $unique->getDropSql();
            }
        }
        // 处理普通索引修改
        if(isset($diffOptions['indexes']['changes'])){
            /**
             * @var $unique Index
             */
            foreach ($diffOptions['indexes']['changes'] as $unique){
                $columnsSql[] = $unique->getDropSql();
                $columnsSql[] = $unique->getAddSql();
            }
        }
        // 处理普通索引增加
        if(isset($diffOptions['indexes']['adds'])){
            /**
             * @var $unique Index
             */
            foreach ($diffOptions['indexes']['adds'] as $unique){
                $columnsSql[] = $unique->getAddSql();
            }
        }

        // 处理引擎
        if(isset($diffOptions['engine'])){
            $columnsSql[] = "ENGINE = {$diffOptions['engine']}";
        }

        // 如果没有变更项目，则返回 empty
        if(!$columnsSql){
            return '';
        }

        $sql = "ALTER TABLE `{$diffOptions['table']}` ";
        $sql .= implode(',',$columnsSql);
        return $sql;
    }


    // 更新主键
    public function dropPrimaryKeySql(array $columns):string{
        $keys = [];
        foreach ($columns as $column){
            $keys[] = "`{$column}`";
        }
        $sql = "DROP PRIMARY KEY,ADD PRIMARY KEY (";
        $sql .= implode(',',$keys);
        $sql .=') USING BTREE';
        return $sql;
    }

    // 更新index
    public function indexSql(){
        $sql = "DROP INDEX `tap`,ADD INDEX `tp`(`tid`) USING BTREE";
    }

    // 更新 unique
    public function uniqueSql(){
        $sql = "DROP INDEX `tap`,ADD INDEX `tp`(`tid`) USING BTREE";
    }

    // 删除字段
    public function dropColumnSql(string $column){
        return "DROP COLUMN $column";
    }

    // 修改字段
    public function modifyColumnSql(){
        $sql = "MODIFY COLUMN `uid2` varchar(20) NULL DEFAULT NULL AFTER `uid1`";
    }

    // 修改引擎
    public function modifyEngineSql(string $engine){
        $sql = "ENGINE = {$engine}";
    }
}