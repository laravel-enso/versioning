<?php

use Faker\Factory;
use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use LaravelEnso\Versioning\app\Traits\Versioning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class VersioningTest extends TestCase
{
    use RefreshDatabase;

    private $faker;

    public function setUp()
    {
        parent::setUp();

        $this->faker = Factory::create();
        $this->createTestModelsTable();
    }

    /** @test */
    public function adds_version_when_creating()
    {
        $model = VersioningTestModel::create(['name' => $this->faker->word]);

        $this->assertEquals(1, $model->version);
    }

    /** @test */
    public function increases_version_when_updating()
    {
        VersioningTestModel::create(['name' => $this->faker->word]);

        $model = VersioningTestModel::first();

        $model->update(['name' => $this->faker->word]);

        $this->assertEquals(2, $model->fresh()->version);
    }

    /** @test */
    public function throws_error_when_version_is_wrong()
    {
        VersioningTestModel::create(['name' => $this->faker->word]);

        $model = VersioningTestModel::first();
        $secondModel = VersioningTestModel::first();

        $model->update(['name' => $this->faker->word]);

        $this->expectException(ConflictHttpException::class);

        $secondModel->update(['name' => $this->faker->word]);
    }

    /** @test */
    public function custom_version_field()
    {
        $this->createCustomTestModelsTable();

        $model = CustomVersioningTestModel::create(['name' => $this->faker->word]);

        $this->assertEquals(1, $model->custom);
    }

    private function createTestModelsTable()
    {
        Schema::create('versioning_test_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('version');
            $table->timestamps();
        });
    }

    private function createCustomTestModelsTable()
    {
        Schema::create('custom_versioning_test_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('custom');
            $table->timestamps();
        });
    }
}

class VersioningTestModel extends Model
{
    use Versioning;

    protected $fillable = ['name'];
}

class CustomVersioningTestModel extends Model
{
    use Versioning;

    protected $versioningAttribute = 'custom';

    protected $fillable = ['name'];
}
