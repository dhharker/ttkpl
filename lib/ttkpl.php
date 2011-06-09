<?php
/* 
 * This file includes all the various bits of the library and provides a point of entry.
 * Include this file in your app to use the library.
 *
 * @todo make somewhere for all the frontend chainable magic glue to go
 * @todo rewrite maths to use arbitrary precision
 * @todo write said glue
 */

define ('TTKPL_PATH', realpath (dirname (__FILE__) . '/../') . '/');

echo "Path: " . TTKPL_PATH . "\n";

// the abstractions on which the model is built
require_once 'core/logging.php';
require_once 'core/misc_base.php';
require_once 'core/scalars.php';
require_once 'core/datasource_abstraction.php';
require_once 'core/datatype_abstraction.php';
require_once 'core/math_abstraction.php';
require_once 'core/kinetics.php';
require_once 'core/time_and_space.php';
// the model
require_once 'core/thermal_model.php';
require_once 'core/thermal_age.php';
// datasources specific to the model
require_once 'datasource/bintanja_import_normalised.php';
require_once 'datasource/pmip_import_normalised.php';
// external libs
require_once 'external/PHP_GNUPlot.php';
// for generating nice reports, graphs etc. for use online or in pdfs
require_once 'reporting/graphs.php';
require_once 'reporting/report.php';
// contains funky method-chaining interface to make describing complex samples less of a headache :)
require_once 'core/magic_glue.php';

?>
