<?php

namespace Modules\Interventi\Tests;

use Modules\Interventi\Components\Sessione;
use PHPUnit\Framework\TestCase;

class SessioneDurataTest extends TestCase
{
    public function testDurataValida(): void
    {
        $sessione = new Sessione();
        $sessione->orario_inizio = '2026-09-02 14:30:00';
        $sessione->orario_fine = '2026-09-02 18:30:00';

        $this->assertSame(4.0, $sessione->ore_calcolate);
    }

    public function testDurataZeroNonValida(): void
    {
        $sessione = new Sessione();
        $sessione->orario_inizio = '2026-09-02 14:30:00';
        $sessione->orario_fine = '2026-09-02 14:30:00';

        $this->expectException(\InvalidArgumentException::class);
        $sessione->ore_calcolate;
    }

    public function testFinePrecedenteNonValida(): void
    {
        $sessione = new Sessione();
        $sessione->orario_inizio = '2026-09-02 14:30:00';
        $sessione->orario_fine = '2026-09-02 13:30:00';

        $this->expectException(\InvalidArgumentException::class);
        $sessione->ore_calcolate;
    }

    public function testCasoIssue1910NonValido(): void
    {
        $sessione = new Sessione();
        $sessione->orario_inizio = '2026-09-02 14:30:00';
        $sessione->orario_fine = '2026-08-27 18:30:00';

        $this->expectException(\InvalidArgumentException::class);
        $sessione->ore_calcolate;
    }

    public function testDurataSuPiuGiorniValida(): void
    {
        $sessione = new Sessione();
        $sessione->orario_inizio = '2026-09-02 14:30:00';
        $sessione->orario_fine = '2026-09-03 10:30:00';

        $this->assertSame(20.0, $sessione->ore_calcolate);
    }
}
