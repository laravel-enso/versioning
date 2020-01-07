<?php

namespace LaravelEnso\Versioning\App\Exceptions;

use LaravelEnso\Helpers\App\Exceptions\EnsoException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Versioning extends ConflictHttpException
{
    public static function recordModified()
    {
        return new static(__('Current record was changed since it was loaded'));
    }
}
