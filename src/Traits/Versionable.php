<?php

namespace LaravelEnso\Versioning\Traits;

use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use LaravelEnso\Versioning\Exceptions\Versioning as VersioningException;
use LaravelEnso\Versioning\Models\Versioning;

trait Versionable
{
    // protected $versioningAttribute = 'version'; // default

    public static function bootVersionable()
    {
        self::created(fn ($model) => $model->startVersioning());

        self::versioningRetrieved(fn ($model) => $model->initVersion());

        self::updating(fn ($model) => $model->checkOrInitVersion());

        self::updated(fn ($model) => $model->incrementVersion());

        self::deleted(fn ($model) => $model->deleteVersioning());
    }

    public static function versioningRetrieved($callback)
    {
        static::registerModelEvent('versioningRetrieved', $callback);
    }

    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;

        if ($relation === 'versioning') {
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
            DB::rollBack();
            throw VersioningException::recordModified();
        }

        return $this;
    }

    public function lockWithoutEvents()
    {
        DB::table($this->getTable())->lock()
            ->where($this->getKeyName(), $this->getKey())
            ->first();
    }

    public function startVersioning()
    {
        DB::transaction(fn () => $this->createVersion());
    }

    public function usesSoftDelete()
    {
        return in_array(SoftDeletes::class, class_uses(self::class));
    }

    private function createVersion()
    {
        $this->lockWithoutEvents();

        $startsAt = 1;

        try {
            $this->versioning()->save(
                new Versioning(['version' => $startsAt])
            );
        } catch (Exception $exception) {
            throw VersioningException::recordModified();
        }

        $this->{$this->versioningAttribute()} = $startsAt;
    }

    private function incrementVersion()
    {
        $this->versioning->increment('version');
        $this->{$this->versioningAttribute()} = $this->versioning->version;

        DB::commit();
    }

    private function initVersion()
    {
        $version = optional($this->relations['versioning'])->version;
        $this->{$this->versioningAttribute()} = $version;
    }

    private function deleteVersioning()
    {
        if (! $this->usesSoftDelete() || $this->isForceDeleting()) {
            $this->versioning()->delete();
        }
    }

    private function checkOrInitVersion()
    {
        DB::beginTransaction();

        $versioning = $this->versioning()->lock()->first();

        if ($versioning) {
            $this->checkVersion($versioning->version);
        } else {
            $this->startVersioning();
            $versioning = $this->versioning()->lock()->first();
        }

        $this->relations['versioning'] = $versioning;

        unset($this->{$this->versioningAttribute()});
    }

    private function versioningAttribute()
    {
        return property_exists($this, 'versioningAttribute')
            ? $this->versioningAttribute
            : 'version';
    }
}
