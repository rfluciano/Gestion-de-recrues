<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitiesTable extends Migration
{
    public function up()
    {
        Schema::create('unities', function (Blueprint $table) {
            $table->id('id_unity'); // Assuming this is your primary key
            $table->unsignedBigInteger('id_parent')->nullable();
            $table->string('type')->nullable();
            $table->string('title')->nullable();
            $table->timestamps(); // Include timestamps if needed
        });
    }

    public function down()
    {
        Schema::dropIfExists('unities');
    }
}
