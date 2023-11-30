<?php
    include_once __DIR__.'/../../core.php';

    echo "<br>";
    
    $targa = "";
	for( $r=0; $r<sizeof($rs); $r++ ){
        if ($targa != $rs[$r]['targa']) {
            if ($targa != "") {
                echo "
                </table>
                    <br/>";
            }
            echo "
                <table cellspacing='0' style='table-layout:fixed;'>
                    <col width='150'><col width='250'>
                    <tr>
                        <th bgcolor='#ffffff' class='full_cell1 cell-padded' width='150'>Targa: ".$rs[$r]['targa']."</th>
                        <th bgcolor='#ffffff' class='full_cell cell-padded' width='250'>Automezzo: ".$rs[$r]['nome']."</th>
                    </tr>
                </table>

                <table class='table table-bordered' cellspacing='0' style='table-layout:fixed;'>
                    <col width='50'><col width='300'><col width='50'><col width='50'><col width='50'>
                    <tr>
                        <th bgcolor='#dddddd' class='full_cell1 cell-padded' width='10%'>Codice</th>
                        <th bgcolor='#dddddd' class='full_cell cell-padded' >Descrizione</th>
                        <th bgcolor='#dddddd' class='full_cell cell-padded' width='20%'>Sub.Cat.</th>
                        <th bgcolor='#dddddd' class='full_cell cell-padded' width='15%'>Q.t&agrave;</th>
                        <th bgcolor='#dddddd' class='full_cell cell-padded' width='5%'></th>
                    </tr>";
            $targa = $rs[$r]['targa'];
        }
        echo "
		<tr>";
        
        $qta = number_format( $rs[$r]['qta'], 3, ",", "." )."&nbsp;".$rs[$r]['um'];

        echo "
            <td class='first_cell cell-padded'>".$rs[$r]['codice']."</td>
            <td class='table_cell cell-padded'>".$rs[$r]['descrizione']."</td>
            <td class='table_cell cell-padded'>".$rs[$r]['subcategoria']."</td>
            <td class='table_cell text-right cell-padded'>".$qta."</td>
            <td class='table_cell cell-padded'></td>
        </tr>";
    }
    if ($targa != "") {
        echo "
        </table>";
    }

?>
