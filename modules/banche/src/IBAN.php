<?php

namespace Modules\Banche;

use UnexpectedValueException;

/**
 * Format:
 * b = National bank code (Codice ABI)
 * s = Bank/branch code (sort code, or CAB – Codice d'Avviamento Bancario)
 * c = Account number
 * d = Account type
 * i = National identification number
 * k = IBAN check digits (CIN europeo)
 * x = National check digits (CIN nazionale).
 */
class IBAN
{
    public static $countries = [
        'AL' => [
            'length' => 28,
            'pattern' => '8n 16c',
            'structure' => 'ALkk bbbs sssx cccc cccc cccc cccc',
        ],
        'AD' => [
            'length' => 24,
            'pattern' => '8n 12c',
            'structure' => 'ADkk bbbb ssss cccc cccc cccc',
        ],
        'AT' => [
            'length' => 20,
            'pattern' => '16n',
            'structure' => 'ATkk bbbb bccc cccc cccc',
        ],
        'AZ' => [
            'length' => 28,
            'pattern' => '4c 20n',
            'structure' => 'AZkk bbbb cccc cccc cccc cccc cccc',
        ],
        'BH' => [
            'length' => 22,
            'pattern' => '4a 14c',
            'structure' => 'BHkk bbbb cccc cccc cccc cc',
        ],
        'BE' => [
            'length' => 16,
            'pattern' => '12n',
            'structure' => 'BEkk bbbc cccc ccxx',
        ],
        'BA' => [
            'length' => 20,
            'pattern' => '16n',
            'structure' => 'BA39 bbbs sscc cccc ccxx',
        ],
        'BR' => [
            'length' => 29,
            'pattern' => '23n 1a 1c',
            'structure' => 'BR39 bbbb bbbb ssss sccc cccc cccc c',
        ],
        'BG' => [
            'length' => 22,
            'pattern' => '4a 6n 8c',
            'structure' => 'BGkk bbbb ssss ddcc cccc cc',
        ],
        'CR' => [
            'length' => 21,
            'pattern' => '17n',
            'structure' => 'CRkk bbbc cccc cccc cccc c',
        ],
        'HR' => [
            'length' => 21,
            'pattern' => '17n',
            'structure' => 'HRkk bbbb bbbc cccc cccc c',
        ],
        'CY' => [
            'length' => 28,
            'pattern' => '8n 16c',
            'structure' => 'CYkk bbbs ssss cccc cccc cccc cccc',
        ],
        'CZ' => [
            'length' => 24,
            'pattern' => '20n',
            'structure' => 'CZkk bbbb ssss sscc cccc cccc',
        ],
        'DK' => [
            'length' => 18,
            'pattern' => '14n',
            'structure' => 'DKkk bbbb cccc cccc cc',
        ],
        'DO' => [
            'length' => 28,
            'pattern' => '4a 20n',
            'structure' => 'DOkk bbbb cccc cccc cccc cccc cccc',
        ],
        'EE' => [
            'length' => 20,
            'pattern' => '16n',
            'structure' => 'EEkk bbss cccc cccc cccx',
        ],
        'FO' => [
            'length' => 18,
            'pattern' => '14n',
            'structure' => 'FOkk bbbb cccc cccc cx',
        ],
        'FI' => [
            'length' => 18,
            'pattern' => '14n',
            'structure' => 'FIkk bbbb bbcc cccc cx',
        ],
        'FR' => [
            'length' => 27,
            'pattern' => '10n 11c 2n',
            'structure' => 'FRkk bbbb bggg ggcc cccc cccc cxx',
        ],
        'GE' => [
            'length' => 22,
            'pattern' => '2c 16n',
            'structure' => 'GEkk bbcc cccc cccc cccc cc',
        ],
        'DE' => [
            'length' => 22,
            'pattern' => '18n',
            'structure' => 'DEkk bbbb bbbb cccc cccc cc',
        ],
        'GI' => [
            'length' => 23,
            'pattern' => '4a 15c',
            'structure' => 'GIkk bbbb cccc cccc cccc ccc',
        ],
        'GR' => [
            'length' => 27,
            'pattern' => '7n 16c',
            'structure' => 'GRkk bbbs sssc cccc cccc cccc ccc',
        ],
        'GL' => [
            'length' => 18,
            'pattern' => '14n',
            'structure' => 'GLkk bbbb cccc cccc cc',
        ],
        'GT' => [
            'length' => 28,
            'pattern' => '4c 20c',
            'structure' => 'GTkk bbbb cccc cccc cccc cccc cccc',
        ],
        'HU' => [
            'length' => 28,
            'pattern' => '24n',
            'structure' => 'HUkk bbbs sssk cccc cccc cccc cccx',
        ],
        'IS' => [
            'length' => 26,
            'pattern' => '22n',
            'structure' => 'ISkk bbbb sscc cccc iiii iiii ii',
        ],
        'IE' => [
            'length' => 22,
            'pattern' => '4c 14n',
            'structure' => 'IEkk aaaa bbbb bbcc cccc cc',
        ],
        'IL' => [
            'length' => 23,
            'pattern' => '19n',
            'structure' => 'ILkk bbbn nncc cccc cccc ccc',
        ],
        'IT' => [
            'length' => 27,
            'pattern' => '1a 10n 12c',
            'structure' => 'ITkk xbbb bbss sssc cccc cccc ccc',
        ],
        'KZ' => [
            'length' => 20,
            'pattern' => '3n 13c',
            'structure' => 'KZkk bbbc cccc cccc cccc',
        ],
        'KW' => [
            'length' => 30,
            'pattern' => '4a 22c',
            'structure' => 'KWkk bbbb cccc cccc cccc cccc cccc cc',
        ],
        'LV' => [
            'length' => 21,
            'pattern' => '4a 13c',
            'structure' => 'LVkk bbbb cccc cccc cccc c',
        ],
        'LB' => [
            'length' => 28,
            'pattern' => '4n 20c',
            'structure' => 'LBkk bbbb cccc cccc cccc cccc cccc',
        ],
        'LI' => [
            'length' => 21,
            'pattern' => '5n 12c',
            'structure' => 'LIkk bbbb bccc cccc cccc c',
        ],
        'LT' => [
            'length' => 20,
            'pattern' => '16n',
            'structure' => 'LTkk bbbb bccc cccc cccc',
        ],
        'LU' => [
            'length' => 20,
            'pattern' => '3n 13c',
            'structure' => 'LUkk bbbc cccc cccc cccc',
        ],
        'MK' => [
            'length' => 19,
            'pattern' => '3n 10c 2n',
            'structure' => 'MK07 bbbc cccc cccc cxx',
        ],
        'MT' => [
            'length' => 31,
            'pattern' => '4a 5n 18c',
            'structure' => 'MTkk bbbb ssss sccc cccc cccc cccc ccc',
        ],
        'MR' => [
            'length' => 27,
            'pattern' => '23n',
            'structure' => 'MRkk bbbb bsss sscc cccc cccc cxx',
        ],
        'MU' => [
            'length' => 30,
            'pattern' => '4a 19n 3a',
            'structure' => 'MUkk bbbb bbss cccc cccc cccc cccc cc',
        ],
        'MC' => [
            'length' => 27,
            'pattern' => '10n 11c 2n',
            'structure' => 'MCkk bbbb bsss sscc cccc cccc cxx',
        ],
        'MD' => [
            'length' => 24,
            'pattern' => '2c 18n',
            'structure' => 'MDkk bbcc cccc cccc cccc cccc',
        ],
        'ME' => [
            'length' => 22,
            'pattern' => '18n',
            'structure' => 'ME25 bbbc cccc cccc cccc xx',
        ],
        'NL' => [
            'length' => 18,
            'pattern' => '4a 10n',
            'structure' => 'NLkk bbbb cccc cccc cc',
        ],
        'NO' => [
            'length' => 15,
            'pattern' => '11n',
            'structure' => 'NOkk bbbb cccc ccx',
        ],
        'PK' => [
            'length' => 24,
            'pattern' => '4c 16n',
            'structure' => 'PKkk bbbb cccc cccc cccc cccc',
        ],
        'PS' => [
            'length' => 29,
            'pattern' => '4c 21n',
            'structure' => 'PSkk bbbb zzzz zzzz zccc cccc cccc c',
        ],
        'PL' => [
            'length' => 28,
            'pattern' => '24n',
            'structure' => 'PLkk bbbs sssx cccc cccc cccc cccc',
        ],
        'PT' => [
            'length' => 25,
            'pattern' => '21n',
            'structure' => 'PT50 bbbb ssss cccc cccc cccx x',
        ],
        'RO' => [
            'length' => 24,
            'pattern' => '4a 16c',
            'structure' => 'ROkk bbbb cccc cccc cccc cccc',
        ],
        'SM' => [
            'length' => 27,
            'pattern' => '1a 10n 12c',
            'structure' => 'SMkk xbbb bbss sssc cccc cccc ccc',
        ],
        'SA' => [
            'length' => 24,
            'pattern' => '2n 18c',
            'structure' => 'SAkk bbcc cccc cccc cccc cccc',
        ],
        'RS' => [
            'length' => 22,
            'pattern' => '18n',
            'structure' => 'RSkk bbbc cccc cccc cccc aa',
        ],
        'SK' => [
            'length' => 24,
            'pattern' => '20n',
            'structure' => 'SKkk bbbb ssss sscc cccc cccc',
        ],
        'SI' => [
            'length' => 19,
            'pattern' => '15n',
            'structure' => 'SI56 bbss sccc cccc cxx',
        ],
        'ES' => [
            'length' => 24,
            'pattern' => '20n',
            'structure' => 'ESkk bbbb ssss xxcc cccc cccc',
        ],
        'SE' => [
            'length' => 24,
            'pattern' => '20n',
            'structure' => 'SEkk bbbc cccc cccc cccc cccc',
        ],
        'CH' => [
            'length' => 21,
            'pattern' => '5n 12c',
            'structure' => 'CHkk bbbb bccc cccc cccc c',
        ],
        'TN' => [
            'length' => 24,
            'pattern' => '20n',
            'structure' => 'TNkk bbss sccc cccc cccc cccc',
        ],
        'TR' => [
            'length' => 26,
            'pattern' => '5n 17c',
            'structure' => 'TRkk bbbb b0cc cccc cccc cccc cc',
        ],
        'AE' => [
            'length' => 23,
            'pattern' => '3n 16n',
            'structure' => 'AEkk bbbc cccc cccc cccc ccc',
        ],
        'GB' => [
            'length' => 22,
            'pattern' => '4a 14n',
            'structure' => 'GBkk bbbb ssss sscc cccc cc',
        ],
        'VG' => [
            'length' => 24,
            'pattern' => '4c 16n',
            'structure' => 'VGkk bbbb cccc cccc cccc cccc',
        ],
    ];

