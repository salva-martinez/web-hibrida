@extends('layouts.app')
@section('title', 'Editar Estímulo')
@section('content')
@include('admin.estimulos.create', ['estimulo' => $estimulo])
@overwrite