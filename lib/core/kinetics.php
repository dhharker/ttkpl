<?php namespace ttkpl;

/*
* Copyright 2008-2011 David Harker
*
* Licensed under the EUPL, Version 1.1 or – as soon they
  will be approved by the European Commission - subsequent
  versions of the EUPL (the "Licence");
* You may not use this work except in compliance with the
  Licence.
* You may obtain a copy of the Licence at:
*
* http://ec.europa.eu/idabc/eupl
*
* Unless required by applicable law or agreed to in
  writing, software distributed under the Licence is
  distributed on an "AS IS" basis,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
  express or implied.
* See the Licence for the specific language governing
  permissions and limitations under the Licence.
*/


/**
 * @author David Harker david.harker@york.ac.uk
 */
class kinetics {

    public $Ea = NULL;
    public $F = NULL;
    public $desc = "Undescribed kinetic parameters!";

    const GAS_CONSTANT = 8.314472;


    public function __construct ($Ea, $F, $desc) {
        $this->Ea = scalarFactory::makeKilojoulesPerMole ($Ea);
        $this->Ea->desc = "Energy of activation";
        $this->F = scalarFactory::makeSeconds ($F);
        $this->F->desc = "Pre-exponential factor";
        $this->desc = $desc;
    }
    public function getRate (scalar $T) {
        $dbgWas = '';
        if ($T->intName == 'DEG_K_ABS') {
            $Tkelvin = $T->value;
            $dbgWas = 'K';
        }
        elseif ($T->intName == 'DEG_C_ABS') {
            $ks = scalarFactory::makeKelvin ($T);
            $Tkelvin = $ks->value;
            $dbgWas = 'C';
        }
        else
            return FALSE;

        if ($Tkelvin == 0) {
            debug ("Ruh-roh! Was $dbgWas");
            debug ("Error: only temperatures above absolute zero are supported.");
            die();
        }
        $RoR = $this->F->value * exp ((-$this->Ea->value)/(self::GAS_CONSTANT * $Tkelvin));
        return scalarFactory::makeMolesPerSecond ($RoR); // i.e. k_T
    }
    public function getTempAtRate (scalar $k) {
        $Tkelvin = (-$this->Ea->value/self::GAS_CONSTANT) / (log ($k->value) - log ($this->F->value));
        return scalarFactory::makeKelvin ($Tkelvin);
    }

}


class dnaHistogram extends histogram {

    function numBonds () {
        $this->numBonds = 0;
        $this->bondsInBin = array();
        foreach ($this->bins as $numBonds => $numFragments) {
            $this->bondsInBin[$numBonds] = $numBonds * $numFragments;
            $this->numBonds += $this->bondsInBin[$numBonds];
        }
        return $this->numBonds;
    }

}



?>
