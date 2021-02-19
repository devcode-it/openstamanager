@extends('layouts.base')

@section('body_class')bg-light@endsection

@section('body')
    @yield('before_content')

<div class="box box-outline box-center-large @yield('box_class')">
    <div class="box-header text-center">
        @yield('box_header')
    </div>

    <div class="box-body">
        @yield('content')
    </div>
</div>

    @yield('after_content')
@endsection
