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
 *
 */

/**
 * This file includes all the various bits of the library and provides a point of entry.
 * Include this file in your app to use the library.
 *
 * @author David Harker david.harker@york.ac.uk
 * @todo write glue
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
// base class for file-based geo-data parsing classes (i.e. pmip2 and worldclim)
require_once 'datasource/raw_importer.class.php';
// datasources specific to the model
require_once 'datasource/bintanja_import_normalised.php';
require_once 'datasource/pmip_import_normalised.php';
require_once 'datasource/worldclim_import_normalised.php';
// external libs
require_once 'external/PHP_GNUPlot.php';
// for generating nice reports, graphs etc. for use online or in pdfs
require_once 'reporting/graphs.php';
require_once 'reporting/report.php';
// contains funky method-chaining interface to make describing complex samples less of a headache :)
require_once 'core/magic_glue.php';

?>
