@extends('layouts.acceso')
@section('titulo', 'Recuperar contraseña')
@section('encabezado', 'Recuperemos tu acceso.')
@section('descripcion', 'Escribe el correo de tu cuenta para solicitar un enlace.')
@section('formulario')
<form method="POST" action="{{ route('password.email') }}">
    @csrf
<div class="mb-3"><label class="form-label" for="correo">Correo electrónico</label><input class="form-control @error('correo') is-invalid @enderror" id="correo" name="correo" type="email" value="{{ old('correo') }}" autocomplete="email" maxlength="254" required></div>
    <button class="btn btn-primary" type="submit">Solicitar enlace <span data-icono="flecha"></span></button>
</form>
@if(app()->environment('local') && config('mail.default') === 'log')
    <div class="alert alert-secondary mt-3 texto-mini">Modo de desarrollo: el mensaje se escribe en el registro privado del servidor; no llega al correo. Configura SMTP para el envío real.</div>
@endif
<p class="text-center texto-mini mt-3"><a href="{{ route('login') }}">Volver a iniciar sesión</a></p>
@endsection
