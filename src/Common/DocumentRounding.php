<?php

namespace Common;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

class DocumentRounding
{
    public const SETTING = 'Modalità predefinita arrotondamento documenti di vendita';
    public const DESCRIPTION = 'Arrotondamento';
    public const INPUT_DECIMALS = 6;
    public const DISPLAY_DECIMALS = 3;

    public static function defaultMode(): ?string
    {
        return match ((string) setting(self::SETTING)) {
            'Disabilitato' => null,
            'Euro inferiore' => 'down',
            'Euro superiore' => 'up',
            'Euro più vicino' => 'nearest',
            default => null,
        };
    }

    public static function target(float $total, string $mode): float
    {
        if (!is_finite($total) || $total < 0) {
            throw new InvalidArgumentException('Totale non valido.');
        }

        $mills = (int) round($total * 1000, 0, PHP_ROUND_HALF_UP);
        $euros = intdiv($mills, 1000);
        $remainder = $mills % 1000;

        $targetMills = match ($mode) {
            'down' => $euros * 1000,
            'up' => ($remainder === 0 ? $euros : $euros + 1) * 1000,
            'nearest' => ($remainder < 500 ? $euros : $euros + 1) * 1000,
            default => throw new InvalidArgumentException('Modalità non valida.'),
        };

        return $targetMills / 1000;
    }

    public static function context($document, ?int $fallbackVatId = null): array
    {
        $managed = [];
        $needsRevalidation = false;

        foreach ($document->sconti()->get() as $discount) {
            $isManaged = self::isManaged($discount);
            $isImported = !empty($discount->original_type);

            if (!$isManaged && $isImported && method_exists($discount, 'getOriginalComponent')) {
                try {
                    $original = $discount->getOriginalComponent();
                    $isManaged = $original && self::isManaged($original);
                } catch (Throwable) {
                    $isManaged = false;
                }
            }

            if ($isManaged) {
                $managed[] = $discount;
                $needsRevalidation = $needsRevalidation || $isImported;
            }
        }

        $existing = count($managed) === 1 ? $managed[0] : null;
        $vatIds = [];
        $rawTaxable = 0.0;
        $rawVat = 0.0;
        $rawSocial = 0.0;

        foreach ($document->getRighe() as $row) {
            if ($existing && get_class($row) === get_class($existing) && (int) $row->id === (int) $existing->id) {
                continue;
            }
            if (method_exists($row, 'isDescrizione') && $row->isDescrizione()) {
                continue;
            }

            $rawTaxable += (float) ($row->totale_imponibile ?? 0);
            $rawVat += (float) ($row->iva ?? 0) + (float) ($row->iva_rivalsa_inps ?? 0);
            $rawSocial += (float) ($row->rivalsa_inps ?? 0);

            $idVat = (int) ($row->id_iva ?? 0);
            if ($idVat > 0) {
                $vatIds[$idVat] = $idVat;
            }
        }

        $vatIds = array_values($vatIds);
        $sourceTotal = round($rawTaxable + $rawVat + $rawSocial, self::DISPLAY_DECIMALS, PHP_ROUND_HALF_UP);
        $baseTotal = self::accountingTotal($rawTaxable, $rawVat, $rawSocial);

        if (count($vatIds) > 1) {
            $existingVat = $existing ? (int) $existing->id_iva : 0;
            $defaultVat = $existing && !$needsRevalidation && in_array($existingVat, $vatIds, true) ? $existingVat : null;
        } elseif (count($vatIds) === 1) {
            $defaultVat = (int) $vatIds[0];
        } else {
            $defaultVat = $existing && !$needsRevalidation ? (int) $existing->id_iva : $fallbackVatId;
        }

        if (!$vatIds && $defaultVat) {
            $vatIds[] = (int) $defaultVat;
        }

        return [
            'existing' => $existing,
            'duplicate_count' => count($managed),
            'source_total' => $sourceTotal,
            'base_total' => $baseTotal,
            'raw_taxable' => $rawTaxable,
            'raw_vat' => $rawVat,
            'raw_social_security' => $rawSocial,
            'vat_ids' => $vatIds,
            'default_vat_id' => $defaultVat,
        ];
    }

    public static function solve(array $context, float $vatPercent, bool $pricesIncludeVat, string $mode): array
    {
        $source = (float) $context['source_total'];
        $target = self::target($source, $mode);
        $difference = round($target - $source, self::DISPLAY_DECIMALS, PHP_ROUND_HALF_UP);
        $base = (float) $context['base_total'];

        if (abs($difference) < 0.0005 && abs($base - $target) < 0.0000001) {
            return ['target' => $target, 'difference' => 0.0, 'input' => 0.0, 'adjustment' => 0.0, 'requires_adjustment' => false];
        }

        $startMicros = (int) round(-($target - $source) * 1000000, 0, PHP_ROUND_HALF_UP);
        for ($offset = 0; $offset <= 20000; ++$offset) {
            $candidates = $offset === 0 ? [$startMicros] : [$startMicros + $offset, $startMicros - $offset];
            foreach ($candidates as $candidateMicros) {
                $effects = self::storedEffects($candidateMicros / 1000000, $vatPercent, $pricesIncludeVat);
                $predicted = self::accountingTotal(
                    (float) $context['raw_taxable'] - $effects['net'],
                    (float) $context['raw_vat'] - $effects['vat'],
                    (float) $context['raw_social_security']
                );

                if (abs($predicted - $target) < 0.0000001) {
                    $adjustment = round(-$effects['gross'], self::INPUT_DECIMALS, PHP_ROUND_HALF_UP);
                    return [
                        'target' => $target,
                        'difference' => $difference,
                        'input' => $effects['input'],
                        'adjustment' => $adjustment,
                        'requires_adjustment' => abs($adjustment) >= 0.0000005,
                    ];
                }
            }
        }

        throw new RuntimeException('Impossibile determinare una rettifica coerente.');
    }

