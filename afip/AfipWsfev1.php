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
class AfipWsfev1
{
    protected $wsdl;       // The WSDL corresponding to WSAA
    protected $url;        // The url of WebService

    protected $token;
    protected $sign;
    protected $cuit;

    protected $wsFilePath;

    protected $errLog;

    protected $client;

    protected $wsVersion = 'wsfev1';

    public function __Construct($cuit, $token, $sign)
    {
        $prmErr=null;
        if (!$cuit)
            $prmErr = "<li>AfipWsfev1::__Construct() - Se debe especificar el parametro cuit</li>";
        elseif (!$token)
            $prmErr .= "<li>AfipWsfev1::__Construct() - Se debe especificar el parametro token</li>";
        elseif (!$sign)
            $prmErr .= "<li>AfipWsfev1::__Construct() - Se debe especificar el parametro sign</li>";

        if ($prmErr)
            criticalExit($prmErr);


        $entorno = SERVER_ENTORNO;

        $this->token = $token;
        $this->sign  = $sign;
        $this->cuit  = $cuit;

        $this->wsFilePath = dirname(__FILE__).'/wsfiles/';
        $this->wsdl       = $this->wsFilePath."wsfe_v1.wsdl";

        if ($entorno == 'Test')
            $this->url    = "https://wswhomo.afip.gov.ar/wsfev1/service.asmx";
        elseif ($entorno == 'Prod')
            $this->url    = "https://servicios1.afip.gov.ar/wsfev1/service.asmx";
        else
            criticalExit('AfipWsfev1::__Construct() - No se ha definido la constante SERVER_ENTORNO (Test - Prod)');

        $this->errLog = new ErrorLog();

        $this->client = new SoapClient( $this->wsdl, array(
                                'soap_version'   => SOAP_1_2,
                                'location'       => $this->url,
                                'trace'          => 1,
                                'exceptions'     => 0
                                ));

        file_put_contents(TMP_PATH."wsfev1_functions.txt",print_r($this->client->__getFunctions(),TRUE));
        file_put_contents(TMP_PATH."wsfev1_types.txt",print_r($this->client->__getTypes(),TRUE));


    }

    public function FECAESolicitar($FeCAEReq)
    {
        $prm = array(
                    'Auth'          => array(
                        'Token'     => $this->token,
                        'Sign'      => $this->sign,
                        'Cuit'      => $this->cuit
                        ),
                    'FeCAEReq'      => $FeCAEReq['FeCAEReq']

                    );

        $results = $this->client->FECAESolicitar($prm);
        if (!$this->getErrors($results, 'FECAESolicitar'))
        {
            $arrRet['FeCabResp']['Cuit']   = $results->FECAESolicitarResult->FeCabResp->Cuit;
            $arrRet['FeCabResp']['PtoVta']   = $results->FECAESolicitarResult->FeCabResp->PtoVta;
            $arrRet['FeCabResp']['CbteTipo']   = $results->FECAESolicitarResult->FeCabResp->CbteTipo;
            $arrRet['FeCabResp']['FchProceso']   = $results->FECAESolicitarResult->FeCabResp->FchProceso;
            $arrRet['FeCabResp']['CantReg']   = $results->FECAESolicitarResult->FeCabResp->CantReg;
            $arrRet['FeCabResp']['Resultado']   = $results->FECAESolicitarResult->FeCabResp->Resultado;
            $arrRet['FeCabResp']['Reproceso']   = $results->FECAESolicitarResult->FeCabResp->Reproceso;


            $arrRet['FeDetResp']['FECAEDetResponse']['Concepto']  = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Concepto;
            $arrRet['FeDetResp']['FECAEDetResponse']['DocTipo']  = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->DocTipo;
            $arrRet['FeDetResp']['FECAEDetResponse']['DocNro']  = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->DocNro;
            $arrRet['FeDetResp']['FECAEDetResponse']['CbteDesde']  = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CbteDesde;
            $arrRet['FeDetResp']['FECAEDetResponse']['CbteHasta']  = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CbteHasta;
            $arrRet['FeDetResp']['FECAEDetResponse']['CbteFch']  = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CbteFch;
            $arrRet['FeDetResp']['FECAEDetResponse']['Resultado']  = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Resultado;
            $arrRet['FeDetResp']['FECAEDetResponse']['CAE']  = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAE;
            $arrRet['FeDetResp']['FECAEDetResponse']['CAEFchVto']  = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAEFchVto;

            if (isset($results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones) && $retObs = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones->Obs)
            {
                if (is_array($retObs))
                {
                    foreach ($retObs as $k => $v)
                    {
                        $arrRet['FeDetResp']['FECAEDetResponse']['Observaciones']['Obs'][$k]['Code'] = $retObs[$k]->Code;
                        $arrRet['FeDetResp']['FECAEDetResponse']['Observaciones']['Obs'][$k]['Msg']  = $retObs[$k]->Msg;
                    }
                }
                else
                {
                    $arrRet['FeDetResp']['FECAEDetResponse']['Observaciones']['Obs'][0]['Code'] = $retObs->Code;
                    $arrRet['FeDetResp']['FECAEDetResponse']['Observaciones']['Obs'][0]['Msg'] = $retObs->Msg;
                }
            }

            $arrRet['WsVersion'] = $this->wsVersion;
            return $arrRet;
        }
        return false;
    }

