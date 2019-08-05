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
            $versioning = $model->versioning()->first();

            if ($versioning) {
                $model->{$model->versioningAttribute()} = $versioning->version;

                return;
            }

            DB::transaction(function () use ($model) {
                tap($model)->lockWithoutEvents()
                    ->startVersioning();
            });
        });

        self::updating(function ($model) {
            if (! isset($model->{$model->versioningAttribute()})) {
                $model->throwMissingAttributeException();
            }

            DB::beginTransaction();

            $versioning = $model->versioning()->lock()->first();

            if (! $versioning) {
                $this->throwMissingVersionException($model);
            }

            $model->checkVersion($versioning->version);
            unset($model->{$model->versioningAttribute()});
        });

        self::updated(function ($model) {
            $versioning = $model->versioning()->first();
            $versioning->increment('version');
            $model->{$model->versioningAttribute()} = $versioning->version;

            DB::commit();
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

    public function lockFor($version)
    {
        tap($this)->lockWithoutEvents()
            ->checkVersion($version);
    }

    public function lockWithoutEvents()
    {
        DB::table($this->getTable())->lock()
            ->where($this->getKeyName(), $this->getKey())
            ->first();
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
        $startsAt = 1;

        $this->versioning()->save(
            new Versioning(['version' => $startsAt])
        );

        $this->{$this->versioningAttribute()} = $startsAt;
    }

    private function throwInvalidVersionException()
    {
        throw new ConflictHttpException(__(
            'Current record was changed since it was loaded. Please refresh the page',
            ['class' => get_class($this)]
        ));
    }

    private function throwMissingAttributeException()
    {
        throw new ConflictHttpException(__(
            'The versioning attribute ":attribute" is missing from ":class" model. Please refresh the page',
            ['attribute' => $this->versioningAttribute(), 'class' => get_class($this)]
        ));
    }

    private function throwMissingVersionException($model)
    {
        throw new ConflictHttpException(__(
            'The current ":class" model is missing its versioning. Please refresh the page',
            ['class' => get_class($model)]
        ));
    }
}
