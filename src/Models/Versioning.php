<?php

namespace LaravelEnso\Versioning\Models;

use Illuminate\Database\Eloquent\Model;

class Versioning extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['version' => 'integer'];

    public function versionable()
    {
        return $this->morphTo();
    }
}
