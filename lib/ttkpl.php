<?php
/* 
 * This file includes all the various bits of the library and provides a point of entry.
 * Include this file in your app to use the library.
 *
 * @todo make somewhere for all the frontend chainable magic glue to go
 * @todo rewrite maths to use arbitrary precision
 * @todo write said glue
 */

require_once 'core/misc_base.php';
require_once 'core/scalars.php';
require_once 'core/datasource_abstraction.php';
require_once 'core/datatype_abstraction.php';
require_once 'core/math_abstraction.php';
require_once 'core/kinetics.php';
require_once 'core/time_and_space.php';
require_once 'core/thermal_model.php';
require_once 'core/thermal_age.php';

require_once 'datasource/bintanja_import_normalised.php';
require_once 'datasource/pmip_import_normalised.php';

require_once ("external/PHP_GNUPlot.php");

require_once 'reporting/graphs.php';
require_once 'reporting/report.php';


?>
