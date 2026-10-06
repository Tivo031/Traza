<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subtareas', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id_subtarea');
            $table->unsignedBigInteger('id_tarea');
            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('posicion');
            $table->dateTime('fecha_finalizacion')->nullable();
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->dateTime('fecha_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->index(['id_tarea', 'posicion', 'id_subtarea'], 'ix_subtareas_tarea_orden');
            $table->foreign('id_tarea', 'cf_subtareas_tarea')->references('id_tarea')->on('tareas')->onDelete('restrict')->onUpdate('restrict');
        });
        DB::statement("ALTER TABLE subtareas ADD CONSTRAINT ck_subtareas_finalizacion CHECK (fecha_finalizacion IS NULL OR fecha_finalizacion >= fecha_creacion)");
    }

    public function down(): void
    {
        Schema::dropIfExists('subtareas');
    }
};
