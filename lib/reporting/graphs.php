<?php namespace ttkpl;

/*
* Copyright 2008-2013 David Harker
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
 * Standardised way of drawing pretty graphs of model i/o for use in web ui and reports
 * @author David Harker david.harker@york.ac.uk
 */


class ttkplPlot {

    public $gp = NULL; // GNUPlot object
    public $d = NULL;
    public $da = array ();
    public $axes = array ('x', 'y');
    public $logAxes = array ();
    public $gridAxes = array ();
    public $axisLabels = array ();
    public $plotTypes = array ();
    public $plotCols = array ();
    public $plotExtra = array ();
    public $autoScale = true;

    function __construct ($title, $xm = null, $ym = null, $pxSize = null) {
        $this->_init ($title, $xm, $ym, $pxSize);
    }

    function _init ($title, $xm = null, $ym = null, $pxSize = null) {
        $this->gp = new GNUPlot ();
        $this->gp->setSize(($xm==null)?1.0:$xm,($ym==null)?1.0:$ym);
        $this->gp->setTitle ($title);
        if ($pxSize === null) {
            $pxSize = "1000,800";
        }
        $this->gp->pxSize = $pxSize;
    }

    function labelAxes ($xTitle='', $yTitle='', $x2Title='', $y2Title='') {
        $do = array (
            'x' => $xTitle,
            'x2' => $x2Title,
            'y' => $yTitle,
            'y2' => $y2Title,
        );
        foreach ($do as $axis => $title)
            if (strlen ($title) > 0) {
                $this->axisLabels[$axis] = $title;
                if (!in_array ($axis, $this->axes))
                    $this->axes[] = $axis;
            }
        return $this;
    }

    function setData ($title="Untitled series", $ds = 0, $axes = 'x1y1', $pt = 'lines', $cols = '1:2', $extra='') {
        $this->d[$ds] = new \ttkpl\PGData ($title);
        $this->da[$ds] = $axes;
        $this->plotTypes[$ds] = $pt;
        $this->plotCols[$ds] = $cols;
        $this->plotExtra[$ds] = $extra;
        return $this;
    }

    function addData ($x, $y, $ds = 0) {
        if (!isset ($this->d[$ds]) || !is_object ($this->d[$ds]))
            return FALSE;

        $this->d[$ds]->addDataEntry (array ($x, $y));
        return $this;
    }
    function addDataAssoc ($d, $ds = 0) {
        foreach ((array) $d as $x => $y)
            $this->d[$ds]->addDataEntry ((is_array ($y)) ? $y : array ($x, $y));
        return $this;
    }
    function sada ($d, $t, $axes = 'x1y1', $pt = 'lines', $cols = '1:2', $extra='', $ds = -1) {
        $ds = ($ds == -1) ? count ($this->d) : $ds;
        $this->setData ($t, $ds, $axes, $pt, $cols, $extra='');
        $this->addDataAssoc ($d, $ds);
        return $ds;
    }
    function setLog ($axes) {
        $this->logAxes = array_merge ($this->logAxes, (array) $axes);
        return $this;
    }
    function setGrid ($axes) {
        $this->gridAxes = array_merge ($this->gridAxes, (array) $axes);
        return $this;
    }
    function set ($toSet) {
        $this->gp->set($toSet);
        return $this;
    }
    function plot ($filename = 'untitled_ttkpl_plot', $xtn = null) {
        
        //echo "Plotting:\n";
        
        if (!!$this->autoScale)
            $this->gp->set ("autoscale");
        
        foreach ($this->gridAxes as $g)
            $this->gp->set ("grid $g");

        foreach ($this->logAxes as $l)
            $this->gp->set ("log $l");

        foreach ($this->axes as $a) {
            $this->gp->setTics ($a, 'nomirror');
            if (!in_array ($a, $this->logAxes))
                $this->gp->set ("nolog $a");
        }

        foreach ($this->axisLabels as $a => $l)
            $this->gp->setDimLabel ($a, $l);

        foreach ($this->d as $di => $ds) {
            $this->gp->plotData ($ds, $this->plotTypes[$di], $this->plotCols[$di], $this->da[$di], $this->plotExtra[$di]);
//echo "Legend: " . $ds->legend . "\n";
        }

        $this->gp->set ("key left below");
        //$this->gp->set ("size ratio 0.5");
        
        
        $this->_export ($filename,$xtn);
            

        $this->gp->close();

        return true;
    }
    
    function _export ($filename = 'untitled_ttkpl_plot', $xtn = null) {
        return self::__export($this->gp, $filename, $xtn);
    }
    
    static function __export (&$object, $filename = 'untitled_ttkpl_plot', $xtn = null) {
        if (is_array ($xtn))
            foreach ($xtn as $xn)
                $object->export($filename.'.'.$xn);
        elseif (strlen ($xtn) > 0)
            $object->export($filename.'.'.$xtn);
        else
            $object->export($filename);
    }
    

}



?>