    public function FECompConsultar($CbteTipo,$CbteNro,$PtoVta)
    {
        $prm = array(
                    'Auth'          => array(
                        'Token'     => $this->token,
                        'Sign'      => $this->sign,
                        'Cuit'      => $this->cuit
                        ),
                    'FeCompConsReq' => array (
                        'CbteTipo'  => $CbteTipo,
                        'CbteNro'   => $CbteNro,
                        'PtoVta'    => $PtoVta
                        )

                    );
        $results = $this->client->FECompConsultar($prm);
        if (!$this->getErrors($results, 'FECompConsultar'))
        {
            $arr = $this->resultToArray($results);
            $arrRet = $arr['FECompConsultarResult']['ResultGet'];
            return $arrRet;
        }
        return false;
    }    

    public function FECompTotXRequest()
    {
        $prm = array(
                    'Auth' => array(
                        'Token' => $this->token,
                        'Sign'  => $this->sign,
                        'Cuit'  => $this->cuit)
                    );
        $results = $this->client->FECompTotXRequest($prm);
        if (!$this->getErrors($results, 'FECompTotXRequest'))
        {
            $arrRet['RegXReq'] = $results->FECompTotXRequestResult->RegXReq;
            return $arrRet;
        }
        return false;
    }

    public function FEParamGetTiposDoc()
    {
        $prm = array(
                    'Auth' => array(
                        'Token' => $this->token,
                        'Sign'  => $this->sign,
                        'Cuit'  => $this->cuit)
                    );
        $results = $this->client->FEParamGetTiposDoc($prm);
        if (!$this->getErrors($results, 'FEParamGetTiposDoc'))
        {
            $docTipo = $results->FEParamGetTiposDocResult->ResultGet->DocTipo;
            if (is_array($docTipo))
            {
                foreach ($docTipo as $k => $itDocTipo)
                {
                    $arrRet['DocTipo'][$k]['Id']        = $itDocTipo->Id;
                    $arrRet['DocTipo'][$k]['Desc']      = $itDocTipo->Desc;
                    $arrRet['DocTipo'][$k]['FchDesde']  = $itDocTipo->FchDesde;
                    $arrRet['DocTipo'][$k]['FchHasta']  = $itDocTipo->FchHasta;
                }
            }
            else
            {
                $arrRet['DocTipo'][0]['Id']        = $docTipo->Id;
                $arrRet['DocTipo'][0]['Desc']      = $docTipo->Desc;
                $arrRet['DocTipo'][0]['FchDesde']  = $docTipo->FchDesde;
                $arrRet['DocTipo'][0]['FchHasta']  = $docTipo->FchHasta;
            }
            return $arrRet;
        }
        return false;
    }

