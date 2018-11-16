<?php

namespace LaravelEnso\Versioning\app\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use LaravelEnso\Versioning\app\Models\Versioning;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

trait Versionable
{
    // protected $versioningAttribute = 'version'; // default

    protected static function bootVersionable()
    {
        self::created(function ($model) {
            $model->startVersioning();
        });

        self::retrieved(function ($model) {
            if ($model->versioning) {
                $model->{$model->versioningAttribute()} = $model->versioning->version;
                unset($model->versioning);

                return;
            }

            \DB::transaction(function () use ($model) {
                \DB::table($model->getTable())
                    ->where($model->getKeyName(), $model->getKey())
                    ->lockForUpdate()
                    ->first();

                $model->startVersioning();
            });
        });

        self::updating(function ($model) {
            \DB::beginTransaction();

            if (!isset($model->{$model->versioningAttribute()})) {
                \DB::rollback();

                throw new ConflictHttpException(__(
                    'The versioning attribute ":attribute" is missing from ":class" model',
                    ['attribute' => $model->versioningAttribute(), 'class' => get_class($model)]
                ));
            }

            $versioning = $model->versioning()
                ->lockForUpdate()
                ->first();

            if (!$versioning) {
                \DB::rollback();

                throw new ConflictHttpException(__(
                    'The current ":class" model is missing its versioning',
                    ['class' => get_class($model)]
                ));
            }

            if ($model->{$model->versioningAttribute()} !== $versioning->version) {
                \DB::rollback();

                throw new ConflictHttpException(__(
                    'The state of the current entity has changed since you read it and cannot be saved',
                    ['class' => get_class($model)]
                ));
            }

            unset($model->{$model->versioningAttribute()});
        });

        self::updated(function ($model) {
            if(! $model->versioning) {
                $model->load('versioning');
            }

            $model->versioning->increment('version');

            $model->{$model->versioningAttribute()} = $model->versioning->version;

            unset($model->versioning);

            \DB::commit();
        });

        self::deleted(function ($model) {
            if (!in_array(SoftDeletes::class, class_uses(get_class($model)))
                || $model->isForceDeleting()) {
                $model->versioning()
                    ->delete();
            }
        });
    }

    public function versioning()
    {
        return $this->morphOne(Versioning::class, 'versionable');
    }

    private function versioningAttribute()
    {
        return property_exists($this, 'versioningAttribute')
            ? $this->versioningAttribute
            : 'version';
    }

    private function startVersioning()
    {
        $this->versioning()
            ->save(new Versioning());

        $this->{$this->versioningAttribute()} = 1;
    }
}
