<?php

namespace LaravelEnso\Versioning\App\Exceptions;

use LaravelEnso\Helpers\App\Exceptions\EnsoException;

class Versioning extends EnsoException
{
    public static function recordModified()
    {
        return new static(__('Current record was changed since it was loaded'),
            409);
    }
}
