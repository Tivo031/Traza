@extends('layouts.acceso')
@section('titulo', '404')
@section('encabezado', 'Página no encontrada')
@section('descripcion', 'La dirección solicitada no existe.')
@section('formulario')
<a class="btn btn-primary" href="{{ route('login') }}">Volver al acceso</a>
@endsection
