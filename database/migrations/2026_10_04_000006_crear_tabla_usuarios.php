<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id_usuario');
            $table->unsignedBigInteger('id_rol');
            $table->string('nombre', 150);
            $table->string('correo', 254);
            $table->string('contrasena', 255);
            $table->unsignedTinyInteger('activo')->default(1);
            $table->dateTime('fecha_verificacion_correo')->nullable();
            $table->string('token_recordatorio', 100)->nullable();
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->dateTime('fecha_actualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['correo'], 'cu_usuarios_correo');
            $table->index(['id_rol'], 'ix_usuarios_rol');
            $table->foreign('id_rol', 'cf_usuarios_rol')->references('id_rol')->on('roles')->onDelete('restrict')->onUpdate('restrict');
        });
        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT ck_usuarios_activo CHECK (activo IN (0, 1))");
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