    public function FECompUltimoAutorizado($PtoVta,$CbteTipo)
    {
        $prm = array(
                    'Auth' => array(
                        'Token' => $this->token,
                        'Sign'  => $this->sign,
                        'Cuit'  => $this->cuit
                        ),
                    'PtoVta' => $PtoVta,
                    'CbteTipo' => $CbteTipo

                    );
        $results = $this->client->FECompUltimoAutorizado($prm);
        if (!$this->getErrors($results, 'FECompUltimoAutorizado'))
        {
            $arrRet['PtoVta']   = $results->FECompUltimoAutorizadoResult->PtoVta;
            $arrRet['CbteTipo']   = $results->FECompUltimoAutorizadoResult->CbteTipo;
            $arrRet['CbteNro']   = $results->FECompUltimoAutorizadoResult->CbteNro;
            return $arrRet;
        }
        return false;
    }

    public function FEParamGetTiposCbte()
    {
        $prm = array(
                    'Auth' => array(
                        'Token' => $this->token,
                        'Sign'  => $this->sign,
                        'Cuit'  => $this->cuit)
                    );
        $results = $this->client->FEParamGetTiposCbte($prm);
        if (!$this->getErrors($results, 'FEParamGetTiposCbte'))
        {
            $arrRet['CbteTipo'] = $results->FEParamGetTiposCbteResult->ResultGet->CbteTipo;
            return $arrRet;
        }
        return false;
    }

    public function FEParamGetPtosVenta()
    {
        $prm = array(
                    'Auth' => array(
                        'Token' => $this->token,
                        'Sign'  => $this->sign,
                        'Cuit'  => $this->cuit,
                        'Cuit2'  => $this->cuit-10)
                        );
        $results = $this->client->FEParamGetPtosVenta($prm);
        if (!$this->getErrors($results, 'FEParamGetPtosVenta'))
        {
            $ptoVenta = $results->FEParamGetPtosVentaResult->ResultGet->PtoVenta;
            if (is_array($ptoVenta))
                $arrRet['PtoVenta'] = $ptoVenta;
            else
                $arrRet['PtoVenta'][0] = $ptoVenta;
            return $arrRet;
        }
        return false;
    }

    public function FEParamGetTiposConcepto()
    {
        $prm = array(
                    'Auth' => array(
                        'Token' => $this->token,
                        'Sign'  => $this->sign,
                        'Cuit'  => $this->cuit)
                    );
        $results = $this->client->FEParamGetTiposConcepto($prm);
        if (!$this->getErrors($results, 'FEParamGetTiposConcepto'))
        {
            $arrRet['ConceptoTipo']   = $results->FEParamGetTiposConceptoResult->ResultGet->ConceptoTipo;
            return $arrRet;
        }
        return false;
    }

    public function FEParamGetTiposOpcional()
    {
        $prm = array(
                    'Auth' => array(
                        'Token' => $this->token,
                        'Sign'  => $this->sign,
                        'Cuit'  => $this->cuit)
                    );
        $results = $this->client->FEParamGetTiposOpcional($prm);
        if (!$this->getErrors($results, 'FEParamGetTiposOpcional'))
        {
            $arrRet['OpcionalTipo']   = $results->FEParamGetTiposOpcionalResult->ResultGet->OpcionalTipo;
            return $arrRet;
        }
        return false;
    }

    public function FEParamGetTiposIva()
    {
        $prm = array(
                    'Auth' => array(
                        'Token' => $this->token,
                        'Sign'  => $this->sign,
                        'Cuit'  => $this->cuit)
                    );
        $results = $this->client->FEParamGetTiposIva($prm);
        if (!$this->getErrors($results, 'FEParamGetTiposIva'))
        {
            $arrRet['IvaTipo']   = $results->FEParamGetTiposIvaResult->ResultGet->IvaTipo;
            return $arrRet;
        }
        return false;
    }

