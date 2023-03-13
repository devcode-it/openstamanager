<?php

namespace Plugins\PresentazioniBancarie\Cbi\Records;

abstract class BaseRecord implements RecordInterface
{
    protected $dati = [];

    /**
     * Costruttore predefinito, che inizializza le informazioni interne al record in modo autonomo secondo la relativa struttura.
     */
    public function __construct()
    {
        $struttura = static::getStruttura();

        // Inizializzazione di tutti i campi
        foreach ($struttura as $nome => $campo) {
            $this->{$nome} = $campo['valore'] ?: '';
        }
    }

    public function __get($name)
    {
        $method = $this->getCamelCase($name);
        if (method_exists($this, 'get'.$method)) {
            return $this->{'get'.$name}();
        }

        return $this->get($name);
    }

    public function __set($name, $value)
    {
        $method = $this->getCamelCase($name);
        if (method_exists($this, 'set'.$method)) {
            $this->{'set'.$name}($value);
        } else {
            $this->set($name, $value);
        }
    }

    public function toCBI(): string
    {
        $contenuto = ' '.static::getCodice();
        $struttura = static::getStruttura();

        // Informazioni sui campi disponibili
        $contenuti = [];
        foreach ($struttura as $nome => $campo) {
            $contenuti[$campo['inizio']] = $this->{$nome};
        }

        // Sort per indici
        ksort($contenuti);

        // Completamento dei filler
        foreach ($contenuti as $inizio => $string) {
            $dimensione_contenuto = strlen($contenuto) + 1;

            if ($dimensione_contenuto != $inizio) {
                $contenuto .= str_repeat(' ', $inizio - $dimensione_contenuto);
            }

            $contenuto .= $string;
        }

        // Filler finale per la riga
        $contenuto .= str_repeat(' ', 120 - strlen($contenuto));

        return $contenuto;
    }

    public function fromCBI(string $contenuto): void
    {
        $struttura = static::getStruttura();

        // Informazioni sui campi disponibili
        foreach ($struttura as $nome => $campo) {
            $string = substr($contenuto, $campo['inizio'] - 1, $campo['dimensione']);

            // Aggiunta del contenuto al record
            $this->{$nome} = trim($string);
        }
    }

    public function get(string $name): ?string
    {
        return isset($this->dati[$name]) ? $this->dati[$name] : null;
    }

    public function set(string $name, ?string $value): void
    {
        $struttura = static::getStruttura();
        $record = $struttura[$name];

        if (empty($record)) {
            return;
        }

        // Pad automatico sulla base del tipo
        if ($record['tipo'] == 'string') {
            $value = $this->padString($value, $record['dimensione'], isset($record['forzaPadding']) ? $record['forzaPadding'] : "" );
        } elseif ($record['tipo'] == 'numeric') {
            $value = $this->padNumber($value, $record['dimensione']);
        } elseif ($record['tipo'] == 'constant') {
            $value = $record['valore'];
        }

        $this->dati[$name] = $value;
    }

    /**
     * @return string
     */
    protected function padString(?string $string, int $length, $pad = STR_PAD_RIGHT)
    {
        // Sostituzione di alcuni simboli noti
        $replaces = [
            '&#039;' => "'",
            '&quot;' => "'",
            '&amp;' => '&',
        ];
        $string = str_replace(array_keys($replaces), array_values($replaces), $string);

        $string = substr($string, 0, $length);

        if ( $pad == STR_PAD_LEFT || $pad == STR_PAD_RIGHT ) {
			return str_pad($string, $length, " ", $pad);
		}

		return str_pad($string, $length, " ");
    }

    /**
     * @return string
     */
    protected function padNumber(?string $string, int $length)
    {
        $string = substr($string, 0, $length);

        return str_pad($string, $length, '0', STR_PAD_LEFT);
    }

    /**
     * @return string
     */
    protected function getCamelCase(string $string)
    {
        $words = str_replace('_', ' ', $string);
        $upper = ucwords($words);
        $name = str_replace(' ', '', $upper);

        return $name;
    }
}
