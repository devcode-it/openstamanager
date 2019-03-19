<?php

// File e cartelle deprecate
$files = [
    'plugins/xml/AT_v1.0.xml',
    'plugins/xml/DT_v1.0.xml',
    'plugins/xml/EC_v1.0.xml',
    'plugins/xml/MC_v1.0.xml',
    'plugins/xml/MT_v1.0.xml',
    'plugins/xml/NE_v1.0.xml',
    'plugins/xml/NS_v1.0.xml',
    'plugins/xml/RC_v1.0.xml',
    'plugins/xml/SE_v1.0.xml',
	'plugins/exportFE/view.php',
	'plugins/exportFE/src/stylesheet-1.2.1.xsl',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(DOCROOT.'/'.$value);
}

delete($files);
