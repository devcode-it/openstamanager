@php
    $strength = $attributes->has('strength-trigger');
@endphp

<x-input-wrapper :name="$name" :id="$id" :unique_id="$unique_id" :label="$label">
    @include('components.inputs.standard-input')

    <x-slot name="after">
        <span class="input-group-addon after">
            <i onclick="togglePassword_{{ $id }}()" class="clickable fa" id="{{ $id }}_toggle"></i>
        </span>
    </x-slot>

    <x-slot name="before">{{ isset($before) ? $before : null }}</x-slot>
</x-input-wrapper>

<script>
    function togglePassword_{{ $id }}() {
        var button = $("#{{ $id }}_toggle");

        if (button.hasClass("fa-eye")) {
            $("#{{ $id }}").attr("type", "text");
            button.removeClass("fa-eye").addClass("fa-eye-slash");
            button.attr("title", "'.tr('Nascondi password').'");
        } else {
            $("#{{ $id }}").attr("type", "password");
            button.removeClass("fa-eye-slash").addClass("fa-eye");
            button.attr("title", "'.tr('Visualizza password').'");
        }
    }

    $(document).ready(function(){
        togglePassword_{{ $id }}();
    });
</script>

@if($attributes->has('strength-trigger'))
    <div id="{{ $id }}_viewport_progress"></div>

    <script src="{{ url('/') }}/assets/dist/password-strength/password.min.js"></script>
    <script>
        $(document).ready(function(){
            $("#{{ $id }}").pwstrength({
                ui: {
                    bootstrap3: true,
                    showVerdictsInsideProgressBar: true,
                    viewports: {
                        progress: "#{{ $id }}_viewport_progress",
                    },
                    progressBarExtraCssClasses: "progress-bar-striped active",
                    showPopover: true,
                    showProgressBar: false,
                    popoverPlacement: "top",
                    showStatus: true,
                    showErrors: true,
                    showVerdicts: true,
                    useVerdictCssClass: false,
                    showScore: false,
                    progressBarMinWidth: 50,
                    colorClasses: ["danger", "danger", "warning", "warning", "success", "success"],
                },
                i18n: {
                    t: function (key) {
                        var result = globals.translations.password[key];

                        return result === key ? \'\' : result;
                    }
                },
                common: {
                    minChar: 6,
                    onKeyUp: function(event, data) {
                        var len = $("#{{ $id }}").val().length;

                        if(len < 6) {
                            $("{{ $attributes->get('strength-trigger') }}").attr("disabled", true).addClass("disabled");
                        } else {
                            $("{{ $attributes->get('strength-trigger') }}").attr("disabled", false).removeClass("disabled");
                        }
                    }
                },
            });

            $("#{{ $id }}_viewport_progress").insertAfter($("#{{ $id }}").closest(".form-group").find("div[id$=-errors]")).css("margin-top", "5px");
        });
    </script>
@endif
