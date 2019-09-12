<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVersioningsTable extends Migration
{
    public function up()
    {
        Schema::create('versionings', function (Blueprint $table) {
            $table->increments('id');

            $table->string('versionable_type');
            $table->unsignedBigInteger('versionable_id');

            $table->integer('version');

            $table->timestamps();

            $table->unique(['versionable_type', 'versionable_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('versionings');
    }
}
