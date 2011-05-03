<?php
/* 
 * 
 */

require_once ("../external/PHP_GNUPlot.php");

class drrcPlot {

    public $gp = NULL;
    public $d = NULL;
    public $da = array ();
    public $axes = array ('x', 'y');
    public $logAxes = array ();
    public $gridAxes = array ();
    public $axisLabels = array ();
    public $plotTypes = array ();
    public $plotCols = array ();
    public $plotExtra = array ();

    function __construct ($title) {
        $this->_init ($title);
    }

    function _init ($title) {
        $this->gp = new GNUPlot ();
        $this->gp->setSize( 1.0, 1.0 );
        $this->gp->setTitle ($title);
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
    }

    function setData ($title="Untitled series", $ds = 0, $axes = 'x1y1', $pt = 'lines', $cols = '1:2', $extra='') {
        $this->d[$ds] = new PGData ($title);
        $this->da[$ds] = $axes;
        $this->plotTypes[$ds] = $pt;
        $this->plotCols[$ds] = $cols;
        $this->plotExtra[$ds] = $extra;
    }

    function addData ($x, $y, $ds = 0) {
        if (!isset ($this->d[$ds]) || !is_object ($this->d[$ds]))
            return FALSE;

        $this->d[$ds]->addDataEntry (array ($x, $y));
    }
    function addDataAssoc ($d, $ds = 0) {
        foreach ((array) $d as $x => $y)
            $this->d[$ds]->addDataEntry ((is_array ($y)) ? $y : array ($x, $y));
    }
    function sada ($d, $t, $axes = 'x1y1', $pt = 'lines', $cols = '1:2', $extra='', $ds = -1) {
        $ds = ($ds == -1) ? count ($this->d) : $ds;
        $this->setData ($t, $ds, $axes, $pt, $cols, $extra='');
        $this->addDataAssoc ($d, $ds);
        return $ds;
    }
    function setLog ($axes) {
        $this->logAxes = array_merge ($this->logAxes, (array) $axes);
    }
    function setGrid ($axes) {
        $this->gridAxes = array_merge ($this->gridAxes, (array) $axes);
    }

    function plot ($filename = 'untitled_drrc_plot.png') {
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

        foreach ($this->d as $di => $ds)
            $this->gp->plotData ($ds, $this->plotTypes[$di], $this->plotCols[$di], $this->da[$di]);

        $this->gp->set ("key left below");
        //$this->gp->set ("size ratio 0.5");
        $this->gp->export($filename);

        $this->gp->close();
    }

}



?>
