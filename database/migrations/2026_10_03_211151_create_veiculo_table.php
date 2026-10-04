<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculo', function (Blueprint $table) {
            $table->id();
            $table->string('modelo', 80);
            $table->foreignId('marca_id')->constrained('marca', 'id', 'fk_veiculo_marca');
            $table->string('placa', 8);
            $table->smallInteger('ano');
            $table->smallInteger('tipo');
            $table->decimal('capacidade_toneladas', 6, 2)->nullable();
            $table->smallInteger('ativo')->default(1);
            $table->timestamps();

            $table->unique('placa', 'uq_veiculo_placa');

            $table->index('marca_id', 'ix_veiculo_marca');
        });

        DB::statement('ALTER TABLE veiculo ADD CONSTRAINT ck_veiculo_tipo  CHECK (tipo IN (1, 2))');
        DB::statement('ALTER TABLE veiculo ADD CONSTRAINT ck_veiculo_ativo CHECK (ativo IN (0, 1))');
        DB::statement('ALTER TABLE veiculo ADD CONSTRAINT ck_veiculo_ano   CHECK (ano BETWEEN 1950 AND 2100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculo');
    }
};