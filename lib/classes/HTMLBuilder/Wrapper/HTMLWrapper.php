<?php

namespace HTMLBuilder\Wrapper;

// Utilizzo della funzione prepareToField (PHP 5.6+)
// use function \HTMLBuilder\prepareToField;

/**
 * @since 2.3
 */
class HTMLWrapper implements WrapperInterface
{
    public function before(&$values, &$extras)
    {
        $result = '';

        // Valori particolari
        $values['icon-before'] = $this->parser($values, $values['icon-before']);
        $values['icon-after'] = $this->parser($values, $values['icon-after']);

        // Generazione dell'etichetta
        if (!empty($values['label'])) {
            $result .= '
<div class="form-group">
    <label for="'.\HTMLBuilder\prepareToField($values['id']).'">'.(empty($values['help']) ? $values['label'] : '<span class="tip" title="'.\HTMLBuilder\prepareToField($values['help']).'">'.$values['label'].'</span>').'</label>';
        }

        if (!empty($values['icon-before']) || !empty($values['icon-after'])) {
            $result .= '
    <div class="input-group">';

            if (!empty($values['icon-before'])) {
                $result .= '
        <span class="input-group-addon'.(!empty($values['icon-custom']) ? ' '.$values['icon-custom'] : '').'">'.$values['icon-before'].'</span>';
            }
        }

        return $result;
    }

    public function after(&$values, &$extras)
    {
        $result = '';

        if (!empty($values['icon-before']) || !empty($values['icon-after'])) {
            if (!empty($values['icon-after'])) {
                $result .= '
                <span class="input-group-addon'.(!empty($values['icon-custom']) ? ' '.$values['icon-custom'] : '').'">'.$values['icon-after'].'</span>';
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

        $rand = rand(0, 99);

        $values['data-parsley-errors-container'] = '#'.$values['id'].$rand.'-errors';

        $result .= '
        <div id="'.$values['id'].$rand.'-errors"></div>';

        if (!empty($values['label'])) {
            $result .= '
        </div>';
            unset($values['label']);
        }

        return $result;
    }

    protected function parser(&$values, $string)
    {
        $result = $string;

        if (starts_with($string, 'add|')) {
            $result = $this->add($values, $string);
            $values['icon-custom'] = 'no-padding';
        } elseif (starts_with($string, 'choice|')) {
            $result = $this->choice($values, $string);
            $values['icon-custom'] = 'no-padding';
        }

        return $result;
    }

    protected function add(&$values, $string)
    {
        $result = null;

        $pieces = explode('|', $string);

        $id_module = $pieces[1];
        $extra = empty($pieces[2]) ? '' : '&'.$pieces[2];

        $classes = empty($pieces[3]) ? '' : ' '.$pieces[3];

        $module = \Modules::getModule($id_module);
        if (in_array($module['permessi'], ['r', 'rw'])) {
            $result = '
<button data-href="'.ROOTDIR.'/add.php?id_module='.$id_module.$extra.'&select='.$values['id'].'&ajax=yes" data-target="#bs-popup2" data-toggle="modal" data-title="'._('Aggiungi').'" type="button" class="btn'.$classes.'">
    <i class="fa fa-plus"></i>
</button>';
        }

        return $result;
    }

    protected function choice(&$values, $string)
    {
        $result = null;

        $choices = [
            [
                'id' => 'UNT',
                'descrizione' => _('&euro;'),
            ],
            [
                'id' => 'PRC',
                'descrizione' => '%',
            ],
        ];

        $pieces = explode('|', $string);

        $type = $pieces[1];
        $value = (empty($pieces[2]) || !in_array($pieces[2], array_column($choices, 'id'))) ? 'UNT' : $pieces[2];

        if ($type == 'untprc') {
            $result = '{[ "type": "select", "name": "tipo_'.\HTMLBuilder\prepareToField($values['name']).'", "value": "'.\HTMLBuilder\prepareToField($value).'", "values": "json='.substr(str_replace('"', '\"', json_encode($choices)), 2, -2).'", "class": "no-search" ]}';

            $result = \HTMLBuilder\HTMLBuilder::replace($result);
        }

        return $result;
    }
}
