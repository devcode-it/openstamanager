<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

namespace HTMLBuilder\Wrapper;

use Modules;

/**
 * @since 2.3
 */
class HTMLWrapper implements WrapperInterface
{
    public function before(&$values, &$extras)
    {
        $result = '';

        // Valori particolari
        $values['icon-before'] = isset($values['icon-before']) ? $this->parser($values, $extras, $values['icon-before']) : null;
        $values['icon-after'] = isset($values['icon-after']) ? $this->parser($values, $extras, $values['icon-after']) : null;

        // Generazione dell'etichetta
        if (!empty($values['label'])) {
            $result .= '
<div class="form-group">
    <label for="'.prepareToField($values['id']).'">'.(empty($values['help']) ? $values['label'] : '<span class="tip" title="'.prepareToField($values['help']).'">'.$values['label'].' <i class="fa fa-question-circle-o"></i></span>').'</label>';
        }

        if (!empty($values['icon-before']) || !empty($values['icon-after']) || !empty($values['validation'])) {
            $result .= '
    <div class="input-group has-feedback">';

            if (!empty($values['icon-before'])) {
                $result .= '
        <span class="input-group-addon before'.(!empty($values['icon-custom']) ? ' '.$values['icon-custom'] : '').'">'.$values['icon-before'].'</span>';
            }
        }

        return $result;
    }

