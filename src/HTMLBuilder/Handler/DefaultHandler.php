<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace HTMLBuilder\Handler;

/**
 * Gestione dell'input di tipo "text", "file", "password", "email", "number", "textarea" e "hidden".
 *
 * @since 2.3
 */
class DefaultHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        $values['class'][] = 'openstamanager-input';

        // Delega della gestione al metodo specifico per il tipo di input richiesto
        if (in_array($values['type'], get_class_methods($this))) {
            $result = $this->{$values['type']}($values, $extras);
        }

        // Caso non previsto
        else {
            $result = $this->custom($values, $extras);
        }

        return $result;
    }

    /**
     * Gestione dell'input di tipo non altrimenti previsto.
     * Esempio: {[ "type": "undefined", "label": "Custom di test", "placeholder": "Test", "name": "custom", "value": "custom" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function custom(&$values, &$extras)
    {
        // Generazione del codice HTML
        return '
    <span |attr|>|value|</span>';
    }

    /**
     * Gestione dell'input di tipo "text".
     * Esempio: {[ "type": "text", "label": "Text di test", "placeholder": "Test", "name": "text" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function text(&$values, &$extras)
    {
        // Generazione del codice HTML
        return '
    <input |attr| autocomplete="off">';
    }

    /**
     * Gestione dell'input di tipo "file".
     * Esempio: {[ "type": "file", "label": "File di test", "placeholder": "Test", "name": "file" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function file(&$values, &$extras)
    {
        // Delega al metodo "text", per la generazione del codice HTML
        return $this->text($values, $extras);
    }

    /**
     * Gestione dell'input di tipo "password".
     * Esempio: {[ "type": "password", "label": "Password di test", "placeholder": "Test", "name": "password" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function password(&$values, &$extras)
    {
        $values['icon-after'] = '<i onclick="togglePassword_'.$values['id'].'()" class="clickable fa" id="'.$values['id'].'_toggle"></i>';

        $result = '
    <script>
        function togglePassword_'.$values['id'].'() {
            var button = $("#'.$values['id'].'_toggle");

            if (button.hasClass("fa-eye")) {
                $("#'.$values['id'].'").attr("type", "text");
                button.removeClass("fa-eye").addClass("fa-eye-slash");
                button.attr("title", "'.tr('Nascondi password').'");
            }
            else {
                $("#'.$values['id'].'").attr("type", "password");
                button.removeClass("fa-eye-slash").addClass("fa-eye");
                button.attr("title", "'.tr('Visualizza password').'");
            }
        }

        $(document).ready(function(){
            togglePassword_'.$values['id'].'();
        });
    </script>';

        if (!empty($values['strength'])) {
            $result .= '
    <div id="'.$values['id'].'_viewport_progress"></div>

    <script src="'.base_path().'/assets/dist/password-strength/password.min.js"></script>
       <script>
        $(document).ready(function(){
            $("#'.$values['id'].'").pwstrength({
                ui: {
                    bootstrap3: true,
                    showVerdictsInsideProgressBar: true,
                    viewports: {
                        progress: "#'.$values['id'].'_viewport_progress",
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
                        var len = $("#'.$values['id'].'").val().length;

                        if(len < 6) {
                            $("'.$values['strength'].'").attr("disabled", true).addClass("disabled");
                        } else {
                            $("'.$values['strength'].'").attr("disabled", false).removeClass("disabled");
                        }
                    }
                },
            });

            $("#'.$values['id'].'_viewport_progress").insertAfter($("#'.$values['id'].'").closest(".form-group").find("div[id$=-errors]")).css("margin-top", "5px");
        });
    </script>';
        }

        // Delega al metodo "text", per la generazione del codice HTML
        $result .= $this->text($values, $extras);

        return $result;
    }

    /**
     * Gestione dell'input di tipo "hidden".
     * Esempio: {[ "type": "hidden", "label": "Hidden di test", "placeholder": "Test", "name": "hidden" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function hidden(&$values, &$extras)
    {
        $original = $values;

        $values = [];
        $values['type'] = $original['type'];
        $values['value'] = $original['value'];
        $values['name'] = $original['name'];
        $values['id'] = $original['id'];
        $values['class'] = [];

        // Delega al metodo "text", per la generazione del codice HTML
        return $this->text($values, $extras);
    }

    /**
     * Gestione dell'input di tipo "email".
     * Esempio: {[ "type": "email", "label": "Email di test", "placeholder": "Test", "name": "email" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function email(&$values, &$extras)
    {
        $values['class'][] = 'email-mask';

        $values['type'] = 'text';

        // Delega al metodo "text", per la generazione del codice HTML
        return $this->text($values, $extras);
    }

    /**
     * Gestione dell'input di tipo "number".
     * Esempio: {[ "type": "number", "label": "Number di test", "placeholder": "Test", "name": "number" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function number(&$values, &$extras)
    {
        $values['class'][] = 'number-input';

        $values['value'] = !empty($values['value']) ? $values['value'] : 0;

        // Gestione della precisione (numero specifico, oppure "qta" per il valore previsto nell'impostazione "Cifre decimali per quantità").
        $decimals = null;
        if (isset($values['decimals'])) {
            if (is_numeric($values['decimals'])) {
                $decimals = $values['decimals'];
            } elseif (string_starts_with($values['decimals'], 'qta')) {
                $decimals = setting('Cifre decimali per quantità');
                $values['decimals'] = $decimals;

                // Se non è previsto un valore minimo, lo imposta a 1
                $values['min-value'] = isset($values['min-value']) ? $values['min-value'] : '0.'.str_repeat('0', $decimals - 1).'1';
            }
        }

        // Delega al metodo "text", per la generazione del codice HTML
        $values['type'] = 'text';

        return $this->text($values, $extras);
    }

    /**
     * Gestione dell'input di tipo "textarea".
     * Esempio: {[ "type": "textarea", "label": "Textarea di test", "placeholder": "Test", "name": "textarea" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function textarea(&$values, &$extras)
    {
        $values['class'][] = 'autosize';

        // Generazione del codice HTML
        return '
    <textarea |attr|>|value|</textarea>';
    }
}
