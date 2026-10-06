<?php

namespace App\Models;

use App\Notifications\RecuperacionContrasena;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;
    protected $fillable = ['nombre', 'correo', 'contrasena'];
    protected $hidden = ['contrasena', 'token_recordatorio'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'fecha_verificacion_correo' => 'datetime',
            'fecha_creacion' => 'datetime', 'fecha_actualizacion' => 'datetime'];
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    public function getRememberTokenName(): string
    {
        return 'token_recordatorio';
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    public function routeNotificationForMail($notification = null): string
    {
        return $this->correo;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RecuperacionContrasena($token));
    }

    public function esAdministrador(): bool
    {
        return $this->activo && $this->rol?->codigo === 'ADMINISTRADOR';
    }

    public function tableros(): BelongsToMany
    {
        return $this->belongsToMany(Tablero::class, 'usuarios_tableros', 'id_usuario', 'id_tablero', 'id_usuario', 'id_tablero')
            ->withPivot(['id_usuario_tablero', 'activo']);
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(UsuarioTablero::class, 'id_usuario', 'id_usuario');
    }

    public function proyectosCreados(): HasMany
    {
        return $this->hasMany(Proyecto::class, 'id_creador', 'id_usuario');
    }

    public function tablerosCreados(): HasMany
    {
        return $this->hasMany(Tablero::class, 'id_creador', 'id_usuario');
    }

    public function tareasCreadas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'id_creador', 'id_usuario');
    }

    public function tareasAsignadas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'id_responsable', 'id_usuario');
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(ComentarioTarea::class, 'id_usuario', 'id_usuario');
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(ActividadTarea::class, 'id_actor', 'id_usuario');
    }
}
