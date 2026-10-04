<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->smallInteger('deve_alterar_senha')->default(1);
        });

        DB::statement('ALTER TABLE usuario ADD CONSTRAINT ck_usuario_deve_alterar_senha CHECK (deve_alterar_senha IN (0, 1))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE usuario DROP CONSTRAINT ck_usuario_deve_alterar_senha');

        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('deve_alterar_senha');
        });
    }
};