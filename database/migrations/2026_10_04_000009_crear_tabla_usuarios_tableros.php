<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios_tableros', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id_usuario_tablero');
            $table->unsignedBigInteger('id_tablero');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedTinyInteger('activo')->default(1);
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->dateTime('fecha_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['id_tablero', 'id_usuario'], 'cu_usuarios_tableros_pareja');
            $table->index(['id_usuario', 'activo', 'id_tablero'], 'ix_ut_usuario_activo');
            $table->foreign('id_tablero', 'cf_ut_tablero')->references('id_tablero')->on('tableros')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_usuario', 'cf_ut_usuario')->references('id_usuario')->on('usuarios')->onDelete('restrict')->onUpdate('restrict');
        });
        DB::statement("ALTER TABLE usuarios_tableros ADD CONSTRAINT ck_ut_activo CHECK (activo IN (0, 1))");
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_tableros');
    }
};
