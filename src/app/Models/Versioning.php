<?php

namespace LaravelEnso\Versioning\app\Models;

use Illuminate\Database\Eloquent\Model;

class Versioning extends Model
{
    protected $attributes = ['version' => 1];

    public function versionable()
    {
        return $this->morphTo();
    }
}
