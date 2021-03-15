@if(!$emails->isEmpty())
<div class="box box-info collapsable collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-envelope"></i> {{ tr('Email inviate: _NUM_', [
            '_NUM_' => $emails->count(),
        ]) }}</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <ul>

            @foreach($emails as $email)
            <li>
                {{ Modules::link('Stato email', $email->id, tr('Email "_EMAIL_" da _USER_', [
                    '_EMAIL_' => $email->template->name,
                    '_USER_' => $email->user->username,
                ])) }} ({{ !empty($email['sent_at']) ? tr('inviata il _DATE_ alle _HOUR_', [
                    '_DATE_' => dateFormat($email['sent_at']),
                    '_HOUR_' => timeFormat($email['sent_at']),
                ]) : tr('in coda di invio') }}).
                <ul>
                    <li><b>{{ tr('Destinatari') }}</b>: {{ $email->receivers->pluck('address')->join(', ') }}.</li>

                    @if($email->prints->count())
                    <li><b>{{ tr('Stampe') }}</b>: {{ $email->prints->pluck('title')->join(', ') }}.</li>
                    @endif

                    @if($email->uploads->count())
                        <li><b>{{ tr('Allegati') }}</b>: {{ $email->uploads->pluck('name')->join(', ') }}.</li>
                    @endif
                </ul>
            </li>
            @endforeach

        </ul>
    </div>
</div>
@endif
