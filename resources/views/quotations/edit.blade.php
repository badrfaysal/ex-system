@extends('layouts.app')
@section('header_title', __('messages.quotations.edit_title'))

@section('content')
    @include('quotations._form')
@endsection
