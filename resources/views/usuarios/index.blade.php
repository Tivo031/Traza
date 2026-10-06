@extends('layouts.aplicacion')
@section('titulo', 'Usuarios')
@section('contenido')
<div class="cabecera-pagina"><div><span class="etiqueta-superior">ADMINISTRACIÓN</span><h1>Usuarios del sistema</h1><p>Administra cuentas sin eliminar sus referencias históricas.</p></div></div>
<div class="table-responsive"><table class="table tabla-gestion tabla-organizacion"><caption class="visually-hidden">Cuentas registradas en Traza</caption><thead><tr><th scope="col">Nombre</th><th scope="col">Correo</th><th scope="col">Rol</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead><tbody>
@forelse($usuarios as $usuario)
<tr>
    <td><strong>{{ $usuario->nombre }}</strong></td><td>{{ $usuario->correo }}</td><td>{{ $usuario->rol->nombre }}</td>
    <td><span class="etiqueta {{ $usuario->activo ? 'estado-4' : 'categoria-1' }}">{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</span></td>
    <td><div class="grupo-en-linea">
        <a class="btn btn-light btn-sm" href="{{ route('usuarios.edit', $usuario) }}" aria-label="Editar usuario {{ $usuario->nombre }}">Editar</a>
        <form method="POST" action="{{ route('usuarios.estado', $usuario) }}" data-confirmar="{{ $usuario->activo ? '¿Desactivar esta cuenta? Se conservará su historial.' : '¿Activar esta cuenta?' }}">
            @csrf @method('PATCH')<input type="hidden" name="activo" value="{{ $usuario->activo ? 0 : 1 }}">
            <button type="submit" class="btn btn-light btn-sm">{{ $usuario->activo ? 'Desactivar' : 'Activar' }}</button>
        </form>
    </div></td>
</tr>
@empty <tr><td colspan="5">No hay usuarios registrados.</td></tr> @endforelse
</tbody></table></div>
<div class="mt-4">{{ $usuarios->links() }}</div>
<p class="form-text">No se modifica el rol desde este módulo. Las cuentas nuevas se crean desde Registro como Miembro. Debe permanecer al menos un Administrador activo.</p>
@endsection
