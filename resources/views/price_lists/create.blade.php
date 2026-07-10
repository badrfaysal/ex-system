@extends('layouts.app')
@section('header_title', __('messages.price_lists.add_title'))

@section('content')
    @include('price_lists._form')
@endsection
