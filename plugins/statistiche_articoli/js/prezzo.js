class Prezzo extends Table {
    constructor(calendar, id, direzione){
        super(calendar, "manage_prezzi.php", {
            direzione: direzione
        }, id);
    }
}
