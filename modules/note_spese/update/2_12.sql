-- Allineamento Note spese al perimetro definitivo (#1461)
-- Le Note spese rappresentano costi anticipati personalmente da un Operatore.

-- Rimuove il vecchio hook basato su import automatici da Automezzi/Scadenzario.
DELETE FROM `zz_hooks_lang`
WHERE `id_record` IN (SELECT `id` FROM `zz_hooks` WHERE `name` = 'Note spese da importare');
DELETE FROM `zz_hooks` WHERE `name` = 'Note spese da importare';

-- Mantiene leggibili le tipologie storiche, ma non le propone per nuove Note spese.
UPDATE `co_note_spese_tipologie`
SET `enabled` = 0
WHERE `codice` IN ('assicurazioni', 'affitti', 'contributi_tributi', 'spese_bancarie', 'personale');
