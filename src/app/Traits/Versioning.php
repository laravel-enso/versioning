<?php

namespace LaravelEnso\Versioning\app\Traits;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

trait Versioning
{
    // protected $versioningAttribute = 'version'; // default

    protected static function bootVersioning()
    {
        self::creating(function ($model) {
            $model->{$model->versioningAttribute()} = 1;
        });

        self::updating(function ($model) {
            $attribute = $model->versioningAttribute();

            if ($model->{$attribute} !==
                get_class($model)::find($model->getKey())->{$attribute}) {
                throw new ConflictHttpException(__(
                    'The state of the current entity has changed since you read it and cannot be saved.',
                    ['class' => get_class($model)]
                ));
            }

            $model->{$attribute}++;
        });
    }

    private function versioningAttribute()
    {
        return property_exists($this, 'versioningAttribute')
                ? $this->versioningAttribute
                : 'version';
    }
}
