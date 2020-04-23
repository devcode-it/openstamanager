<?php

if (!$is_last_page) {
    return;
}

echo '
<table>
    <tr>
        <td style="vertical-align:bottom;" width="50%">
            '.tr('lì').', ___________________________
        </td>

        <td align="center" style="vertical-align:bottom;" width="50%">
            '.tr('Firma per accettazione', [], ['upper' => true]).'<br><br>
            _____________________________________________
        </td>
    </tr>
</table>
<br>';