    public static function invoiceGuard($invoice): array
    {
        $reasons = [];
        if (!empty($invoice->tipo?->reversed)) {
            $reasons[] = 'credit_note';
        }
        if (in_array((string) ($invoice->codice_stato_fe ?? ''), ['WAIT', 'RC', 'MC', 'QUEUE', 'DT', 'EC01', 'NE'], true)) {
            $reasons[] = 'electronic_invoice_locked';
        }
        if (abs((float) ($invoice->pagamento?->importo_percentuale_incasso ?? 0)) > 0.0000001) {
            $reasons[] = 'percentage_collection_fee';
        }
        if (abs((float) ($invoice->sconto_finale ?? 0)) > 0.0000001 || abs((float) ($invoice->sconto_finale_percentuale ?? 0)) > 0.0000001) {
            $reasons[] = 'final_discount';
        }
        if (abs((float) ($invoice->rivalsa_inps ?? 0)) > 0.0000001
            || abs((float) ($invoice->ritenuta_acconto ?? 0)) > 0.0000001
            || abs((float) ($invoice->totale_ritenuta_contributi ?? 0)) > 0.0000001
            || !empty($invoice->id_ritenuta_contributi)) {
            $reasons[] = 'withholdings_or_social_security';
        }

        return array_values(array_unique($reasons));
    }

    public static function stampDutyTransition($invoice, int $vatId, array $plan): bool
    {
        if (empty($invoice->addebita_bollo) || isset($invoice->bollo)) {
            return false;
        }

        $stampNatureCodes = ['N2.1', 'N2.2', 'N3.5', 'N3.6', 'N4'];
        $stampTaxable = 0.0;
        $context = self::context($invoice);
        $existing = $context['existing'];

        foreach ($invoice->getRighe() as $row) {
            if ($existing && get_class($row) === get_class($existing) && (int) $row->id === (int) $existing->id) {
                continue;
            }
            $nature = (string) ($row->aliquota->codice_natura_fe ?? '');
            if (in_array($nature, $stampNatureCodes, true)) {
                $stampTaxable += (float) ($row->subtotale ?? 0);
            }
        }

        $stampAmount = abs((float) setting('Importo marca da bollo'));
        $threshold = abs((float) setting("Soglia minima per l'applicazione della marca da bollo"));
        $currentStamp = $stampAmount > 0 && abs($stampTaxable) > $threshold;

        $vat = \Modules\Iva\Aliquota::find($vatId);
        if (!$vat) {
            return false;
        }
        $nature = (string) ($vat->codice_natura_fe ?? '');
        if (in_array($nature, $stampNatureCodes, true)) {
            $effects = self::storedEffects((float) $plan['input'], (float) $vat->percentuale, (bool) setting('Utilizza prezzi di vendita comprensivi di IVA'));
            $stampTaxable -= (float) $effects['net'];
        }

        $afterStamp = $stampAmount > 0 && abs($stampTaxable) > $threshold;

        return $currentStamp !== $afterStamp;
    }

    public static function hasSections($document): bool
    {
        foreach ($document->getRighe() as $row) {
            if (method_exists($row, 'isDescrizione') && $row->isDescrizione() && !empty($row->is_titolo)) {
                return true;
            }
        }
        return false;
    }

    private static function isManaged($discount): bool
    {
        $description = str_replace(["\r\n", "\r"], "\n", trim((string) ($discount->descrizione ?? '')));
        $position = stripos($description, "\nRif.");
        if ($position !== false) {
            $description = substr($description, 0, $position);
        }
        return strcasecmp(trim($description), self::DESCRIPTION) === 0;
    }

    private static function accountingTotal(float $taxable, float $vat, float $social = 0.0): float
    {
        return round($taxable, 2) + round($vat, 2) + round($social, 2);
    }

    private static function storedEffects(float $grossDiscount, float $vatPercent, bool $pricesIncludeVat): array
    {
        $factor = 1 + $vatPercent / 100;
        if ($factor <= 0) {
            throw new InvalidArgumentException('Aliquota IVA non valida.');
        }

        if ($pricesIncludeVat) {
            $input = round($grossDiscount, self::INPUT_DECIMALS, PHP_ROUND_HALF_UP);
            $vatRaw = $input * ($vatPercent / 100) / $factor;
            $vat = round($vatRaw, self::INPUT_DECIMALS, PHP_ROUND_HALF_UP);
            $net = round($input - $vatRaw, self::INPUT_DECIMALS, PHP_ROUND_HALF_UP);
        } else {
            $input = round($grossDiscount / $factor, self::INPUT_DECIMALS, PHP_ROUND_HALF_UP);
            $net = $input;
            $vat = round($input * $vatPercent / 100, self::INPUT_DECIMALS, PHP_ROUND_HALF_UP);
        }

        return ['input' => $input, 'net' => $net, 'vat' => $vat, 'gross' => round($net + $vat, self::INPUT_DECIMALS, PHP_ROUND_HALF_UP)];
    }
}
