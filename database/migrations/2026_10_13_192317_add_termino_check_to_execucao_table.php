<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE execucao ADD CONSTRAINT ck_execucao_termino CHECK (concluida_em > iniciada_em)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE execucao DROP CONSTRAINT ck_execucao_termino');
    }
};