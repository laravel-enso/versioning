<?php

namespace LaravelEnso\Versioning\app\Traits;

use Illuminate\Support\Facades\DB;
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
            if ($model->versioning !== null) {
                $model->{$model->versioningAttribute()} = $model->versioning->version;
                unset($model->versioning);

                return;
            }

            DB::transaction(function () use ($model) {
                DB::table($model->getTable())
                    ->where($model->getKeyName(), $model->getKey())
                    ->lockForUpdate()
                    ->first();

                $model->startVersioning();
            });
        });

        self::updating(function ($model) {
            if (! isset($model->{$model->versioningAttribute()})) {
                $this->throwMissingAttributeException($model);
            }

            DB::beginTransaction();

            $versioning = $model->versioning()
                ->lockForUpdate()
                ->first();

            if (! $versioning) {
                $this->throwMissingVersionException($model);
            }

            $model->checkVersion($versioning->version);

            unset($model->{$model->versioningAttribute()});
        });

        self::updated(function ($model) {
            $model->versioning->increment('version');

            $model->{$model->versioningAttribute()} = $model->versioning->version;

            DB::commit();
            
            unset($model->versioning);
        });

        self::deleted(function ($model) {
            if (! $model->usesSoftDelete() || $model->isForceDeleting()) {
                $model->versioning()->delete();
            }
        });
    }

    public function versioning()
    {
        return $this->morphOne(Versioning::class, 'versionable');
    }

    public function checkVersion($version)
    {
        if ($this->{$this->versioningAttribute()} !== $version) {
            $this->throwInvalidVersionException();
        }

        return $this;
    }

    public function usesSoftDelete()
    {
        return in_array(SoftDeletes::class, class_uses(get_class($this)));
    }

    private function versioningAttribute()
    {
        return property_exists($this, 'versioningAttribute')
            ? $this->versioningAttribute
            : 'version';
    }

    private function startVersioning()
    {
        $versioning = new Versioning();
        $versioning->version = 1;
        $this->versioning()->save($versioning);
        $this->{$this->versioningAttribute()} = $versioning->version;
    }

    private function throwInvalidVersionException()
    {
        throw new ConflictHttpException(__(
            'The state of the current entity has changed since you read it and cannot be saved',
            ['class' => get_class($this)]
        ));
    }

    private function throwMissingAttributeException($model)
    {
        throw new ConflictHttpException(__(
            'The versioning attribute ":attribute" is missing from ":class" model',
            ['attribute' => $model->versioningAttribute(), 'class' => get_class($model)]
        ));
    }

    private function throwMissingVersionException($model)
    {
        throw new ConflictHttpException(__(
            'The current ":class" model is missing its versioning',
            ['class' => get_class($model)]
        ));
    }
}
