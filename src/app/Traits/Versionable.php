<?php

namespace LaravelEnso\Versioning\app\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use LaravelEnso\Versioning\app\Models\Versioning;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

trait Versionable
{
    protected static function bootVersionable()
    {
        self::created(function ($model) {
            $model->startVersioning();
        });

        self::retrieved(function ($model) {
            if (! $model->versioning) {
                DB::transaction(function () use ($model) {
                    tap($model)->lockWithoutEvents()
                        ->startVersioning();
                });
            }
        });

        self::updating(function ($model) {
            DB::beginTransaction();

            $versioning = $model->versioning()->lock()->first();

            if (! $versioning) {
                $this->throwMissingVersionException($model);
            }

            $model->checkVersion($versioning->version);
        });

        self::updated(function ($model) {
            $model->versioning->increment('version');

            DB::commit();
        });

        self::deleted(function ($model) {
            if (! $model->usesSoftDelete() || $model->isForceDeleting()) {
                $model->versioning()->delete();
            }
        });
    }

    public function initializeVersionable()
    {
        $this->with[] = 'versioning';
    }

    public function versioning()
    {
        return $this->morphOne(Versioning::class, 'versionable');
    }

    public function checkVersion($version)
    {
        if ($this->versioning->version !== $version) {
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

    private function startVersioning()
    {
        $startsAt = 1;

        $this->versioning()->save(
            new Versioning(['version' => $startsAt])
        );
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
