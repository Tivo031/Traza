<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comentarios_tarea', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id_comentario');
            $table->unsignedBigInteger('id_tarea');
            $table->unsignedBigInteger('id_usuario');
            $table->text('contenido');
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->dateTime('fecha_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->index(['id_tarea', 'fecha_creacion', 'id_comentario'], 'ix_comentarios_tarea_fecha');
            $table->index(['id_usuario'], 'ix_comentarios_usuario');
            $table->foreign('id_tarea', 'cf_comentarios_tarea')->references('id_tarea')->on('tareas')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_usuario', 'cf_comentarios_usuario')->references('id_usuario')->on('usuarios')->onDelete('restrict')->onUpdate('restrict');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('comentarios_tarea');
    }
};
