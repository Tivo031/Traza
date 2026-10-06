@extends('layouts.acceso')
@section('titulo', 'Ingresar')
@section('encabezado', 'Bienvenido de nuevo.')
@section('descripcion', 'Un equipo conectado empieza por un espacio compartido.')
@section('formulario')
<form method="POST" action="{{ route('login') }}">
    @csrf
<div class="mb-3"><label class="form-label" for="correo">Correo electrónico</label><input class="form-control @error('correo') is-invalid @enderror" id="correo" name="correo" type="email" value="{{ old('correo') }}" autocomplete="username" maxlength="254" required></div><div class="mb-3"><label class="form-label" for="contrasena">Contraseña</label><div class="password-contenedor"><input class="form-control @error('contrasena') is-invalid @enderror" id="contrasena" name="contrasena" type="password" autocomplete="current-password" required><button type="button" class="btn-texto" data-ver-clave="contrasena" aria-label="Mostrar contraseña" aria-pressed="false"><span data-icono="ojo"></span></button></div></div>
    <div class="d-flex justify-content-between align-items-center gap-2 mb-4">
        <label class="form-check texto-mini"><input class="form-check-input" name="recordarme" type="checkbox" value="1" @checked(old('recordarme'))>Recordarme</label>
        <a class="enlace-pequeno" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
    </div>
    <button class="btn btn-primary" type="submit">Ingresar <span data-icono="flecha"></span></button>
</form>
<p class="text-center texto-mini mt-3">¿Aún no tienes cuenta? <a href="{{ route('register') }}">Crear cuenta</a></p>
@endsection
