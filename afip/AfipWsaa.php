<?php
/*
Copyright 2013 2014 Leonardo Daniel Bisaro (leonardo.bisaro@gmail.com)

This program is free software; you can redistribute it and/or modify it 
under the terms of the GNU General Public License as published by the 
Free Software Foundation; either version 2 of the License, or (at your option) 
any later version. This program is distributed in the hope that it will be 
useful, but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
See the GNU General Public License for more details. 
You should have received a copy of the GNU General Public License along with 
this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, 
Fifth Floor, Boston, MA 02110-1301 USA.
*/

ini_set("soap.wsdl_cache_enabled", "0");
include_once LIB_PATH."ErrorLog.php";
class AfipWsaa
{
    protected $ws;         // The name of WebService
    protected $wsdl;       // The WSDL corresponding to WSAA
    protected $cert;       // The X.509 certificate in PEM format
    protected $privateKey; // The private key correspoding to CERT (PEM)
    protected $url;        // The url of WebService

    protected $ea;         // Alias de la empresa para la cual se especifica

    protected $wsFilePath;
    protected $traXmlFile;
    protected $traTmpFile;

    protected $errLog;

    public function __Construct($ws,$empresaAlias)
    {
        if (!$this->validWs($ws))
            criticalExit("AfipWsaa::__Construct() - Se debe especificar un WebService ".($ws?" valido. (Ref.: ".$ws.") ":"")." como parametro del constructor.");

        if (!$empresaAlias)
            criticalExit("AfipWsaa::__Construct() - Se debe especificar un alias de empresa como parametro del constructor.");

        $entorno    = SERVER_ENTORNO;

        $this->ws   = $ws;
        if ($entorno == 'Test')
            $this->ea   = $empresaAlias;
        elseif ($entorno == 'Prod')
            $this->ea   = $empresaAlias.'hp';

        $this->wsFilePath = dirname(__FILE__).'/wsfiles/';
        $this->traXmlFile = TMP_PATH.$this->ea.'_'.$this->ws.'_tra.xml';
        $this->traTmpFile = TMP_PATH.$this->ea.'_'.$this->ws.'_tra.tmp';
        $this->taFile     = TMP_PATH.$this->ea.'_'.$this->ws.'_ta.xml';

        $this->wsdl       = $this->wsFilePath."wsaa.wsdl";

        if ($entorno == 'Test')
        {
            $this->cert   = $this->wsFilePath.$this->ea."_test.crt";
            $this->url    = "https://wsaahomo.afip.gov.ar/ws/services/LoginCms";
            $this->privateKey = $this->wsFilePath.$this->ea."_test.key";
        }
        elseif ($entorno == 'Prod')
        {
            $this->cert   = $this->wsFilePath.$this->ea."_prod.crt";
            $this->url    = "https://wsaa.afip.gov.ar/ws/services/LoginCms";
            $this->privateKey = $this->wsFilePath.$this->ea."_prod.key";
        }
        else
        {
            criticalExit('AfipWsaa::__Construct() - No se ha definido la constante: SERVER_ENTORNO');
        }

        if (!file_exists($this->cert))
            criticalExit("AfipWsaa::__Construct() - No se pudo abrir el archivo ".$this->cert);

        if (!file_exists($this->privateKey))
            criticalExit("AfipWsaa::__Construct() - No se pudo abrir el archivo ".$this->privateKey);

        if (!file_exists($this->wsdl))
            criticalExit("AfipWsaa::__Construct() - No se pudo abrir el archivo ".$this->wsdl);

        $this->errLog = new ErrorLog();

    }


