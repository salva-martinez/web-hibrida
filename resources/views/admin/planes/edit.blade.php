@extends('layouts.app')
@section('title', 'Editar Plan')
@section('content')
@include('admin.planes.create', ['plan' => $plan, 'pacientes' => $pacientes, 'estimulos' => $estimulos])
@overwrite