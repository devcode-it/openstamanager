<?php

if (file_exists(base_dir().'/files/my_impianti')) {
    copyr(base_dir().'/files/my_impianti', base_dir().'/files/impianti');
    delete(base_dir().'/files/my_impianti');
}