    protected static $parsers = [
        'b' => 'bank_code',
        's' => 'branch_code',
        'c' => 'account_number',
        'd' => 'account_type',
        'i' => 'id',
        'k' => 'check_digits',
        'x' => 'national_check_digits',
    ];

    /**
     * @var string
     */
    protected $iban;
    /**
     * @var string
     */
    protected $nation;
    /**
     * @var string
     */
    protected $bank_code;
    /**
     * @var string
     */
    protected $branch_code;
    /**
     * @var string
     */
    protected $account_number;
    /**
     * @var string
     */
    protected $account_type;
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $check_digits;
    /**
     * @var string
     */
    protected $national_check_digits;

    public function __construct($iban)
    {
        $iban = str_replace(' ', '', $iban);
        $this->iban = $iban;

        $this->nation = $nation = substr($iban, 0, 2);
        $info = self::$countries[$nation];

        $structure = $info['structure'];
        $structure = str_replace(' ', '', $structure);

        $regex = $nation;
        $keys = array_keys(self::$parsers);

        $length = strlen($this->iban);
        $current = strlen($nation);
        while ($current <= $length) {
            $char = $structure[$current];
            if (in_array($char, $keys)) {
                $count = substr_count($structure, $char);
                $regex .= '(?<'.self::$parsers[$char].'>[A-Z0-9]{'.$count.'})';
                $current += $count;
            } else {
                $regex .= $char;
                ++$current;
            }
        }

        preg_match_all('/^'.$regex.'/', $iban, $matches);
        $matches = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        foreach ($matches as $key => $value) {
            if (!isset($value[0])) {
                throw new UnexpectedValueException('Invalid '.$key.' for format '.$regex);
            }

            $this->{$key} = $value[0];
        }
    }

