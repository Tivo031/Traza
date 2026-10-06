@extends('layouts.acceso')
@section('titulo', 'Registro')
@section('encabezado', 'Cada gran proyecto empieza aquí.')
@section('descripcion', 'Crea tu cuenta para formar parte del equipo.')
@section('formulario')
<form method="POST" action="{{ route('register') }}">
    @csrf
<div class="mb-3"><label class="form-label" for="nombre">Nombre completo</label><input class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" autocomplete="name" maxlength="150" required></div><div class="mb-3"><label class="form-label" for="correo">Correo electrónico</label><input class="form-control @error('correo') is-invalid @enderror" id="correo" name="correo" type="email" value="{{ old('correo') }}" autocomplete="username" maxlength="254" required></div><div class="mb-3"><label class="form-label" for="contrasena">Contraseña</label><div class="password-contenedor"><input class="form-control @error('contrasena') is-invalid @enderror" id="contrasena" name="contrasena" type="password" autocomplete="new-password" required><button type="button" class="btn-texto" data-ver-clave="contrasena" aria-label="Mostrar contraseña" aria-pressed="false"><span data-icono="ojo"></span></button></div><div class="form-text">Al menos 12 caracteres, una letra y un número. Máximo 72 bytes.</div></div><div class="mb-3"><label class="form-label" for="confirmacion_contrasena">Confirmar contraseña</label><div class="password-contenedor"><input class="form-control @error('confirmacion_contrasena') is-invalid @enderror" id="confirmacion_contrasena" name="confirmacion_contrasena" type="password" autocomplete="new-password" required><button type="button" class="btn-texto" data-ver-clave="confirmacion_contrasena" aria-label="Mostrar contraseña" aria-pressed="false"><span data-icono="ojo"></span></button></div></div>
    <p class="texto-mini texto-secundario">Tu cuenta tendrá rol Miembro. El Administrador asignará tus tableros.</p>
    <button class="btn btn-primary" type="submit">Crear cuenta <span data-icono="flecha"></span></button>
</form>
<p class="text-center texto-mini mt-3">¿Ya tienes cuenta? <a href="{{ route('login') }}">Iniciar sesión</a></p>
@endsection
