<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUseraccountTable extends Migration
{
    public function up()
    {
        Schema::create('useraccount', function (Blueprint $table) {
            $table->id('id_user'); // Auto-incrementing primary key
            $table->string('matricule');
            $table->string('email')->unique(); // Unique email address
            $table->string('password'); // Password
            $table->string('discriminator'); // Discriminator field
            $table->boolean('isactive')->default(true); // Active status (default to true)
            $table->boolean('remember_me')->default(false); // Remember me option (default to false)
            $table->unsignedBigInteger('id_superior')->nullable(); // Optional foreign key to superior user

            // Define relationships
            // $table->foreign('matricule')->references('matricule')->on('employees')->onDelete('cascade');

            $table->foreign('id_superior')->references('id_user')->on('useraccount')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('useraccount');
    }
}
