@extends('layouts.acceso')
@section('titulo', '419')
@section('encabezado', 'La sesión del formulario venció')
@section('descripcion', 'Recarga la página y vuelve a intentarlo.')
@section('formulario')
<a class="btn btn-primary" href="{{ route('login') }}">Volver al acceso</a>
@endsection
