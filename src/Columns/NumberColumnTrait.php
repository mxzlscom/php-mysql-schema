<?php

namespace Mengx\MysqlSchema\Columns;

trait NumberColumnTrait
{
    private bool $unsigned = false;

    public function unsigned():self{
        $this->unsigned = true;
        return $this;
    }
}