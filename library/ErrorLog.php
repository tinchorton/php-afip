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

class ErrorLog
{

    protected $log = array();

    function add($str)
    {
        if(!empty($str))
        {
            if (is_array($str))
                foreach ($str as $it)
                    $this->log[] = $it;
            else
                $this->log[] = $str;
        }
    }
    function addError($error)
    {
        $this->add($error);
    }
    function addErr($error)
    {
        $this->add($error);
    }


    function get($reset = true)
    {
        if (empty($this->log))
            return null;

        $log = $this->log;
        if ($reset)
            $this->reset();
        return $log;
    }
    function getErrLog()
    {
        return $this->get();
    }

    /**
     * Funcion agregada para mantener compatibilidad con $this->resetErrLog()
     */
    function reset()
    {
        $this->log = null;
    }
    function resetErrLog()
    {
        $this->reset();
    }
}
?>
