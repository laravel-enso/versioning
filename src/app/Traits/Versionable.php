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
            if ($model->versioning) {
                $model->{$model->versioningAttribute()} = $model->versioning->version;
                unset($model->versioning);

                return;
            }

            DB::transaction(function () use ($model) {
                $model->pessimisticLock()
                    ->startVersioning();
            });
        });

        self::updating(function ($model) {
            if (! isset($model->{$model->versioningAttribute()})) {
                $this->throwMissingAttributeException($model);
            }

            DB::beginTransaction();

            $versioning = $model->versioning()
                ->lock()
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

    public function pessimisticLock($value = true)
    {
        $this->lock($value)
            ->find($this->getKey());
    }

    public function lockFor($version)
    {
        tap($this)->pessimisticLock()
            ->checkVersion($version);
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

        $this->versioning()
            ->save(new Versioning(['version' => 1]));

        $this->{$this->versioningAttribute()} = $startsAt;
    }

    private function throwInvalidVersionException()
    {
        throw new ConflictHttpException(__(
            'Current record was changed since it was loaded. Please refresh the page',
            ['class' => get_class($this)]
        ));
    }

    private function throwMissingAttributeException($model)
    {
        throw new ConflictHttpException(__(
            'The versioning attribute ":attribute" is missing from ":class" model. Please refresh the page',
            ['attribute' => $model->versioningAttribute(), 'class' => get_class($model)]
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
