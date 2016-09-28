<?php
/**
 * Archivo de configuracion
 */

// Estableciendo zona horaria
    date_default_timezone_set('America/Argentina/Buenos_Aires');

// Entorno de trabajo del server

    define('SERVER_ENTORNO' , 'Test');
    //define('SERVER_ENTORNO' , 'Prod');

// Paths

    // Root
    define('ROOT_DIR', str_replace('\\','/',realpath(dirname(__FILE__))).'/');

        // Temp
        define('TMP_PATH', ROOT_DIR.'tmp/');
        // Models
        define('MDL_PATH', ROOT_DIR.'afip/');
        // Library
        define('LIB_PATH',ROOT_DIR.'library/');

?>