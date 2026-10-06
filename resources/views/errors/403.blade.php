@extends('layouts.acceso')
@section('titulo', '403')
@section('encabezado', 'Acceso no permitido')
@section('descripcion', 'Tu cuenta no tiene permiso para esta operación.')
@section('formulario')
<a class="btn btn-primary" href="{{ route('login') }}">Volver al acceso</a>
@endsection
