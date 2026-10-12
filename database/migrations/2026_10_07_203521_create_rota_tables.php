<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // Todas as tabelas referentes a rotina de rota são criadas juntos por conta da referência circular entre elas.
    public function up(): void
    {
        Schema::create('rota', function (Blueprint $table) {
            $table->id();
            $table->string('identificacao', 20);
            $table->string('nome', 120);
            $table->text('descricao');
            $table->string('cor', 7);
            $table->foreignId('versao_atual_id')->nullable();
            $table->smallInteger('ativo')->default(1);
            $table->timestamps();

            $table->unique('identificacao', 'uq_rota_identificacao');
            $table->unique('nome', 'uq_rota_nome');
        });

        DB::statement('ALTER TABLE rota ADD CONSTRAINT ck_rota_ativo CHECK (ativo IN (0, 1))');
        // Valida se a cor possui o formato válido em hexadecimal.
        DB::statement("ALTER TABLE rota ADD CONSTRAINT ck_rota_cor CHECK (cor ~ '^#[0-9a-f]{6}$')");

        // Cria rota_versao
        Schema::create('rota_versao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rota_id')->constrained('rota', 'id', 'fk_rota_versao_rota');
            $table->smallInteger('numero');
            $table->decimal('extensao_metros', 10, 2);
            $table->timestampTz('criada_em')->useCurrent();
            $table->foreignId('usuario_id')->constrained('usuario', 'id', 'fk_rota_versao_usuario');

            $table->unique(['rota_id', 'numero'], 'uq_rota_versao_numero');
        });

        DB::statement('ALTER TABLE rota_versao ADD CONSTRAINT ck_rota_versao_extensao CHECK (extensao_metros >= 0)');

        Schema::table('rota', function (Blueprint $table) {
            $table->foreign('versao_atual_id', 'fk_rota_rota_versao')
                  ->references('id')
                  ->on('rota_versao');
        });

        // Cria rota_trecho
        Schema::create('rota_trecho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rota_versao_id')
                  ->constrained('rota_versao', 'id', 'fk_rota_trecho_rota_versao')
                  ->cascadeOnDelete();
            $table->jsonb('geometria');
            $table->decimal('extensao_metros', 10, 2);

            $table->index('rota_versao_id', 'ix_rota_trecho_versao');
        });

        DB::statement('ALTER TABLE rota_trecho ADD CONSTRAINT ck_rota_trecho_extensao CHECK (extensao_metros >= 0)');

        // Cria rota_bairro
        Schema::create('rota_bairro', function (Blueprint $table) {
            $table->foreignId('rota_id')
                  ->constrained('rota', 'id', 'fk_rota_bairro_rota')
                  ->cascadeOnDelete();
            $table->foreignId('bairro_id')
                  ->constrained('bairro', 'id', 'fk_rota_bairro_bairro');

            $table->primary(['rota_id', 'bairro_id'], 'pk_rota_bairro');
            $table->index('bairro_id', 'ix_rota_bairro_bairro');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rota_bairro');
        Schema::dropIfExists('rota_trecho');

        Schema::table('rota', function (Blueprint $table) {
            $table->dropForeign('fk_rota_rota_versao');
        });

        Schema::dropIfExists('rota_versao');
        Schema::dropIfExists('rota');
    }
};