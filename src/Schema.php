<?php declare(strict_types=1);


namespace Mengx\MysqlSchema;


class Schema
{




    private static \PDO $pdo;


    /**
     * 初始化一个数据库
     * @param string $host
     * @param string $database
     * @param string $username
     * @param string $password
     */
    public static function init(string $host,  string $username, string $password,string $database,string $charset = 'utf8mb4',$collate='utf8mb4_unicode_ci')
    {
        $dsn="mysql:host={$host}";
        self::$pdo = new \PDO($dsn,$username,$password);
        self::createDatabase($database,$charset,$collate);
        $useDbSql = sprintf('use %s',$database);
        self::$pdo->exec($useDbSql);
    }


    private static function createDatabase($database,$charset='utf8mb4',$collate='utf8mb4_unicode_ci'){
        $sql = sprintf('CREATE DATABASE IF NOT EXISTS %s DEFAULT CHARACTER SET %s DEFAULT COLLATE %s',$database,$charset,$collate);
        if(self::$pdo->exec($sql) === false){
            throw new \Exception('数据库创建失败');
        }
    }


    /**
     * 读取目录文件，进行数据表部署
     * @param string $path
     */
    public static function deploy(string $path){
        if(!self::$pdo){
            throw new \Exception('请先使用 init 方法初始化');
        }
        self::scandir($path);
    }

    public static function snake(string $value):string{
        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = mb_strtolower((preg_replace('/(.)(?=[A-Z])/u', '$1'.'_', $value)), 'UTF-8');
        }
        return $value;
    }

    /**
     * 删除所有表
     */
    public static function dropTableAll(){
        $sql = "SHOW TABLES";
        $tables = self::$pdo->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table){
            $rowTotal = self::$pdo->query("SELECT COUNT(*) total from {$table}")->fetch(\PDO::FETCH_COLUMN);
            if(!$rowTotal){
                // 删除该表
                self::dropTable($table);
            }
        }
    }

    // 删除一个表
    public static function dropTable(string $name):bool{
        $sql = "DROP TABLE IF EXISTS {$name}";
        return self::$pdo->exec($sql) !== false;
    }


    // 读取一个目录
    private static function scandir(string $path){
        $items = scandir($path);
        foreach ($items as $item){
            if($item === '.' || $item === '..'){
                continue;
            }
            // 判断是否是目录或文件
            $filename = $path.DIRECTORY_SEPARATOR.$item;
            if(is_dir($filename)){
                self::scandir($filename);
            }else{
                // 文件
                $class = require $filename;
                $class->up();
            }
        }
    }


    public static function table(string $tableName,\Closure $closure){

        $table = new Table($tableName);
        $closure($table);
        // 判断是否存在
        if(self::tableExists($table->getName())){
            // 拉取表结构
            $sql = self::getTableCreateSql($table->getName());
//            var_export($sql);exit;
            $fromTable = (new TableParser($table->getName(),$sql))->parseSql();
            $diff = new TableDiff($fromTable,$table);
            $alterSql = $diff->getAlterSql();
            if($alterSql){
                echo $alterSql.PHP_EOL;
                // 执行 sql 获得结果
                $res = self::$pdo->exec($alterSql);
                if($res === false){
                    throw new \Exception('更新表出错');
                }
            }
        }else{
            // 新增
            $sql = $table->getCreateSql();
            $res = self::$pdo->exec($sql);
            if($res === false){
                throw new \Exception('新增表出错');
            }
        }
    }

    // 查询表是否存在
    private static function tableExists(string $name):bool{
        $sql = "SHOW TABLES LIKE '{$name}'";
        $result = self::$pdo->query($sql)->fetch();
        return $result !== false;
    }

    private static function getTableCreateSql(string $name){
        $sql = "SHOW CREATE TABLE `{$name}`";
        $result = self::$pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return $result['Create Table'];
    }


}