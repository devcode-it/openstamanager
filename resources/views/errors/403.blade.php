@extends('errors.base')

@section('title', tr("Accesso negato!"))

@section('error_color', 'info')
@section('error_header', '403')

@section('error_message', tr('Oops! Accesso negato'))
@section('error_info', tr('Non possiedi permessi sufficienti per accedere alla pagina da te richiesta'))