    public function FEParamGetTiposTributos()
    {
        $prm = array(
                    'Auth' => array(
                        'Token' => $this->token,
                        'Sign'  => $this->sign,
                        'Cuit'  => $this->cuit)
                    );
        $results = $this->client->FEParamGetTiposTributos($prm);
        if (!$this->getErrors($results, 'FEParamGetTiposTributos'))
        {
            $arrRet['TributoTipo']   = $results->FEParamGetTiposTributosResult->ResultGet->TributoTipo;
            return $arrRet;
        }
        return false;
    }

    public function FEParamGetTiposMonedas()
    {
        $prm = array(
                    'Auth' => array(
                        'Token' => $this->token,
                        'Sign'  => $this->sign,
                        'Cuit'  => $this->cuit)
                    );
        $results = $this->client->FEParamGetTiposMonedas($prm);
        if (!$this->getErrors($results, 'FEParamGetTiposMonedas'))
        {
            $arrRet['Moneda']   = $results->FEParamGetTiposMonedasResult->ResultGet->Moneda;
            return $arrRet;
        }
        return false;
    }

    public function FEParamGetCotizacion($MonId)
    {
        $prm = array(
                    'Auth' => array(
                        'Token' => $this->token,
                        'Sign'  => $this->sign,
                        'Cuit'  => $this->cuit),
                    'MonId' => $MonId
                    );

        $results = $this->client->FEParamGetCotizacion($prm);

        if (!$this->getErrors($results, 'FEParamGetCotizacion'))
        {
            $arrRet['MonId']    = $results->FEParamGetCotizacionResult->ResultGet->MonId;
            $arrRet['MonCotiz'] = $results->FEParamGetCotizacionResult->ResultGet->MonCotiz;
            $arrRet['FchCotiz'] = $results->FEParamGetCotizacionResult->ResultGet->FchCotiz;
            return $arrRet;
        }
        return false;
    }

    public function FEDummy()
    {
        $results = $this->client->FEDummy();
        if (!$this->getErrors($results, 'FEDummy'))
        {
            $arrRet['AppServer']  = $results->FEDummyResult->AppServer;
            $arrRet['DbServer']   = $results->FEDummyResult->DbServer;
            $arrRet['AuthServer'] = $results->FEDummyResult->AuthServer;
            return $arrRet;
        }
        return false;
    }

    private function getErrors($results, $method)
    {
        // Quitar comentarios para ver el log generado

        file_put_contents(TMP_PATH.'CUIT_'.$this->cuit."_WSFEv1_request_".$method.".xml",$this->client->__getLastRequest());
        file_put_contents(TMP_PATH.'CUIT_'.$this->cuit."_WSFEv1_response_".$method.".xml",$this->client->__getLastResponse());


        if (is_soap_fault($results))
        {
            $this->errLog->add("Conexion SOAP: <b>".$results->faultstring.".</b> [AFIP::WSFEv1::".$method." - Error Code: ".$results->faultcode."]");
            return true;
        }

        if ($method == 'FEDummy')
            return false;

        $mr = $method.'Result';
        
        if (isset($results->$mr->Errors))
        {
            $errors = $results->$mr->Errors;
            if (is_array($errors))
            {
                foreach ($errors as $error)
                    $this->errLog->add("WebService AFIP::WSFEv1::".$method." <b>".$error->Err->Msg.".</b> [Codigo de error: ".$error->Err->Code."]");
            }
            else
            {
                $this->errLog->add("WebService AFIP::WSFEv1::".$method." <b>".$errors->Err->Msg.".</b> [Codigo de error: ".$errors->Err->Code."]");
            }
            return true;
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

    private function resultToArray($obj)
    {
        if (!is_object($obj))
        {
            $arr = $obj;
        }
        else
        {
            $arr = get_object_vars($obj);
            if (is_array($arr))
            {
                foreach ($arr as $k => $v)
                {
                    if (is_object($v))
                        $arr[$k] = $this->resultToArray($v);
                    else
                        $arr[$k] = $v;
                }
            }
        }
        return $arr;
    }
}
?>
