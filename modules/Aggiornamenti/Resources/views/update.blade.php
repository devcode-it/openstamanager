{% extends "layouts/base.twig" %}

{% block body_class %}hold-transition login-page{% endblock %}
{% block title %}{{ 'Aggiornamento'|trans }}{% endblock %}

{% block body %}
<div class="card card-outline card-center-large card-warning">
    <div class="card-header">
        <a class="h5" data-toggle="tab" href="#info">{{ "Informazioni sull'aggiornamento"|trans }}</a>

        <ul class="nav nav-tabs float-right" id="tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#changelog">{{ 'Changelog'|trans }}</a>
            </li>
        </ul>
    </div>

    <div class="card-body tab-content">
        <div id="info" class="tab-pane fade in active">

            {% if update.isCoreUpdate() %}
            <p>{{ "Il pacchetto selezionato contiene un aggiornamento dell'intero gestionale"|trans }}.</p>
            <p>{{ "Si consiglia vivamente di effettuare un backup dell'installazione prima di procedere"|trans }}.</p>

            <button type="button" class="btn btn-primary float-right" onclick="backup()">
                <i class="fa fa-database"></i> {{ 'Crea backup'|trans }}
            </button>

            <div class="clearfix"></div>
            <hr>

            <h3 class="text-center">{{ 'OpenSTAManager versione _VERSION_'|trans({'_VERSION_': update.getVersion()}) }}</h3>

            {% include 'config/list.twig' with {requirements: update.getRequirements()} only %}

            {% else %}
            {% set elements = update.componentUpdates() %}

            <div class="alert alert-warning">
                <i class="fa fa-warning"></i>
                <b>{{ 'Attenzione!'|trans }}</b> {{ 'Verranno aggiornate le sole componenti del pacchetto che non sono già installate e aggiornate'|trans }}.
            </div>

            {% if elements.modules is not empty %}
            <p>{{ 'Il pacchetto selezionato comprende i seguenti moduli'|trans }}:</p>
            <ul class="list-group">

                {% for element in elements.modules %}
                <li class="list-group-item">
                    <span class="badge">{{ element['info']['version'] }}</span>

                    {% if element.is_installed %}
                    <span class="badge">{{ 'Installato'|trans }}</span>';
                    {% endif %}

                    {{ element['info']['name'] }}
                </li>
                {% endfor %}

            </ul>
            {% endif %}

            {% if elements.plugins is not empty %}
            <p>{{ 'Il pacchetto selezionato comprende i seguenti plugin'|trans }}:</p>
            <ul class="list-group">';

                {% for element in elements.plugins %}
                <li class="list-group-item">
                    <span class="badge">{{ element['info']['version'] }}</span>

                    {% if element.is_installed %}
                    <span class="badge">{{ 'Installato'|trans }}</span>';
                    {% endif %}

                    {{ element['info']['name'] }}
                </li>
                {% endfor %}

            </ul>
            {% endif %}
            {% endif %}

        </div>

        <div id="changelog" class="tab-pane fade">
            {% set changelog = update.getChangelog() %}

            {% if changelog is not empty %}
            {{ changelog|raw }}
            {% else %}
            <p>{{ 'Nessuna changelog individuabile'|trans }}.</p>
            {% endif %}

        </div>

        <hr>

        <form action="{{ action_link|replace({'|action|': 'cancel'}) }}" method="post" style="display:inline-block">
            <button type="submit" class="btn btn-warning">
                <i class="fa fa-arrow-left"></i> {{ 'Annulla'|trans }}
            </button>
        </form>

        <form action="{{ action_link|replace({'|action|': 'execute'}) }}" method="post" class="float-right" style="display:inline-block">
            <button type="submit" class="btn btn-success">
                <i class="fa fa-arrow-right"></i> {{ 'Procedi'|trans }}
            </button>
        </form>
    </div>
</div>

<script>
    function backup(){
        swal({
            title: "{{ 'Nuovo backup'|trans }}",
            text: "{{ 'Sei sicuro di voler creare un nuovo backup?'|trans }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn btn-lg btn-success",
            confirmButtonText: "{{ 'Crea'|trans }}",
        }).then(function(){
            $("#main_loading").show();

            $.ajax({
                url: "{{ url_for('module', {'module_id':  module('Backup').id}) }}",
                type: "post",
                data: {
                    op: "backup",
                },
                success: function(data){
                    $("#main_loading").fadeOut();
                }
            });
        }, function(){});
    }
</script>
{% endblock %}