    public static function generate($contents = [])
    {
        $nation = $contents['nation'];
        $info = self::$countries[$nation];

        $structure = $info['structure'];
        $structure = str_replace(' ', '', $structure);

        $keys = array_keys(self::$parsers);

        $length = strlen($structure);
        $current = strlen($nation);
        $result = $nation;
        while ($current <= $length) {
            $char = $structure[$current];
            if (in_array($char, $keys)) {
                $count = substr_count($structure, $char);
                $result .= str_pad(
                        substr($contents[self::$parsers[$char]], 0, $count),
                    $count, STR_PAD_LEFT);
                $current += $count;
            } else {
                $result .= $char;
                ++$current;
            }
        }

        return new self($result);
    }

    /**
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * @return string
     */
    public function getNation()
    {
        return $this->nation;
    }

    /**
     * @return string
     */
    public function getBankCode()
    {
        return $this->bank_code;
    }

    /**
     * @return string
     */
    public function getBranchCode()
    {
        return $this->branch_code;
    }

    /**
     * @return string
     */
    public function getAccountNumber()
    {
        return $this->account_number;
    }

    /**
     * @return string
     */
    public function getAccountType()
    {
        return $this->account_type;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCheckDigits()
    {
        return $this->check_digits;
    }

    /**
     * @return string
     */
    public function getNationalCheckDigits()
    {
        return $this->national_check_digits;
    }
}
