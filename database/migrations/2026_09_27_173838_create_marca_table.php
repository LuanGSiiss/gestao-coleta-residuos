<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marca', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 60);
            $table->smallInteger('ativo')->default(1);
            $table->timestamps();

            $table->unique('nome', 'uq_marca_nome');
        });

        DB::statement('ALTER TABLE marca ADD CONSTRAINT ck_marca_ativo CHECK (ativo IN (0, 1))');
    }

    public function down(): void
    {
        Schema::dropIfExists('marca');
    }
};
