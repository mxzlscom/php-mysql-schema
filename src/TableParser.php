<?php declare(strict_types=1);


namespace Mengx\MysqlSchema;


class TableParser
{
    private string $sql;

    private string $tableName;

    public function __construct(string $tableName,string $sql)
    {
        $this->sql = $sql;
        $this->tableName = $tableName;
    }

    // 解析 sql ，并返回一个
    public function parseSql():Table
    {
        // 提取 括号内的内容
        // 从第一个括号，和最后一个括号开始截取
//        echo PHP_EOL;
//        var_export($this->sql);exit;

        $beforeIndex = strpos($this->sql, '(');
        $endIndex = strrpos($this->sql, ')');
        $columnsContent = substr($this->sql, $beforeIndex + 1, $endIndex - $beforeIndex - 1);
        $columnsContents = array_filter(explode(",\n", $columnsContent));

        $footSql = substr($this->sql,$endIndex+2);
        $footItems = explode(' ',$footSql);

        $options = [];
        foreach ($footItems as $item){
            if(strpos($item,'=') !== false){
                $opts = explode('=',$item);
                $options[$opts[0]] = $opts[1];
            }
        }


        // 得到了每列内容，现在就是解析每列内容了
//        if($this->tableName === 'user_visits'){
////                    var_export($this->sql);
//            var_dump($options);
//            var_dump($footItems);
//
//            var_dump($footSql);
//        exit;
//        }

        $table = new Table($this->tableName);
        $table->setEngine($options['ENGINE']);
        // 将每列内容，转成每列的单词
        foreach ($columnsContents as $columnContent) {
            $columnContent = trim($columnContent);
            preg_match_all("('[^']+'|[^\s]+)",$columnContent,$ks);
            $columnKeywords = $ks[0];
            switch ($columnKeywords[0]) {
                case 'UNIQUE':
                    if ($columnKeywords[1] === 'KEY') {
                        // 唯一字段
                        $keyName = str_replace('`','',$columnKeywords[2]);
                        $columns = $this->contentToColumnsNames($columnKeywords[3]);
//                        var_dump($columns);
//                        var_dump($keyName);
//                        var_dump($columnContent);
//                        exit;
                        $table->unique($columns, $keyName);
                    } else {
                        throw new \Exception('UNIQUE 未处理');
                    }
                    break;
                case 'PRIMARY':
                    if ($columnKeywords[1] === 'KEY') {
                        // 主键处理
                        // 从 string 中提取出 字段名
                        $columns = $this->contentToColumnsNames($columnKeywords[2]);
                        $table->primary($columns);
                    } else {
                        throw new \Exception('PRIMARY 未处理');
                    }
                    break;
                case 'KEY':
                    // 普通索引处理
                    $keyName = str_replace('`','',$columnKeywords[1]);
                    $columns = $this->contentToColumnsNames($columnKeywords[2]);
                    $table->index($columns, $keyName);
                    break;
                default:
                    // 其他
                    // 将content 转成 column 对象
                    // 判断类型
                    $this->contentToColumn($table,$columnContent,$columnKeywords);
                    break;
            }
        }

        return $table;
//        $sql = $table->toCreateSql();
//        var_dump($sql);
//        exit;

    }

    // 普通列处理
    private function contentToColumn(Table $table,string $columnContent,array $keywords):void
    {
        // 判断类型

        $columnName = str_replace('`','',$keywords[0]);
        // 判断是否是 varchar
//        var_dump($keywords);
//        exit;



        preg_match('/\w+/',$keywords[1],$result);
        $typeContent = $result[0];
//        var_dump($typeContent);
//        var_dump($result);


        // 是否有默认值 所有列都具有属性
        $hasDefault = array_search('DEFAULT',$keywords);
        $default = null;
        if($hasDefault !== false){
            $default = $keywords[$hasDefault + 1];

            if($default === 'NULL'){
                $default = null;
            }
        }
        // 是否有备注 所有列都具有属性
        $hasComment = array_search('COMMENT',$keywords);



        $comment = '';
        if($hasComment !== false){
            $comment = trim($keywords[$hasComment + 1],"'");
            // 去除收尾符号
        }
//    0.nb
        // 先按类来处理
        // 处理int 类
        // int 类
        // 处理 int 字符
        if(strpos($keywords[1],'int') !== false){
            if(!is_null($default)){
                $default = intval(trim($default,"'"));
            }
            // 判断是否无符号
            // 判断是否是自增
            $hasUnsigned = array_search('unsigned',$keywords);
            $hasAutoIncrement = array_search('AUTO_INCREMENT',$keywords);
//            preg_match("/\d+/",$keywords[1],$len);
            $column = $table->int($columnName,$default)->setColumnName($typeContent);
            if($hasAutoIncrement){
                $column->autoIncrement();
            }
            if($hasUnsigned){
                $column->unsigned();
            }
        }elseif(strpos($keywords[1],'char') !== false){
            if(!is_null($default)){
                $default = trim($default,"'");
            }
            // varchar 类型
            preg_match("/\d+/",$keywords[1],$result);
            $len = $result[0];
            $column = $table->varchar($columnName,intval($len),$default)->setColumnName($typeContent);
        }elseif(strpos($keywords[1],'text') !== false){
            $column = $table->text($columnName)->setColumnName($typeContent);
        }elseif($typeContent === 'float' || $typeContent === 'decimal'){
            // float
            if(!is_null($default)){
                $default = floatval(trim($default,"'"));
            }
            preg_match('/[\d,]+/',$keywords[1],$result);
            [$total,$places] = explode(',',$result[0]);
            $column = $table->float($columnName,intval($total),intval($places),$default)->setColumnName($typeContent);
        }elseif($typeContent === 'timestamp'){
            $defaultCurrent = $default === 'CURRENT_TIMESTAMP';
            $column = $table->timestamp($columnName,$defaultCurrent)->nullable(is_null($default));
        }elseif($typeContent === 'json'){
            $column = $table->json($columnName)->setColumnName($typeContent);
        }
        else{

            var_dump($keywords);
            var_dump($columnContent);
//            exit;
            throw new \Exception('未支持字段解析');
        }
        if($hasComment){
            $column->comment($comment);
        }
    }

    private function contentToColumnsNames(string $content): array
    {
        $beforeIndex = strpos($content, '(');
        $endIndex = strrpos($content, ')');
        $columnsContent = substr($content, $beforeIndex + 1, $endIndex - $beforeIndex - 1);
        $columns = explode(',', $columnsContent);
        foreach ($columns as &$column) {
            $column = str_replace('`','',$column);
        }
        return $columns;
    }
}