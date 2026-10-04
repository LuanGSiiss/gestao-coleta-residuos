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
            $table->foreignId('funcionario_id')
                  ->nullable()
                  ->constrained('funcionario', 'id', 'fk_usuario_funcionario');

            $table->unique('funcionario_id', 'uq_usuario_funcionario');
        });

        DB::statement('ALTER TABLE usuario ADD CONSTRAINT ck_usuario_vinculo CHECK (funcionario_id IS NULL OR perfil = 3)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE usuario DROP CONSTRAINT ck_usuario_vinculo');

        Schema::table('usuario', function (Blueprint $table) {
            $table->dropForeign('fk_usuario_funcionario');
            $table->dropUnique('uq_usuario_funcionario');
            $table->dropColumn('funcionario_id');
        });
    }
};