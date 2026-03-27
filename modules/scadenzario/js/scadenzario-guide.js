/**
 * Guida Scadenzario
 * Guida interattiva per l'utilizzo del modulo Scadenzario
 */

$(document).ready(function() {
    // Verifica se siamo nel modulo Scadenzario
    var moduleName = $('#main-modal .modal-title').first().text() || $('h1').first().text() || '';
    
    if (moduleName.includes('Scadenzario') || $('h1').length > 0 && $('h1').first().text().includes('Scadenzario')) {
        initScadenzarioGuide();
    }
});

function showScadenzarioGuide() {
    if (typeof swal === 'undefined') {
        alert('Guida ai filtri delle tabelle\n\n');
        return;
    }

    swal({
        title: 'Guida ai filtri in Scadenzario',
        html: `
            <p> 
                != in caso si voglia ricercare un record diverso da un dato valore specifico;<br>
                Esempio: stato != "pagato" → trova tutte le scadenze non pagate<br><br>
                = in caso si voglia ricercare uno specifico valore all'interno dei record;<br>
                Esempio: stato = "pagato" → trova tutte le scadenze pagate<br>
                Esempio: ricerca per data specifica "=01/01/2021" → trova tutte le scadenze con data scadenza 01/01/2021<br><br>
                > in caso si voglia ricercare un record maggiore di un dato valore specifico;<br>
                Esempio: importo > 100 → trova tutte le scadenze con importo maggiore di 100<br>
                Esempio: data scadenza > "01/01/2021" → trova tutte le scadenze con data scadenza successiva al 01/01/2021<br><br>
                < in caso si voglia ricercare un record minore di un dato valore specifico;<br>
                Esempio: importo < 100 → trova tutte le scadenze con importo minore di 100<br>
                Esempio: data scadenza < "01/01/2021" → trova tutte le scadenze con data scadenza precedente al 01/01/2021<br>
            </p>
        `,
        type: 'info',
        showConfirmButton: false,
        confirmButtonClass: 'btn-info',
        confirmButtonText: 'Ho capito',
        width: '700px'
    });
}
