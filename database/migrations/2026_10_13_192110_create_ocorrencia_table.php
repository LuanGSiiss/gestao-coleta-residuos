<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocorrencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execucao_id')
                  ->constrained('execucao', 'id', 'fk_ocorrencia_execucao')
                  ->cascadeOnDelete();
            $table->string('titulo', 120);
            $table->text('descricao');
            $table->timestampTz('data_hora');
            $table->string('endereco', 200)->nullable();

            $table->foreignId('usuario_id')->constrained('usuario', 'id', 'fk_ocorrencia_usuario');
            $table->timestampTz('registrada_em')->useCurrent();

            $table->index('execucao_id', 'ix_ocorrencia_execucao');
        });

        DB::statement('ALTER TABLE ocorrencia ADD CONSTRAINT ck_ocorrencia_data_hora CHECK (data_hora <= registrada_em)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ocorrencia');
    }
};