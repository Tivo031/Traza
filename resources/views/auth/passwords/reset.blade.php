@extends('layouts.acceso')
@section('titulo', 'Restablecer contraseña')
@section('encabezado', 'Una nueva contraseña.')
@section('descripcion', 'Elige una clave nueva y confírmala para continuar.')
@section('formulario')
<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
<div class="mb-3"><label class="form-label" for="correo">Correo electrónico</label><input class="form-control @error('correo') is-invalid @enderror" id="correo" name="correo" type="email" value="{{ old('correo', $correo) }}" autocomplete="username" maxlength="254" required></div><div class="mb-3"><label class="form-label" for="contrasena">Nueva contraseña</label><div class="password-contenedor"><input class="form-control @error('contrasena') is-invalid @enderror" id="contrasena" name="contrasena" type="password" autocomplete="new-password" required><button type="button" class="btn-texto" data-ver-clave="contrasena" aria-label="Mostrar contraseña" aria-pressed="false"><span data-icono="ojo"></span></button></div><div class="form-text">Mínimo 12 caracteres, una letra y un número; máximo 72 bytes.</div></div><div class="mb-3"><label class="form-label" for="confirmacion_contrasena">Confirmar contraseña</label><div class="password-contenedor"><input class="form-control @error('confirmacion_contrasena') is-invalid @enderror" id="confirmacion_contrasena" name="confirmacion_contrasena" type="password" autocomplete="new-password" required><button type="button" class="btn-texto" data-ver-clave="confirmacion_contrasena" aria-label="Mostrar contraseña" aria-pressed="false"><span data-icono="ojo"></span></button></div></div>
    <button class="btn btn-primary" type="submit">Restablecer contraseña</button>
</form>
<p class="text-center texto-mini mt-3"><a href="{{ route('password.request') }}">Solicitar un nuevo enlace</a></p>
@endsection
