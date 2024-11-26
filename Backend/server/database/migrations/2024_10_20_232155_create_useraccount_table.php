<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUseraccountTable extends Migration
{
    public function up()
    {
        Schema::create('useraccount', function (Blueprint $table) {
            $table->string('matricule')->primary(); // 'matricule' comme clé primaire
            $table->string('username')->unique(); 
            $table->string('password'); // Mot de passe
            $table->string('discriminator'); // Champ discriminant
            $table->boolean('isactive')->default(true); // Statut actif (par défaut à true)
            $table->boolean('remember_me')->default(false); // Option de mémorisation (par défaut à false)
            // $table->string('id_superior')->nullable(); // Référence à un utilisateur supérieur, même type que 'matricule'
        });
    }

    public function down()
    {
        Schema::dropIfExists('useraccount');
    }
}
