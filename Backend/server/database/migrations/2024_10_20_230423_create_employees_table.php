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
            // $table->unsignedBigInteger('id_user')->nullable(); // Define id_user as nullable
            // $table->foreign('id_user')->references('id_user')->on('useraccount')->onDelete('cascade'); // Add foreign key constraint
            // $table->unique('id_user');
            $table->foreignId('id_position')->constrained('positions', 'id_position')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('firstname')->nullable();
            $table->boolean('isequipped');
            $table->date('date_entry');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
}
