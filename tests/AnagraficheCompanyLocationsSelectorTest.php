<?php

use PHPUnit\Framework\TestCase;

class AnagraficheCompanyLocationsSelectorTest extends TestCase
{
    public function testCompanyLocationsUseTheCompanyForeignKey(): void
    {
        $source = file_get_contents(__DIR__.'/../modules/anagrafiche/ajax/select.php');
        preg_match("/case 'sedi_azienda':(?<selector>.*?)case 'referenti':/s", $source, $matches);

        $selector = $matches['selector'] ?? '';

        $this->assertStringContainsString('FROM `an_sedi` |where_sedi|', $selector);
        $this->assertStringContainsString("\$where_sedi[] = '`id_anagrafica`='.prepare(\$id_azienda);", $selector);
        $this->assertStringContainsString("\$where_sedi[] = 'deleted_at IS NULL';", $selector);
        $this->assertStringContainsString("str_replace('|where_sedi|', 'WHERE '.implode(' AND ', \$where_sedi), \$query)", $selector);
    }
}
