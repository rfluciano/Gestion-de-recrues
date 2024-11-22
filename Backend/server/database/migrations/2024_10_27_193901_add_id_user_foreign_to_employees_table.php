<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdUserForeignToEmployeesTable extends Migration
{
    public function up()
    {
        // Ajouter la contrainte de clé étrangère sur id_superior après la création de la table
        Schema::table('useraccount', function (Blueprint $table) {
            $table->foreign('id_superior')
                  ->references('matricule')
                  ->on('useraccount')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        // Supprimer la contrainte de clé étrangère sur id_superior
        Schema::table('useraccount', function (Blueprint $table) {
            $table->dropForeign(['id_superior']);
        });
    }
}
