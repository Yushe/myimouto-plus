<?php

namespace MyImouto\Post\QueryBuilders;

use MyImouto\Post\Query;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

interface BuilderInterface
{
    public function build(Query $query): EloquentBuilder;
}
