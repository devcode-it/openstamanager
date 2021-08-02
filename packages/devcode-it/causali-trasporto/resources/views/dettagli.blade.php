@extends('modules.base')

@section('module_content')
    <form action="" method="post" id="edit-form">
        <input type="hidden" name="backto" value="record-edit">
        <input type="hidden" name="op" value="update">

        <div class="row">
            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-12">
                        {[ "type": "text", "label": "{{ _i('Descrizione') }}", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">{{ _i('Flags') }}</h3>
                    </div>
                    <div class="panel-body">
                        <div class="col-md-4">
                            {[ "type": "checkbox", "label": "{{ _i('Causale predefinita') }}", "name": "predefined", "value": "$predefined$", "help":"{{ _i('Impostare questa causale di trasporto come predefinita per i ddt') }}." ]}
                        </div>
                        <div class="col-md-4">
                            {[ "type": "checkbox", "label": "{{ _i('Importabile?') }}", "name": "is_importabile", "value": "$is_importabile$", "help": "{{ _i('I documenti associati a questa causale possono essere importati a livello contabile in altri documenti (per esempio, in Fatture)') }}", "placeholder": "{{ _i('Importabile') }}" ]}
                        </div>
                        <div class="col-md-4">
                            {[ "type": "checkbox", "label": "{{ _i('Abilita storno') }}", "name": "reversed", "value": "$reversed$", "help": "{{ _i('I documenti associati a questa causale possono essere stornati come nota di credito') }}", "placeholder": "{{ _i('Abilita storno') }}" ]}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
