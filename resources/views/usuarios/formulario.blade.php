@extends('layouts.aplicacion')
@section('titulo', 'Editar usuario')
@section('contenido')
<a class="volver-tablero" href="{{ route('usuarios.index') }}"><span data-icono="volver"></span>Volver a usuarios</a>
<div class="cabecera-pagina"><div><span class="etiqueta-superior">ADMINISTRACIÓN</span><h1>Editar usuario</h1><p>Actualiza los datos de identificación. La contraseña y el rol no se editan aquí.</p></div></div>
<form class="superficie ancho-formulario" method="POST" action="{{ route('usuarios.update', $usuario) }}">
    @csrf @method('PUT')
    <div class="mb-3"><label for="nombre" class="form-label">Nombre completo</label>
        <input id="nombre" name="nombre" required maxlength="150" value="{{ old('nombre', $usuario->nombre) }}" class="form-control @error('nombre') is-invalid @enderror" autofocus>
        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3"><label for="correo" class="form-label">Correo de acceso</label>
        <input id="correo" name="correo" type="email" required maxlength="254" value="{{ old('correo', $usuario->correo) }}" class="form-control @error('correo') is-invalid @enderror" aria-describedby="ayuda-correo">
        @error('correo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text" id="ayuda-correo">Si cambias el correo, la persona deberá usar el nuevo para iniciar sesión.</div>
    </div>
    <p class="texto-mini texto-secundario">Rol actual: <strong>{{ $usuario->rol->nombre }}</strong>.</p>
    <div class="grupo-en-linea mt-4"><button class="btn btn-primary" type="submit">Guardar cambios</button><a class="btn btn-light" href="{{ route('usuarios.index') }}">Cancelar</a></div>
</form>
@endsection
