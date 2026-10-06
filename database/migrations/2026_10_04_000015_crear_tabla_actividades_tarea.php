<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades_tarea', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id_actividad');
            $table->unsignedBigInteger('id_tarea');
            $table->unsignedBigInteger('id_actor');
            $table->string('codigo_accion', 40);
            $table->unsignedBigInteger('id_estado_anterior')->nullable();
            $table->unsignedBigInteger('id_estado_nuevo')->nullable();
            $table->text('observacion')->nullable();
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->index(['id_tarea', 'fecha_creacion', 'id_actividad'], 'ix_actividades_tarea_fecha');
            $table->index(['id_actor'], 'ix_actividades_actor');
            $table->index(['id_estado_anterior'], 'ix_actividades_estado_anterior');
            $table->index(['id_estado_nuevo'], 'ix_actividades_estado_nuevo');
            $table->foreign('id_tarea', 'cf_actividades_tarea')->references('id_tarea')->on('tareas')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_actor', 'cf_actividades_actor')->references('id_usuario')->on('usuarios')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_estado_anterior', 'cf_actividades_estado_anterior')->references('id_estado')->on('estados_tarea')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_estado_nuevo', 'cf_actividades_estado_nuevo')->references('id_estado')->on('estados_tarea')->onDelete('restrict')->onUpdate('restrict');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('actividades_tarea');
    }
};
