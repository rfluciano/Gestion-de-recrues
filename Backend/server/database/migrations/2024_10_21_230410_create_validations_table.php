<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateValidationsTable extends Migration
{
    public function up()
    {
        Schema::create('validations', function (Blueprint $table) {
            $table->id('id_validation'); // Auto-incrementing primary key
            $table->string('id_validator')->constrained('useraccount', 'matricule')->onDelete('cascade'); // Foreign key to User
            $table->foreignId('id_request')->constrained('requests', 'id_request')->onDelete('cascade');
            $table->date('delivery_date')->nullable(); // Date for delivery
            $table->date('validation_date'); // Date of validation
            $table->string('status', 55); // Status of validation
            $table->string('rejection_reason', 255)->nullable(); // Reason for rejection, if any
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    public function down()
    {
        Schema::dropIfExists('validations');
    }
}
