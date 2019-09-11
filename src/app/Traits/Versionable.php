<?php

namespace LaravelEnso\Versioning\app\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use LaravelEnso\Versioning\app\Models\Versioning;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

trait Versionable
{
    // protected $versioningAttribute = 'version'; // default

    public static function versioningRetrieved($callback)
    {
        static::registerModelEvent('versioningRetrieved', $callback);
    }

    protected static function bootVersionable()
    {
        self::created(function ($model) {
            $model->startVersioning();
        });

        self::versioningRetrieved(function ($model) {
            if ($model->relations['versioning']) {
                $model->{$model->versioningAttribute()} = $model->relations['versioning']->version;

                return;
            }

            DB::transaction(function () use ($model) {
                tap($model)->lockWithoutEvents()
                    ->startVersioning();
            });
        });

        self::updating(function ($model) {
            DB::beginTransaction();

            $versioning = $model->versioning()->lock()->first();

            if (! $versioning) {
                $this->throwMissingVersionException($model);
            }

            $model->checkVersion($versioning->version);

            $model->relations['versioning'] = $versioning;

            unset($model->{$model->versioningAttribute()});
        });

        self::updated(function ($model) {
            $model->versioning->increment('version');
            $model->{$model->versioningAttribute()} = $model->versioning->version;

            DB::commit();
        });

        self::deleted(function ($model) {
            if (! $model->usesSoftDelete() || $model->isForceDeleting()) {
                $model->versioning()->delete();
            }
        });
    }

    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;

        if ($relation === 'versioning' && $value) {
            $this->fireModelEvent('versioningRetrieved', false);
        }

        return $this;
    }

    public function initializeVersionable()
    {
        $this->with[] = 'versioning';
        $this->observables[] = 'relationsRetrieved';
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
            'Current record was changed since it was loaded',
            ['class' => get_class($this)]
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
