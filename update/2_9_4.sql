-- Fix: rendere il capitale sociale opzionale (NULL se non specificato)
ALTER TABLE `an_anagrafiche`
    MODIFY `capitale_sociale` DECIMAL(15,6) NULL DEFAULT NULL;
