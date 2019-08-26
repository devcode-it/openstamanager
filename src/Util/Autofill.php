<?php

namespace Util;

/**
 * Classe dedicata alla gestione delle righe fantasma per il miglioramento grafico delle stampe tabellari.
 *
 * @since 2.3
 */
class Autofill
{
    protected $space = 0;
    protected $current = 0;

    protected $char_number;
    protected $column_number;

    protected $max_rows = 20;
    protected $max_rows_first_page = 20;
    protected $max_additional = 15;

    public function __construct($column_number, $char_number = 70)
    {
        $this->column_number = $column_number;
        $this->char_number = $char_number;
    }

    public function setRows($rows, $additional = null, $first_page = null)
    {
        $this->max_rows = $rows;

        $this->max_additional = isset($additional) ? $additional : floor($this->max_rows - $this->max_rows / 4);
        $this->max_rows_first_page = isset($first_page) ? $first_page : $rows;
    }

    public function count($text, $small = false)
    {
        $count = ceil(strlen($text) / $this->char_number);
        $count += substr_count($text, PHP_EOL);
        $count += substr_count($text, '<br>');

        if ($small) {
            $count = $count / 3;
        }

        $this->set($count);
    }

    public function set($count)
    {
        if ($count > $this->current) {
            $this->current = $count;
        }
    }

    public function next()
    {
        $this->space += $this->current;
        $this->current = 0;
    }

    public function getAdditionalNumber()
    {
        $page = ceil($this->space / $this->max_rows_first_page);
        if ($page > 1) {
            $rows = floor($this->space) % $this->max_rows;
        } else {
            $rows = floor($this->space) % $this->max_rows_first_page;
        }

        $number = $this->max_additional - $rows;

        return $number > 0 ? $number : 0;
    }

    public function generate()
    {
        $this->next();

        $result = '';

        $number = $this->getAdditionalNumber();

        for ($i = 0; $i < $number; ++$i) {
            $result .= '
                    <tr>';

            for ($c = 0; $c < $this->column_number; ++$c) {
                $result .= '
                        <td>&nbsp;</td>';
            }

            $result .= '
                    </tr>';
        }

        return $result;
    }
}
