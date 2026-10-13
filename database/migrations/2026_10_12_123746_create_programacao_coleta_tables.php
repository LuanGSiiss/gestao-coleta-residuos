<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Criando tabela de programacao_coleta.
        Schema::create('programacao_coleta', function (Blueprint $table) {
            $table->id();
            $table->string('descricao', 120);
            $table->foreignId('rota_id')->constrained('rota', 'id', 'fk_programacao_coleta_rota');
            $table->foreignId('motorista_id')->constrained('funcionario', 'id', 'fk_programacao_coleta_motorista');
            $table->foreignId('veiculo_id')->constrained('veiculo', 'id', 'fk_programacao_coleta_veiculo');
            $table->smallInteger('tipo_coleta');
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->text('orientacoes')->nullable();
            $table->smallInteger('situacao')->default(1);
            $table->timestampTz('encerrada_em')->nullable();
            $table->timestamps();

            // Criação de indices para as chave estrangeiras, já que o postgres não faz isso por padrão.
            $table->index('rota_id', 'ix_programacao_coleta_rota');
            $table->index('motorista_id', 'ix_programacao_coleta_motorista');
            $table->index('veiculo_id', 'ix_programacao_coleta_veiculo');
        });

        DB::statement('ALTER TABLE programacao_coleta ADD CONSTRAINT ck_programacao_coleta_tipo_coleta CHECK (tipo_coleta IN (1, 2))');
        DB::statement('ALTER TABLE programacao_coleta ADD CONSTRAINT ck_programacao_coleta_situacao   CHECK (situacao IN (1, 2))');
        DB::statement('ALTER TABLE programacao_coleta ADD CONSTRAINT ck_programacao_coleta_periodo    CHECK (data_fim >= data_inicio)');
        DB::statement('ALTER TABLE programacao_coleta ADD CONSTRAINT ck_programacao_coleta_horario    CHECK (hora_fim > hora_inicio)');

        // Check para validar se o registro possui um data de encerramento ao alterar a situação para encerrada.
        DB::statement('ALTER TABLE programacao_coleta ADD CONSTRAINT ck_programacao_coleta_encerramento CHECK ((situacao = 2) = (encerrada_em IS NOT NULL))');

        // Criando tabela de programacao_coleta_dia_semana.
        Schema::create('programacao_coleta_dia_semana', function (Blueprint $table) {
            $table->foreignId('programacao_coleta_id')
                  ->constrained('programacao_coleta', 'id', 'fk_programacao_coleta_dia_semana_programacao_coleta')
                  ->cascadeOnDelete();
            $table->smallInteger('dia_semana');

            $table->primary(['programacao_coleta_id', 'dia_semana'], 'pk_programacao_coleta_dia_semana');
        });

        DB::statement('ALTER TABLE programacao_coleta_dia_semana ADD CONSTRAINT ck_programacao_coleta_dia_semana CHECK (dia_semana BETWEEN 1 AND 7)');

        // Criando tabela de programacao_coleta_coletor.
        Schema::create('programacao_coleta_coletor', function (Blueprint $table) {
            $table->foreignId('programacao_coleta_id')
                  ->constrained('programacao_coleta', 'id', 'fk_programacao_coleta_coletor_programacao_coleta')
                  ->cascadeOnDelete();
            $table->foreignId('funcionario_id')->constrained('funcionario', 'id', 'fk_programacao_coleta_coletor_funcionario');

            $table->primary(['programacao_coleta_id', 'funcionario_id'], 'pk_programacao_coleta_coletor');
            $table->index('funcionario_id', 'ix_programacao_coleta_coletor_funcionario');
        });

        // Criando tabela de programacao_coleta_coletor.
        Schema::create('execucao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programacao_coleta_id')->constrained('programacao_coleta', 'id', 'fk_execucao_programacao_coleta');
            $table->date('data');
            $table->time('hora_inicio_prevista');
            $table->time('hora_fim_prevista');
            $table->foreignId('motorista_id')->constrained('funcionario', 'id', 'fk_execucao_motorista');
            $table->foreignId('veiculo_id')->constrained('veiculo', 'id', 'fk_execucao_veiculo');
            $table->foreignId('rota_versao_id')->constrained('rota_versao', 'id', 'fk_execucao_rota_versao');
            $table->smallInteger('situacao')->default(1);
            $table->text('orientacoes')->nullable();
            $table->timestampTz('iniciada_em')->nullable();
            $table->timestampTz('concluida_em')->nullable();
            $table->decimal('quilometragem', 8, 2)->nullable();
            $table->decimal('quantidade_kg', 10, 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->text('motivo_cancelamento')->nullable();

            $table->unique(['programacao_coleta_id', 'data'], 'uq_execucao_programacao_coleta_data');

            $table->index(['data', 'situacao'], 'ix_execucao_data_situacao');
            $table->index(['motorista_id', 'data'], 'ix_execucao_motorista_data');
            $table->index('veiculo_id', 'ix_execucao_veiculo');
            $table->index('rota_versao_id', 'ix_execucao_rota_versao');
        });

        DB::statement('ALTER TABLE execucao ADD CONSTRAINT ck_execucao_situacao CHECK (situacao IN (1, 2, 3, 4))');
        DB::statement('ALTER TABLE execucao ADD CONSTRAINT ck_execucao_horario  CHECK (hora_fim_prevista > hora_inicio_prevista)');
        DB::statement('ALTER TABLE execucao ADD CONSTRAINT ck_execucao_inicio CHECK (situacao <> 2 OR iniciada_em IS NOT NULL)');
        DB::statement('
            ALTER TABLE execucao ADD CONSTRAINT ck_execucao_conclusao CHECK (
                situacao <> 3
                OR (iniciada_em IS NOT NULL AND concluida_em IS NOT NULL AND quilometragem IS NOT NULL AND quantidade_kg IS NOT NULL)
            )
        ');
        DB::statement('ALTER TABLE execucao ADD CONSTRAINT ck_execucao_cancelamento CHECK (situacao <> 4 OR motivo_cancelamento IS NOT NULL)');
        DB::statement('ALTER TABLE execucao ADD CONSTRAINT ck_execucao_valores CHECK ((quilometragem IS NULL OR quilometragem >= 0) AND (quantidade_kg IS NULL OR quantidade_kg >= 0))');

        // Criando tabela de programacao_coleta_coletor.
        Schema::create('execucao_coletor', function (Blueprint $table) {
            $table->foreignId('execucao_id')
                  ->constrained('execucao', 'id', 'fk_execucao_coletor_execucao')
                  ->cascadeOnDelete();
            $table->foreignId('funcionario_id')
                  ->constrained('funcionario', 'id', 'fk_execucao_coletor_funcionario');

            $table->primary(['execucao_id', 'funcionario_id'], 'pk_execucao_coletor');
            $table->index('funcionario_id', 'ix_execucao_coletor_funcionario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execucao_coletor');
        Schema::dropIfExists('execucao');
        Schema::dropIfExists('programacao_coleta_coletor');
        Schema::dropIfExists('programacao_coleta_dia_semana');
        Schema::dropIfExists('programacao_coleta');
    }
};