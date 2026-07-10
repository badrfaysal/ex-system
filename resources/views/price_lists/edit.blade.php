@extends('layouts.app')
@section('header_title', __('messages.price_lists.edit_title'))

@section('content')
    @include('price_lists._form')
@endsection
