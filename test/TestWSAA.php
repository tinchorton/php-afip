<!doctype html>
<html class="no-js" lang="es">
    <head>
        <meta charset="utf-8">
        <title>Test WebServices de AFIP :: WSAA</title>
        <link rel="stylesheet" href="css/style.css">

    </head>
    <body>
        <h1>Test WebServices de AFIP :: WSAA</h1>
        <div class="home_link"><a href="index.html" >Home</a></div>

<?php

/**
 * En el archivo php.ini se deben habilitar las siguientes extensiones
 *
 * extension=php_openssl (.dll / .so)
 * extension=php_soap    (.dll / .so)
 *
 */

//Cargando archivo de configuracion
include_once "../config.php";
include_once LIB_PATH."functions.php";


//Cargando modelos de conexion a WebService
include_once MDL_PATH."AfipWsaa.php";
include_once MDL_PATH."AfipWsfev1.php";

//Datos correspondiente a la empresa 1
    //CUIT (Sin guiones)
    $empresaCuit  = '30710736215';
    //El alias debe estar mencionado en el nombre de los archivos de certificados y firmas digitales
    $empresaAlias = 'emp1';

//WebService que utilizara la autenticacion (A modo de ejemplo para este test)
$webService   = 'wsfe';


//Creando el objeto WSAA (Web Service de Autenticación y Autorización)

$wsaa = new AfipWsaa($webService,$empresaAlias);


//Creando el TA (Ticket de acceso)
if ($ta = $wsaa->loginCms())
{
    echo '
    <h2>Ticket de Acceso generado [Entorno: '.SERVER_ENTORNO.']</h2>
    <h3>Mostrando el setup del WSAA mediante: AfipWsaa::getSetUp()</h3>';
    pr($wsaa->getSetUp());
    echo '
    <hr/>';

    echo '
    <h2>Ticket de Acceso generado [Entorno: '.SERVER_ENTORNO.']</h2>
    <h3>Mostrando el TA (Ticket de Acceso)</h3>';
    pr($ta);
    echo '
    <hr/>';

    //Conectando al WebService de Factura electronica (WsFev1)
    $wsfe = new AfipWsfev1($empresaCuit,$ta['token'],$ta['sign']);

    $ret = $wsfe->FEParamGetPtosVenta();
    echo "<h3>AfipWsfev1::FEParamGetPtosVenta()</h3>";
    pr($ret);    
    echo '
    <hr/>';

}
else
{
    echo '
    <hr/>
    <h2>Errores detectados al generar el Ticket de Acceso</h2>
    <pre>';
    print_r($wsaa->getErrLog());
    echo '
    </pre>';
}

?>
    </body>
</html>
