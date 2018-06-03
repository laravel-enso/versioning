<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVersioningsTable extends Migration
{
    public function up()
    {
        Schema::create('versionings', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('versionable');
            $table->integer('version');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('versionings');
    }
}
