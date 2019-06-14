<?php

namespace LaravelEnso\Versioning\app\Models;

use Illuminate\Database\Eloquent\Model;

class Versioning extends Model
{
    protected $casts = ['version' => 'integer'];

    public function versionable()
    {
        return $this->morphTo();
    }
}
