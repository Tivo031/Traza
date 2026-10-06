<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elementos_verificacion', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id_elemento');
            $table->unsignedBigInteger('id_subtarea');
            $table->string('titulo', 150);
            $table->unsignedTinyInteger('completado')->default(0);
            $table->unsignedInteger('posicion');
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->dateTime('fecha_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->index(['id_subtarea', 'posicion', 'id_elemento'], 'ix_elementos_subtarea_orden');
            $table->foreign('id_subtarea', 'cf_elementos_subtarea')->references('id_subtarea')->on('subtareas')->onDelete('restrict')->onUpdate('restrict');
        });
        DB::statement("ALTER TABLE elementos_verificacion ADD CONSTRAINT ck_elementos_completado CHECK (completado IN (0, 1))");
    }

    public function down(): void
    {
        Schema::dropIfExists('elementos_verificacion');
    }
};
