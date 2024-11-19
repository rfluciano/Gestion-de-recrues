<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id('id_request'); // Auto-incrementing primary key
            $table->foreignId('id_requester')->nullable()->constrained('useraccount', 'id_user')->onDelete('set null'); // Foreign key to User
            $table->foreignId('id_resource')->constrained('resources', 'id_resource')->onDelete('cascade'); // Foreign key to Resource
            $table->foreignId('id_receiver')->nullable()->constrained('useraccount', 'id_user')->onDelete('set null'); // Foreign key to User // Receiver's identifier (e.g., username or email)
            $table->date('request_date')->nullable(); // Date of request
            // $table->foreignId('id_validation')->nullable()->constrained('validation', 'id_validation')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('requests');
    }
}
