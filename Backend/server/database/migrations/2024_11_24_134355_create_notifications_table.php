<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('id_notification'); // Clé primaire
            $table->string('id_user')->nullable()->constrained('useraccount', 'matricule')->onDelete('cascade'); // Utilisateur recevant la notification
            $table->string('event_type');
            $table->text('message');
            $table->json('data')->nullable(); // Données liées à la notification
            $table->boolean('is_read')->default(false); // Statut de lecture
            $table->timestamps(); // Timestamps created_at et updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