    public function after(&$values, &$extras)
    {
        $rand = rand(0, 99);
        $pseudo_id = $values['id'].$rand;

        $result = '';

        if (!empty($values['icon-before']) || !empty($values['icon-after']) || !empty($values['validation'])) {
            if (!empty($values['icon-after'])) {
                $result .= '
                <span class="input-group-addon after'.(!empty($values['icon-custom']) ? ' '.$values['icon-custom'] : '').'">'.$values['icon-after'].'</span>';
            }

            if (!empty($values['validation'])) {
                $result .= '
                <span class="input-group-addon after" id="'.$pseudo_id.'_validation">
                    <span class="tip" title="'.tr('Validazione').'"><i class="fa fa-question-circle "></i></span>
                </span>';
            }

            $result .= '
            </div>';

            unset($values['icon-before']);
            unset($values['icon-after']);
            unset($values['icon-custom']);
        }

        if (!empty($values['help']) && !empty($values['show-help'])) {
            $result .= '
        <span class="help-block pull-left"><small>'.$values['help'].'</small></span>';

            unset($values['help']);
            unset($values['show-help']);
        }

        $values['data-parsley-errors-container'] = '#'.$pseudo_id.'-errors';

        $result .= '
        <div id="'.$values['id'].$rand.'-errors"></div>';

        if (!empty($values['label'])) {
            $result .= '
        </div>';
            unset($values['label']);
        }

        if (!empty($values['validation'])) {
            $values['valid'] = '1';

            $value = explode('|', $values['validation']);
            $name = $value[0];
            $id_module = $value[1] ?: '$id_module$';
            $id_record = $value[2] ?: '$id_record$';

            $result .= '
    <script>
        var container = $("#'.$pseudo_id.'_validation");

        container.closest(".input-group").find("input").on("change, blur", function(e){
            var input = $(this);
            var value = input.val();

            var container = $("#'.$pseudo_id.'_validation");
            var parent = container.closest(".input-group");
            var message = container.find("span");
            var icon = container.find("i");

            icon.attr("class", "fa fa-spinner fa-spin");

            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "post",
                data: {
                    id_module: "'.$id_module.'",
                    id_record: "'.$id_record.'",
                    name: "'.$name.'",
                    value: value,
                    op: "validate",
                },
                success: function(data) {
                    data = JSON.parse(data);

                    if (value == "") {
                        parent.removeClass("has-success").removeClass("has-error");
                        icon.attr("class", "fa fa-question-circle");
                        message.tooltipster("content", "'.tr('Validazione').'");
                    } else {
                        if(data.result) {
                            icon.attr("class", "fa fa-check");
                            parent.addClass("has-success").removeClass("has-error");
                        } else {
                            icon.attr("class", "fa fa-close");
                            parent.addClass("has-error").removeClass("has-success");
                        }

                        message.tooltipster("content", data.message);
                        input.attr("valid", +(data.result));

                        if (data.fields) {
                            var fields = data.fields;

                            var form = input.closest("form");
                            Object.keys(fields).forEach(function(element) {
                                var single_input = form.find("[name=" + element + "]");
                                if (!single_input.val()) single_input.val(fields[element]).trigger("change");
                            });
                        }

                    }
                }
            });
        });
    </script>';
        }

        return $result;
    }

    protected function parser(&$values, &$extras, $string)
    {
        $result = $string;

        if (starts_with($string, 'add|')) {
            $result = $this->add($values, $extras, $string);
            $values['icon-custom'] = 'no-padding';
        } elseif (starts_with($string, 'choice|')) {
            $result = $this->choice($values, $extras, $string);
            $values['icon-custom'] = 'no-padding';
        }

        if (str_contains($string, '<button')) {
            $values['icon-custom'] = 'no-padding';
        }

        return $result;
    }

    protected function add(&$values, &$extras, $string)
    {
        $result = null;

        $pieces = explode('|', $string);

        $module_id = $pieces[1];
        $module = Modules::get($module_id);

        $get = !empty($pieces[2]) ? '&'.$pieces[2] : null;
        $classes = !empty($pieces[3]) ? ' '.$pieces[3] : null;
        $btn_extras = !empty($pieces[4]) ? ' '.$pieces[4] : null;

        if (in_array('disabled', $extras)) {
            $classes .= ' disabled';
            $btn_extras .= ' disabled';
        }

        if (in_array($module->permission, ['r', 'rw'])) {
            $result = '
<button type="button" class="btn'.$classes.'" '.$btn_extras.' onclick="openModal(\''.tr('Aggiungi').'\', \''.ROOTDIR.'/add.php?id_module='.$module->id.$get.'&select='.$values['id'].'&ajax=yes\')">
    <i class="fa fa-plus"></i>
</button>';
        }

        return $result;
    }

    protected function choice(&$values, &$extras, $string)
    {
        $result = null;

        $pieces = explode('|', $string);
        $type = $pieces[1];
        $extra = !empty($pieces[3]) ? $pieces[3] : null;

        if ($type == 'untprc') {
            $choices = [
                [
                    'id' => 'PRC',
                    'descrizione' => '%',
                ],
                [
                    'id' => 'UNT',
                    'descrizione' => currency(),
                ],
            ];
        } elseif ($type == 'email') {
            $choices = [
                [
                    'id' => 'a',
                    'descrizione' => tr('A').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                ],
                [
                    'id' => 'cc',
                    'descrizione' => tr('CC').'&nbsp;&nbsp;',
                ],
                [
                    'id' => 'bcc',
                    'descrizione' => tr('CCN'),
                ],
            ];
        } elseif ($type == 'period') {
            $choices = [
                [
                    'id' => 'manual',
                    'descrizione' => tr('giorno/i (manuale)'),
                ],
                [
                    'id' => 'days',
                    'descrizione' => tr('giorno/i'),
                ],
                [
                    'id' => 'months',
                    'descrizione' => tr('mese/i'),
                ],
                [
                    'id' => 'years',
                    'descrizione' => tr('anno/i'),
                ],
            ];
        }

        $value = (empty($pieces[2]) || !in_array($pieces[2], array_column($choices, 'id'))) ? $choices[0]['id'] : $pieces[2];

        $result = '{[ "type": "select", "name": "tipo_'.prepareToField($values['name']).'", "value": "'.prepareToField($value).'", "values": '.json_encode($choices).', "class": "no-search", "extra": "'.$extra.'" ]}';

        $result = \HTMLBuilder\HTMLBuilder::replace($result);

        return $result;
    }
}
