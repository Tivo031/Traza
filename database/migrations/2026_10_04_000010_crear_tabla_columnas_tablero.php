<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('columnas_tablero', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id_columna');
            $table->unsignedBigInteger('id_tablero');
            $table->unsignedBigInteger('id_estado');
            $table->unsignedInteger('posicion');
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->dateTime('fecha_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['id_tablero', 'id_estado'], 'cu_columnas_tablero_estado');
            $table->unique(['id_tablero', 'posicion'], 'cu_columnas_tablero_posicion');
            $table->index(['id_estado'], 'ix_columnas_estado');
            $table->foreign('id_tablero', 'cf_columnas_tablero')->references('id_tablero')->on('tableros')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_estado', 'cf_columnas_estado')->references('id_estado')->on('estados_tarea')->onDelete('restrict')->onUpdate('restrict');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('columnas_tablero');
    }
};
