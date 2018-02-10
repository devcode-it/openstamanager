<?php

namespace HTMLBuilder\Manager;

/**
 * @since 2.4
 */
class FieldManager implements ManagerInterface
{
    public function manage($options)
    {
        $info = $this->getInfo($options);

        return $this->generate($info);
    }

    public function getInfo($options)
    {
        $database = \Database::getConnection();

        $query = 'SELECT `zz_fields`.*'.(isset($options['id_record']) ? ', `zz_field_record`.`value`' : '').' FROM `zz_fields`';

        if (isset($options['id_record'])) {
            $query .= ' LEFT JOIN `zz_field_record` ON `zz_fields`.`id` = `zz_field_record`.`id_field`';
        }

        $query .= ' WHERE `id_module` = '.prepare($options['id_module']);

        if (isset($options['id_record'])) {
            $query .= ' AND `id_record` = '.prepare($options['id_record']);
        }

        $query .= ' AND `top` = '.((isset($options['position']) && $options['position'] == 'top') ? 1 : 0).' ORDER BY `order`';

        $results = $database->fetchArray($query);

        return $results;
    }

    public function generate($fields)
    {
        // Spazio per evitare problemi con la sostituzione del tag
        $result = ' ';

        // Costruzione dei campi
        foreach ($fields as $key => $field) {
            if ($key % 3 == 0) {
                $result .= '
<div class="row">';
            }

            $field['value'] = isset($field['value']) ? $field['value'] : '';

            $result .= '
    <div class="col-xs-4">
        '.str_replace('|value|', $field['value'], $field['content']).'
    </div>';

            if (($key + 1) % 3 == 0) {
                $result .= '
</div>';
            }
        }

        if (!empty($fields) && ($key + 1) % 3 != 0) {
            $result .= '
</div>';
        }

        return $result;
    }
}
