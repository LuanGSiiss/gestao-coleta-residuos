<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('email', 180);
            $table->string('password');
            $table->smallInteger('perfil');
            $table->smallInteger('ativo')->default(1);
            $table->rememberToken();
            $table->timestamps();

            $table->unique('email', 'uq_usuario_email');
        });

        DB::statement('ALTER TABLE usuario ADD CONSTRAINT ck_usuario_perfil CHECK (perfil IN (1, 2, 3))');
        DB::statement('ALTER TABLE usuario ADD CONSTRAINT ck_usuario_ativo  CHECK (ativo  IN (0, 1))');

        // Parte do Laravel, não mexer.
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario');
        Schema::dropIfExists('sessions');
    }
};
