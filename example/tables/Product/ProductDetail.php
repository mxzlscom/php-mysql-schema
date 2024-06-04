<?php

use \Mengx\MysqlSchema\Schema;
use \Mengx\MysqlSchema\Table;

return new class{

    public function up(){

        Schema::table(Schema::snake(pathinfo(__FILE__,PATHINFO_FILENAME)),function(Table $table){
            $table->id()->comment('11位数字ID');

//            $table->varchar('userid',40)->primary()->comment('自定义字符串ID');
//            $table->unsignedBigint('id')->primary()->comment('自定义数字ID');

            $table->varchar('name',1024);

            $table->char('phone',11)->comment('手机号');

            $table->text('content');

            $table->decimal('money',12,2);

            $table->float('width',8,2);

            $table->unsignedBigint('created_at');
            $table->unsignedBigint('updated_at');
            $table->timestamp('timestamp')->comment('时间戳');
//            $table->timestamp('updated_at');
        });
    }
};