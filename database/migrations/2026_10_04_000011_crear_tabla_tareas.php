<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id_tarea');
            $table->unsignedBigInteger('id_columna');
            $table->unsignedBigInteger('id_prioridad');
            $table->unsignedBigInteger('id_tipo_tarea');
            $table->unsignedBigInteger('id_categoria');
            $table->unsignedBigInteger('id_creador');
            $table->unsignedBigInteger('id_responsable')->nullable();
            $table->string('titulo', 150);
            $table->text('descripcion');
            $table->text('criterio_aceptacion');
            $table->unsignedInteger('posicion');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_limite')->nullable();
            $table->dateTime('fecha_cierre')->nullable();
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->dateTime('fecha_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->index(['id_columna', 'posicion', 'id_tarea'], 'ix_tareas_columna_orden');
            $table->index(['id_prioridad'], 'ix_tareas_prioridad');
            $table->index(['id_tipo_tarea'], 'ix_tareas_tipo');
            $table->index(['id_categoria'], 'ix_tareas_categoria');
            $table->index(['id_creador'], 'ix_tareas_creador');
            $table->index(['id_responsable'], 'ix_tareas_responsable');
            $table->index(['fecha_limite'], 'ix_tareas_fecha_limite');
            $table->foreign('id_columna', 'cf_tareas_columna')->references('id_columna')->on('columnas_tablero')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_prioridad', 'cf_tareas_prioridad')->references('id_prioridad')->on('prioridades')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_tipo_tarea', 'cf_tareas_tipo')->references('id_tipo_tarea')->on('tipos_tarea')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_categoria', 'cf_tareas_categoria')->references('id_categoria')->on('categorias')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_creador', 'cf_tareas_creador')->references('id_usuario')->on('usuarios')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_responsable', 'cf_tareas_responsable')->references('id_usuario')->on('usuarios')->onDelete('restrict')->onUpdate('restrict');
        });
        DB::statement("ALTER TABLE tareas ADD CONSTRAINT ck_tareas_planificacion CHECK (fecha_inicio IS NULL OR fecha_limite IS NULL OR fecha_inicio <= fecha_limite)");
        DB::statement("ALTER TABLE tareas ADD CONSTRAINT ck_tareas_cierre CHECK (fecha_cierre IS NULL OR fecha_cierre >= fecha_creacion)");
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
