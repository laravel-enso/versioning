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

            \DB::beginTransaction();

            $version = get_class($model)::lockForUpdate()
                ->find($model->getKey())
                ->{$attribute};

            if ($model->{$attribute} !== $version) {
                throw new ConflictHttpException(__(
                    'The state of the current entity has changed since you read it and cannot be saved.',
                    ['class' => get_class($model)]
                ));
            }

            $model->{$attribute}++;
        });

        self::updated(function ($model) {
            \DB::commit();
        });
    }

    private function versioningAttribute()
    {
        return property_exists($this, 'versioningAttribute')
                ? $this->versioningAttribute
                : 'version';
    }
}
