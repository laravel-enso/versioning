<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LaravelEnso\Versioning\App\Exceptions\Versioning;
use LaravelEnso\Versioning\App\Traits\Versionable;
use Tests\TestCase;

class VersioningTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();

        $this->createTestModelsTable();
    }

    /** @test */
    public function adds_version_when_creating()
    {
        $model = VersioningTestModel::create(['name' => 'testModel']);

        $this->assertEquals(1, $model->versioning->version);
    }

    /** @test */
    public function increases_version_when_updating()
    {
        $model = VersioningTestModel::create(['name' => 'testModel']);

        $model->update(['name' => 'updated']);

        $this->assertEquals(2, $model->versioning->version);
    }

    /** @test */
    public function throws_error_when_version_is_wrong()
    {
        VersioningTestModel::create(['name' => 'testModel']);

        $model = VersioningTestModel::first();

        $secondModel = VersioningTestModel::first();

        $model->update(['name' => 'updated']);

        $this->expectException(Versioning::class);

        $secondModel->update(['name' => 'testModel2']);
    }

    private function createTestModelsTable()
    {
        Schema::create('versioning_test_models', static function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }
}

class VersioningTestModel extends Model
{
    use Versionable;

    protected $fillable = ['name'];
}

class CustomVersioningTestModel extends Model
{
    use Versionable;

    protected $versioningAttribute = 'custom_field';

    protected $fillable = ['name'];
}
