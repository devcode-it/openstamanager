<?php

include_once __DIR__.'/../../core.php';

echo '
    <table class="table table-striped table-condensed table-hover table-bordered"
        <tr>
            <th>'.tr('Conto').'</th>
            <th width="20%">'.tr('Dare').'</th>
            <th width="20%">'.tr('Avere').'</th>
        </tr>';

$max = max(count($righe), 10);
for ($i = 0; $i < $max; ++$i) {
    $required = ($i <= 1);
    $riga = $righe[$i];

    // Conto
    echo '
			<tr>
                <input type="hidden" name="id_scadenza['.$i.']" value="'.$riga['id_scadenza'].'">
                
				<td>
					{[ "type": "select", "name": "idconto['.$i.']", "id": "conto'.$i.'", "value": "'.($riga['id_conto'] ?: '').'", "ajax-source": "conti", "required": "'.$required.'" ]}
				</td>';

    // Dare
    echo '
				<td>
					{[ "type": "number", "name": "dare['.$i.']", "id": "dare'.$i.'", "value": "'.($riga['dare'] ?: 0).'" ]}
				</td>';

    // Avere
    echo '
				<td>
					{[ "type": "number", "name": "avere['.$i.']", "id": "avere'.$i.'", "value": "'.($riga['avere'] ?: 0).'" ]}
				</td>
			</tr>';
}

// Totale per controllare sbilancio
echo '
            <tr>
                <td align="right"><b>'.tr('Totale').':</b></td>';

// Totale dare
echo '
                <td align="right">
                    <span><span id="totale_dare"></span> '.currency().'</span>
                </td>';

// Totale avere
echo '
                <td align="right">
                    <span><span id="totale_avere"></span> '.currency().'</span>
                </td>
            </tr>';

// Verifica sbilancio
echo '
            <tr>
                <td align="right"></td>
                <td colspan="2" align="center">
                    <span id="testo_aggiuntivo"></span>
                </td>
            </tr>
        </table>';

echo '
<script>
var formatted_zero = "'.Translator::numberToLocale(0).'";
var sbilancio = "'.tr('sbilancio di _NUM_', [
    '_NUM_' => '|value| '.currency(),
]).'";

// Ad ogni modifica dell\'importo verifica che siano stati selezionati: il conto, la causale, la data. Inoltre aggiorna lo sbilancio
function calcolaBilancio() {
    bilancio = 0.00;
    totale_dare = 0.00;
    totale_avere = 0.00;

    // Calcolo il totale dare e totale avere
    $("input[id*=dare]").each(function() {
        valore = $(this).val() ? $(this).val().toEnglish() : 0;

        totale_dare += Math.round(valore * 100) / 100;
    });

    $("input[id*=avere]").each(function() {
        valore = $(this).val() ? $(this).val().toEnglish() : 0;

        totale_avere += Math.round(valore * 100) / 100;
    });

    $("#totale_dare").text(totale_dare.toLocale());
    $("#totale_avere").text(totale_avere.toLocale());

    bilancio = Math.round(totale_dare * 100) / 100 - Math.round(totale_avere * 100) / 100;

    if (bilancio == 0) {
        $("#testo_aggiuntivo").removeClass("text-danger").html(\'\');
        $("#add-submit").removeClass("hide");
        $("#btn_crea_modello").removeClass("hide");
    } else {
        $("#testo_aggiuntivo").addClass("text-danger").html(sbilancio.replace("|value|", bilancio.toLocale()));
        $("#add-submit").addClass("hide");
        $("#btn_crea_modello").addClass("hide");
    }

    return bilancio == 0;
}

function bloccaZeri(){
    $("input[id*=dare], input[id*=avere]").each(function() {
        if ($(this).val() == formatted_zero) {
            $(this).prop("disabled", true);
        } else {
            $(this).prop("disabled", false);
        }
    });
}

$(document).ready(function() {
    calcolaBilancio();
    bloccaZeri();

    $("select").on("change", function() {
        if ($(this).parent().parent().find("input[disabled]").length != 1) {
            if ($(this).val()) {
                $(this).parent().parent().find("input").prop("disabled", false);
            } else {
                $(this).parent().parent().find("input").prop("disabled", true);
                $(this).parent().parent().find("input").val("0.00");
            }
        }
    });

    $("input[id*=dare]").on("keyup change", function() {
        if (!$(this).prop("disabled")) {
            if ($(this).val()) {
                $(this).parent().parent().find("input[id*=avere]").prop("disabled", true);
            } else {
                $(this).parent().parent().find("input[id*=avere]").prop("disabled", false);
            }

            calcolaBilancio();
        }
    });

    $("input[id*=avere]").on("keyup change", function() {
        if (!$(this).prop("disabled")) {
            if ($(this).val()) {
                $(this).parent().parent().find("input[id*=dare]").prop("disabled", true);
            } else {
                $(this).parent().parent().find("input[id*=dare]").prop("disabled", false);
            }

            calcolaBilancio();
        }
    });

    // Trigger dell"evento keyup() per la prima volta, per eseguire i dovuti controlli nel caso siano predisposte delle righe in prima nota
    $("input[id*=dare][value!=\'\'], input[id*=avere][value!=\'\']").keyup();

    $("select[id*=idconto]").click(function() {
        $("input[id*=dare][value!=\'\'], input[id*=avere][value!=\'\']").keyup();
    });
});
</script>';
