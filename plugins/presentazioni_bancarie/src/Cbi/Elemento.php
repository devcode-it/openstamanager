<?php

namespace Plugins\PresentazioniBancarie\Cbi;

abstract class Elemento
{
    protected $dati = [];

    public function __get($name)
    {
        $method = $this->getCamelCase($name);
        if (method_exists($this, 'get'.$method)) {
            return $this->{'get'.$name}();
        }

        return $this->{$name};
    }

    public function __set($name, $value)
    {
        $method = $this->getCamelCase($name);
        if (method_exists($this, 'set'.$method)) {
            $this->{'set'.$name}($value);
        } else {
            $this->{$name} = $value;
        }
    }

    /**
     * @return array
     */
    abstract public function toCbiFormat();

    /**
     * @param string $string
     *
     * @return string
     */
    protected function getCamelCase($string)
    {
        $words = str_replace('_', ' ', $string);
        $upper = ucwords($words);
        $name = str_replace(' ', '', $upper);

        return $name;
    }
}
