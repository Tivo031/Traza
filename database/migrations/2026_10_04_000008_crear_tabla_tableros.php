<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tableros', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id_tablero');
            $table->unsignedBigInteger('id_proyecto');
            $table->unsignedBigInteger('id_creador');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->dateTime('fecha_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['id_proyecto', 'nombre'], 'cu_tableros_proyecto_nombre');
            $table->index(['id_creador'], 'ix_tableros_creador');
            $table->foreign('id_proyecto', 'cf_tableros_proyecto')->references('id_proyecto')->on('proyectos')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_creador', 'cf_tableros_creador')->references('id_usuario')->on('usuarios')->onDelete('restrict')->onUpdate('restrict');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tableros');
    }
};
