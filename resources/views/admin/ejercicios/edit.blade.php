@extends('layouts.app')
@section('title', 'Editar Ejercicio')
@section('content')
@include('admin.ejercicios.create', ['ejercicio' => $ejercicio, 'estimulos' => $estimulos])
@overwrite