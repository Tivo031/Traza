@extends('layouts.aplicacion')
@section('titulo', 'Miembros del tablero')
@section('contenido')
<a class="volver-tablero" href="{{ route('tableros.show', $tablero) }}"><span data-icono="volver"></span>Volver al tablero</a>
<div class="cabecera-pagina"><div><span class="etiqueta-superior">{{ $tablero->proyecto->nombre }}</span><h1>Miembros de {{ $tablero->nombre }}</h1><p>Concede o retira acceso sin eliminar el historial del equipo.</p></div></div>
<section class="superficie mb-4">
    <h2>Asignar una persona</h2>
    @if($usuariosDisponibles->isEmpty())
        <p class="texto-secundario mb-0">No hay cuentas activas pendientes de asignar. Las personas nuevas deben registrarse primero.</p>
    @else
    <form method="POST" action="{{ route('asignaciones.store', $tablero) }}" class="row g-3 align-items-end">
        @csrf
        <div class="col-md-8"><label for="id_usuario" class="form-label">Usuario activo</label>
            <select name="id_usuario" id="id_usuario" required class="form-select @error('id_usuario') is-invalid @enderror">
                <option value="">Selecciona una persona</option>
                @foreach($usuariosDisponibles as $persona)<option value="{{ $persona->id_usuario }}" @selected((string) old('id_usuario') === (string) $persona->id_usuario)>{{ $persona->nombre }} ({{ $persona->correo }})</option>@endforeach
            </select>
            @error('id_usuario')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4"><button type="submit" class="btn btn-primary"><span data-icono="mas"></span>Asignar al tablero</button></div>
    </form>
    @endif
    <p class="form-text mt-3 mb-0">Una asignación retirada se reactiva sin duplicar la fila. El Administrador también necesita asignación para ser responsable de una tarea.</p>
</section>
<div class="table-responsive"><table class="table tabla-gestion tabla-organizacion"><caption class="visually-hidden">Asignaciones del tablero {{ $tablero->nombre }}</caption><thead><tr><th scope="col">Persona</th><th scope="col">Cuenta</th><th scope="col">Asignación</th><th scope="col">Acción</th></tr></thead><tbody>
@forelse($asignaciones as $asignacion)
<tr>
    <td><strong>{{ $asignacion->usuario->nombre }}</strong><div class="texto-mini">{{ $asignacion->usuario->correo }}</div></td>
    <td><span class="etiqueta {{ $asignacion->usuario->activo ? 'estado-4' : 'categoria-1' }}">{{ $asignacion->usuario->activo ? 'Activa' : 'Inactiva' }}</span></td>
    <td><span class="etiqueta {{ $asignacion->activo ? 'estado-4' : 'categoria-1' }}">{{ $asignacion->activo ? 'Activa' : 'Retirada' }}</span></td>
    <td>
        @if($asignacion->activo || $asignacion->usuario->activo)
        <form method="POST" action="{{ route('asignaciones.update', [$tablero, $asignacion->usuario]) }}" @if($asignacion->activo) data-confirmar="¿Retirar el acceso de esta persona al tablero?" @endif>
            @csrf @method('PATCH')
            <input type="hidden" name="activo" value="{{ $asignacion->activo ? 0 : 1 }}">
            <button class="btn btn-light btn-sm" type="submit">{{ $asignacion->activo ? 'Retirar acceso' : 'Reactivar' }}</button>
        </form>
        @else <span class="texto-mini">Activa la cuenta en Usuarios.</span> @endif
    </td>
</tr>
@empty
<tr><td colspan="4">Todavía no hay miembros asignados.</td></tr>
@endforelse
</tbody></table></div>
<p class="form-text mt-3">Una cuenta inactiva no tiene acceso aunque conserve una asignación activa. El retiro exige reasignar sus tareas abiertas; no modifica las completadas.</p>
@endsection
