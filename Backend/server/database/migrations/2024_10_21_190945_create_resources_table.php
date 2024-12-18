<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResourcesTable extends Migration
{
    public function up()
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id('id_resource'); // Auto-incrementing primary key
            $table->string('id_holder')->nullable()->constrained('employee', 'matricule')->onDelete('set null'); // Foreign key to User
            $table->string('label')->nullable(); // Label of the resource
            // $table->string('access_login')->nullable(); // Login for access
            // $table->string('access_password')->nullable(); // Password for access
            $table->string('discriminator'); // Not nullable discriminator
            $table->date('date_attribution')->nullable();
            $table->string('isavailable');
            $table->string('description')->nullable();
            $table->string('id_user_chief')->constrained('useraccount', 'matricule')->onDelete('set null'); // Foreign key to User
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('resources');
    }
}