    public function loginCms()
    {
        //Verifica si existe un TA creado con anterioridad, y en caso que exista, verifica que se encuentre vigente.
        if ($aTa = $this->readTa())
            return $aTa;

        $err = 0;
        if (!$this->createTra())
        {
            $err++;
            $this->errLog->add('AfipWsaa::loginCms() - No se pudo crear el TRA');
        }
        else
        {
            if (!$cms = $this->signTra())
            {
                $err++;
                $this->errLog->add('AfipWsaa::loginCms() - No se pudo firmar el TRA');
            }
            else
            {
                $client=new SoapClient( $this->wsdl, array(
                                        'soap_version'   => SOAP_1_2,
                                        'location'       => $this->url,
                                        'trace'          => 1,
                                        'exceptions'     => 0
                                        ));
                $results=$client->loginCms(array('in0'=>$cms));
                file_put_contents(TMP_PATH.$this->ea.'_'.$this->ws."_request_loginCms.xml",$client->__getLastRequest());
                file_put_contents(TMP_PATH.$this->ea.'_'.$this->ws."_response_loginCms.xml",$client->__getLastResponse());
                if (is_soap_fault($results))
                {
                    $err++;
                    $this->errLog->add("AfipWsaa::loginCms() - Fallo en conexion SOAP: <b>".$results->faultstring."</b> [".$results->faultcode."]");
                }
                else
                {
                    $ta = $results->loginCmsReturn;
                    if (!file_put_contents($this->taFile, $ta))
                    {
                       $err++;
                        $this->errLog->add("AfipWsaa::loginCms() - No se pudo crear el archivo de Ticket de Acceso (".$this->taFile.")");
                    }
                    else
                    {
                        $aTa = $this->readTa();
                        return $aTa;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Devuelve un array con los errores registrados mediante $this->errlog->add()
     */
    public function getErrLog()
    {
        return $this->errLog->get();
    }

    /**
     * Verifica si existe un TA (Ticket de acceso) creado con anterioridad,
     * y en caso que exista, verifica que se encuentre vigente.
     *
     * En caso que exista y este vigente devuelve los valores del TA
     *
     */
    private function readTa()
    {
        if (file_exists($this->taFile))
        {
            $ta = file_get_contents($this->taFile);
            $xmlTa = new SimpleXMLElement($ta);

            $aTa['token']       = (string)$xmlTa->credentials->token;
            $aTa['sign']        = (string)$xmlTa->credentials->sign;
            $aTa['expiration']  = (string)$xmlTa->header->expirationTime;
            $aTa['uniqueid']    = (string)$xmlTa->header->uniqueId;


            // Verifica que el TA no expire en la proxima hora (3600 segundos)
            if (strtotime($aTa['expiration'])-(date('U') ) > 3600)
                return $aTa;
        }
        return null;
    }

    protected function createTra()
    {
        $ws = $this->ws;
        $tra = new SimpleXMLElement(
        '<?xml version="1.0" encoding="UTF-8"?>' .
        '<loginTicketRequest version="1.0">'.
        '</loginTicketRequest>');

        $tra->addChild('header');
        $tra->header->addChild('uniqueId',date('U'));
        $tra->header->addChild('generationTime',date('c',date('U')-600));
        $tra->header->addChild('expirationTime',date('c',date('U')+600));
        $tra->addChild('service',$ws);
        if ($tra->asXML($this->traXmlFile))
            return true;
        return false;
    }

    /**
     * This functions makes the PKCS#7 signature using TRA as input file, CERT and
     * PRIVATEKEY to sign. Generates an intermediate file and finally trims the
     * MIME heading leaving the final CMS required by WSAA.
     */
    protected function signTra()
    {
        $err = 0;
        if (!file_exists($this->traXmlFile))
        {
            $err++;
            $this->errLog->add("AfipWsaa::signTra() - No se encuentra el archivo ".$this->traXmlFile." generado por AfipWsaa::createTra()");
        }
        else
        {
            $status = openssl_pkcs7_sign($this->traXmlFile, $this->traTmpFile, "file://".$this->cert,
                                         array("file://".$this->privateKey, ""),
                                         array(),
                                         !PKCS7_DETACHED
                                         );

            if (!$status)
            {
                $err++;
                $this->errLog->add("AfipWsaa::signTra() - No se pudo generar la forma PKCS#7 en el TRA");
            }
            else
            {

                $inf=fopen($this->traTmpFile, "r");
                $i=0;
                $cms="";
                while (!feof($inf))
                {
                    $buffer=fgets($inf);
                    if ( $i++ >= 4 )
                        $cms.=$buffer;
                }
                fclose($inf);
                //unlink($this->traXmlFile);
                //unlink($this->traTmpFile);
                return $cms;

            }
        }
        return false;
    }

    protected function validWs($ws)
    {
        $validWs['wsfe'] = true;
        $validWs['wsfex'] = true;
        $validWs['wsmtxca'] = true;

        if ($validWs[$ws])
            return true;
        return false;
    }

    function getSetUp()
    {
        $setUp['ws'] = $this->ws;
        $setUp['ea'] = $this->ea;
        $setUp['entorno'] = SERVER_ENTORNO;
        $setUp['url'] = $this->url;
        $setUp['wsFilePath'] = $this->wsFilePath;
        $setUp['traXmlFile'] = $this->traXmlFile;
        $setUp['traTmpFile'] = $this->traTmpFile;
        $setUp['taFile'] = $this->taFile;
        $setUp['wsdl'] = $this->wsdl;
        $setUp['cert'] = $this->cert;
        $setUp['privateKey'] = $this->privateKey;
        $setUp['url'] = $this->url;
        return $setUp;
    }



}
?>
