<?php
//funciones de base
/**
* Hace un print_r de un $array
*/
function pr($array)
{
    echo "<pre>";
    print_r($array);
    echo "</pre>";
}

//Salida Critica del proceso
function criticalExit($message)
{
    $software = null;
    if (defined('SOFTWARE_NAME'))
        $software = SOFTWARE_NAME;
    if (defined('SOFTWARE_VER'))
        $software .= ' v'.SOFTWARE_VER;

    $error = '
    <div style="border:1px solid #dd5555;color:#dd5555;padding:10px;margin:10px;font-family:arial;">
        '.($software?'<h2 style="border-bottom:1px solid #dd5555;">Sistema '.$software.'</h2>':'').'
        <h3>ERROR CRITICO</h3>
        <li style="color:#555555;font-size:13px;">
            '.$message.'
        </li>
        <p style="color:#999999;font-size:12px;">Contacte al administrador del sistema informando el presente error.</p>
        <div style="color:#999999;font-size:12px;">

            $_REQUEST:<ul>';
    foreach ($_REQUEST as $k=>$v)
        $error .='<li>'.$k.': <b>'.$v.'</b></li>';
    $error .='
            </ul>
        </div>
    </div>';

    die($error);
}

/**
 * arrayToTable($array)
 *
 * @return html
 */
function arrayToTable($array)
{
    if (is_array($array))
    {
        $echo = "
        <div style=\"border:1px solid #343537;\" >
        <table class=\"arrayToTable\" border=\"1\" style=\"border-collapse: collapse;\">";
        foreach ($array as $k => $v)
        {
            $echo .= "
            <tr><th width=\"25%\" valign=\"top\" >$k</th><td valign=\"top\">".(!$v?"&nbsp;":(is_array($v)?arrayToTable($v):$v))."</td></tr>";
        }
        $echo .= "
        </table>
        </div>";
    }
    else
        $echo = $array;

    return $echo;

}

/**
 * Similar a arrayToTable,
 * pero imprime una tabla tipo dataSet
 * como un datagrid.
 */
function arrayToTableDg($array)
{
    if (is_array($array))
    {
        $first = reset($array);
        if ($first)
        {
            $ths = "
            <tr>
            <th style=\"width:30px;text-align:center;\">K</th>";
            foreach ($first as $k=>$v)
                $ths .= "<th>".$k."</th>";
            $ths .= "
            </tr>";

        }

        $echo = "
        <div style=\"border:1px solid #343537;overflow: auto;\" >
        <table class=\"arrayToTable DG\" style=\"border-collapse: collapse;\">";
        $echo .= $ths;
        foreach ($array as $k => $row)
        {
            $echo .= "
            <tr>
            <th style=\"text-align:center;\">".$k."</th>";
            foreach ($row as $v)
                $echo .= "<td >".($v?$v:"&nbsp;")."</td>";
            $echo .= "
            </tr>";
        }
        $echo .= "
        </table>
        </div>";
    }
    else
    {
        $echo = $array;
    }
    return $echo;
}
 
?>
