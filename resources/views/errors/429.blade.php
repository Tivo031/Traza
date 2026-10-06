@extends('layouts.acceso')
@section('titulo', '429')
@section('encabezado', 'Espera un momento')
@section('descripcion', 'Se recibieron demasiadas solicitudes. Intenta de nuevo en un minuto.')
@section('formulario')
<a class="btn btn-primary" href="{{ route('login') }}">Volver al acceso</a>
@endsection
