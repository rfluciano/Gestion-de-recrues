<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->string('matricule')->primary();
            $table->foreignId('id_position')->constrained('positions', 'id_position')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('firstname')->nullable();
            $table->boolean('isequipped');
            $table->date('date_entry');
            $table->string('id_superior')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
}
