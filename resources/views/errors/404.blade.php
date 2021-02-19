@extends('errors.base')

@section('title', tr("Pagina non trovata!"))

@section('error_color', 'warning')
@section('error_header', '404')

@section('error_message', tr('Oops! Pagina non trovata'))
@section('error_info', tr('Non siamo riusciti a trovare la pagina che stavi cercando'))

@section('js')
    @if(!auth()->check())
    <script>
        location.href = "{{ route('login') }}";
    </script>
    @endif
@endsection
