<?php

namespace Booni3\FluxDatatable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $guarded = [];

    protected $casts = ['received_at' => 'datetime'];
}
