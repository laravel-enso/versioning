<?php

namespace LaravelEnso\Versioning\app\Exceptions;

use LaravelEnso\Helpers\app\Exceptions\EnsoException;

class Versioning extends EnsoException
{
    public static function recordModified()
    {
        return new static(__('Current record was changed since it was loaded'), 409);
    }
}
