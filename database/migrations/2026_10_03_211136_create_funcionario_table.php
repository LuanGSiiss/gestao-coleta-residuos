<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcionario', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('cpf', 11);
            $table->string('cargo', 60);
            $table->smallInteger('tipo');
            $table->smallInteger('carga_horaria_semanal');
            $table->string('cnh', 20)->nullable();
            $table->string('cnh_categoria', 5)->nullable();
            $table->date('cnh_validade')->nullable();
            $table->smallInteger('ativo')->default(1);
            $table->timestamps();

            $table->unique('cpf', 'uq_funcionario_cpf');
        });

        DB::statement('ALTER TABLE funcionario ADD CONSTRAINT ck_funcionario_tipo  CHECK (tipo IN (1, 2))');
        DB::statement('ALTER TABLE funcionario ADD CONSTRAINT ck_funcionario_ativo CHECK (ativo IN (0, 1))');
        DB::statement('ALTER TABLE funcionario ADD CONSTRAINT ck_funcionario_carga CHECK (carga_horaria_semanal > 0)');

        DB::statement('
            ALTER TABLE funcionario ADD CONSTRAINT ck_funcionario_cnh CHECK (
                tipo <> 1
                OR (cnh IS NOT NULL AND cnh_categoria IS NOT NULL AND cnh_validade IS NOT NULL)
            )
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('funcionario');
    }
};