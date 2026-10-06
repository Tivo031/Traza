<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id_proyecto');
            $table->unsignedBigInteger('id_creador');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->dateTime('fecha_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->index(['id_creador'], 'ix_proyectos_creador');
            $table->foreign('id_creador', 'cf_proyectos_creador')->references('id_usuario')->on('usuarios')->onDelete('restrict')->onUpdate('restrict');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
