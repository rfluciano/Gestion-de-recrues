<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePositionsTable extends Migration
{
    public function up()
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id('id_position');
            $table->foreignId('id_unity')->constrained('unities', 'id_unity')->onDelete('cascade'); // Assuming there's a Unity table
            $table->string('title')->nullable();
            $table->boolean('isavailable');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('positions');
    }
}