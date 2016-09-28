<!doctype html>
<html class="no-js" lang="es">
    <head>
        <meta charset="utf-8">
        <title>Test WebServices de AFIP :: WSFEv1</title>
        <link rel="stylesheet" href="css/style.css">

    </head>
    <body>
        <h1>Test WebServices de AFIP :: WSFEv1</h1>
        <div class="home_link"><a href="index.html" >Home</a></div>

<?php
/**
 * En el archivo php.ini se deben habilitar las siguientes extensiones
 *
 * extension=php_openssl (.dll / .so)
 * extension=php_soap    (.dll / .so)
 *
 */

error_reporting(E_ALL);
ini_set('display_errors','Yes');

//Cargando archivo de configuracion
include_once "../config.php";
include_once "functions.php";

//Cargando modelos de conexion a WebService
include_once MDL_PATH."AfipWsaa.php";
include_once MDL_PATH."AfipWsfev1.php";


//Datos correspondiente a la empresa que emite la factura
    //CUIT (Sin guiones)
    $empresaCuit  = '30710736215';
    //El alias debe estar mencionado en el nombre de los archivos de certificados y firmas digitales
    $empresaAlias = 'emp1';


//Obtener los datos de la factura que se desea generar
    //Elegir uno de los include como para tener diferentes tipos de factura
    include "data/TestRegistrarFeMultiIVA_ejemplo_Factura_tipo_A.php";
    //include "data/TestRegistrarFe_ejemplo_Factura_tipo_B.php";


//WebService que utilizara la autenticacion
$webService   = 'wsfe';
//Creando el objeto WSAA (Web Service de Autenticación y Autorización)
$wsaa = new AfipWsaa($webService,$empresaAlias);

//Creando el TA (Ticket de acceso)
if ($ta = $wsaa->loginCms())
{
    $token      = $ta['token'];
    $sign       = $ta['sign'];
    $expiration = $ta['expiration'];
    $uniqueid   = $ta['uniqueid'];

    //Conectando al WebService de Factura electronica (WsFev1)
    $wsfe = new AfipWsfev1($empresaCuit,$token,$sign);

    //Obteniendo el ultimo numero de comprobante autorizado
    $CompUltimoAutorizado = $wsfe->FECompUltimoAutorizado($PtoVta,$CbteTipo);
    echo "<h3>wsfe->FECompUltimoAutorizado(PtoVta,CbteTipo)</h3>";
    pr($CompUltimoAutorizado);
    
    /**
     * Aca se puede hacer una comparacion del Ultimo Comprobante Autorizado
     * y el ultimo comprobante que se registro en la base de datos.
     */

    $CbteDesde = $CompUltimoAutorizado['CbteNro'] + 1;
    $CbteHasta = $CbteDesde;



    //Armando el array para el Request
    //La estructura de este array esta diseñada de acuerdo al registro XML del WebService y utiliza las variables antes declaradas.
        $FeCAEReq = array (
            'FeCAEReq' => array (
                'FeCabReq' => array (
                    'CantReg' => 1,
                    'CbteTipo' => $CbteTipo,
                    'PtoVta' => $PtoVta
                    ),
                'FeDetReq' => array (
                    'FECAEDetRequest' => array(
                        'Concepto' => $Concepto,
                        'DocTipo' => $DocTipo,
                        'DocNro' => $DocNro,
                        'CbteDesde' => $CbteDesde,
                        'CbteHasta' => $CbteHasta,
                        'CbteFch' => $CbteFch,
                        'FchServDesde' => $FchServDesde,
                        'FchServHasta' => $FchServHasta,
                        'FchVtoPago' => $FchVtoPago,
                        'ImpTotal' => number_format(abs($ImpTotal),2,'.',''),
                        'ImpTotConc' => number_format(abs($ImpTotConc),2,'.',''),
                        'ImpNeto' => number_format(abs($ImpNeto),2,'.',''),
                        'ImpOpEx' => number_format(abs($ImpOpEx),2,'.',''),
                        'ImpIVA' => number_format(abs($ImpIVA),2,'.',''),
                        'ImpTrib' => number_format(abs($ImpTrib),2,'.',''),
                        'MonId' => $MonId,
                        'MonCotiz' => $MonCotiz
                        )
                    )
                ),
            );


        if ($tributoBaseImp || $tributoImporte)
        {
            $Tributos = array(
                'Tributo' => array (
                    'Id' => $tributoId,
                    'Desc' => $tributoDesc,
                    'BaseImp' => number_format(abs($tributoBaseImp),2,'.',''),
                    'Alic' => number_format(abs($tributoAlic),2,'.',''),
                    'Importe' => number_format(abs($tributoImporte),2,'.','')
                    )

            );
            $FeCAEReq['FeCAEReq']['FeDetReq']['FECAEDetRequest']['Tributos'] = $Tributos;
        }

        $FeCAEReq['FeCAEReq']['FeDetReq']['FECAEDetRequest']['Iva'] = $Iva;

    echo '
    <table>
        <caption>wsfe->FECAESolicitar(Request)</caption>
        <tr>
            <th >Request</th>
            <th >Response</th>
        </tr>
        <tr>
            <td>
    ';
    pr($FeCAEReq);

    echo "
            </td>
            <td>
    ";

    //Registrando la factura electronica
    $FeCAEResponse = $wsfe->FECAESolicitar($FeCAEReq);

    /**
     * Tratamiento de errores
     */
        
        if (!$FeCAEResponse)
        {
            /* Procesando ERRORES */

            echo '<h2 class="err">NO SE HA GENERADO EL CAE</h2>
                  <h3 class="err">ERRORES DETECTADOS</h3>';

            $errores = $wsfe->getErrLog();
            if (isset($errores))
            {
                foreach ($errores as $v)
                {
                    pr($v);
                }
            }
            echo "<hr/><h3>Response</h3>";

        }
        elseif (!$FeCAEResponse['FeDetResp']['FECAEDetResponse']['CAE'])
        {
            /* Procesando OBSERVACIONES */

            echo '<h2 class="msg">NO SE HA GENERADO EL CAE</h2>
                  <h3 class="msg">OBSERVACIONES INFORMADAS</h3>';

            if (isset($FeCAEResponse['FeDetResp']['FECAEDetResponse']['Observaciones']))
            {
                foreach ($FeCAEResponse['FeDetResp']['FECAEDetResponse']['Observaciones'] as $v)
                {
                    pr($v);
                }
            }
            echo "<hr/><h3>Response</h3>";
        }    

    pr($FeCAEResponse);


    
    echo "
            </td>
        </tr>
    </table>
    ";



}
else
{
    echo '
    <hr/>
    <h3>Errores detectados al generar el Ticket de Acceso</h3>';
    pr($wsaa->getErrLog());
}


?>
    </body>
</html>