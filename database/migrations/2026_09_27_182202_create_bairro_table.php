<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bairro', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->smallInteger('ativo')->default(1);
            $table->timestamps();

            $table->unique('nome', 'uq_bairro_nome');
        });

        DB::statement('ALTER TABLE bairro ADD CONSTRAINT ck_bairro_ativo CHECK (ativo IN (0, 1))');
    }

    public function down(): void
    {
        Schema::dropIfExists('bairro');
    }
};