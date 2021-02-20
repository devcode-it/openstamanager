
@extends('layouts.app')

@section('title', tr("Accessi"))

@section('content')
<div class="box box-outline box-primary">
    <div class="box-header">
        <h3 class="box-title"><i class="fa fa-book"></i> {{ tr('Ultimi 100 accessi') }}</h3>
    </div>

    <div class="box-body table-responsive">
        <table class="datatables table table-hover">
            <thead>
                <tr>
                    <th>{{ tr('Username') }}</th>
                    <th>{{ tr('Data') }}</th>
                    <th>{{ tr('Stato') }}</th>
                    <th>{{ tr('Indirizzo IP') }}</th>
                </tr>
            </thead>

            <tbody>
            @foreach($logs as $log)
                <tr class="bg-{{ $log->color }}">
                    <td>{{ $log->username }}</td>
                    <td>{{ timestampFormat($log->created_at) }}</td>
                    <td><span class="label label-{{ $log->color }}">{{ $log->message }}</span></td>
                    <td>{{ $log->ip }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
