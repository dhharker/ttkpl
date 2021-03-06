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

class MicroEnvironmentalRecord {

        function __construct () {

        }

        /*
            Temporal Methods & Aliases
        */

        // Temporals
        function at ($when, $desc = NULL, $event = NULL) {          $this->_setTimeContext ($when, $desc, $event, FALSE);           return $this;   }
        function by ($when, $desc = NULL, $event = NULL) {          $this->_setTimeContext ($when, $desc, $event, TRUE);            return $this;   }

        // Aliases
        function deposited ($whenOrDesc, $descOrNull = NULL) {      $this->_tAliasEvent ('DEPOSITED', $whenOrDesc, $descOrNull);    return $this;   }
        function excavated ($whenOrDesc, $descOrNull = NULL) {      $this->_tAliasEvent ('EXCAVATED', $whenOrDesc, $descOrNull);    return $this;   }
        function moved ($whenOrDesc, $descOrNull = NULL) {          $this->_tAliasEvent ('MOVED', $whenOrDesc, $descOrNull);        return $this;   }


        /*
            Datum methods & Aliases
        */

        // Datums
        function isLocated ($latOD, $lonON = NULL, $descON = NULL) {
            if ((!is_numeric ($latOD) || !is_numeric ($lonON)) && $desc = $this->_desc ($latOD) && $desc !== FALSE) {
                // ... update description only
            }
            elseif ($l = latLon::bootStrap (array ($latOD, $lonOn)) && $l !== FALSE) {
                if (strlen ($descON) > 0) {
                    // ... set description
                }
                // ... set location
            }
        }
        /**
         * @todo function isElevated
         * @todo function isShaded
         */

        
        // Interpolatable
        /**
         * @todo function thenBuried
         * @todo function nowBuried
         */


        // Aliases







        /*
            Heavy metal
        */

        function _interpolateFromToAt (EnvironmentalRecord $older, EnvironmentalRecord $younger, $at_yrsbp) {
            /*
            Assume that anything that's changed in $younger from its value in $older did so
            in a linear manner over the time difference between the two,
            */
        }

        private $tcER = array (); // array (int years bp => EnvironmentalRecord)
        private $tcDesc = array ();
        private $tcEvent = array ();
        private $tcInterp = array ();

        // convenience
        private $ctcER = NULL;
        private $ctcYbp = NULL;

        function _setTimeContext ($when, $desc = NULL, $event = NULL, $interpolatePastwards = FALSE) {
            $when = $this->_when ($when);
            if (!$when) return FALSE;
            if     (strtolower ($interpolatePastwards) == 'at') $interpolatePastwards = FALSE;
            elseif (strtolower ($interpolatePastwards) == 'by') $interpolatePastwards = TRUE;



            // ... now set context
            if (!(isset ($this->tcER[$when]) && is_a ($this->tcER[$when], 'EnvironmentalRecord')))
                $this->tcER[$when] = new EnvironmentalRecord ();

            if ($desc !== NULL) $this->tcDesc[$when] = $desc;
            if ($event !== NULL) $this->tcEvent[$when] = $event;
            if ($interp !== NULL) $this->tcInterp[$when] = $interpolatePastwards;

            $this->ctcER &= $this->tcER[$when];
            $this->ctcYbp = $when;



        }

        //function _setContextualDatum ($when

        /**
         * Takes practically anything and converts it into the number of years before 1950.
         * @param mixed palaeoTime object, bootstrappable numberic value or HR string
         * @return int years before 1950 represented by $when
         */
        function _when ($when) {
            if (is_object ($when) && is_a ($when, '\ttkpl\palaeoTime'))
                return $when->getYearsBp();
            elseif (is_numeric ($when) && $when !== FALSE && $pt = palaeoTime::bootStrap ($when) && $pt !== FALSE)
                return $pt->getYearsBp();
            elseif ($when == 'THEN')
                return $this->ctcYbp;
            elseif ($when == 'NOW')
                return (date ('Y') - 1950) * -1;

            // Strip it down to lower case letters and numbers and .
            $when = preg_replace ('/[^a-z0-9.-]/im', '', strtolower ($when));

            // Find the numeric part
            if (preg_match ("/(-?[0-9]+(?:\.[0-9]+)?)/", $when, $m) > 0)
                $years = $m[1];
            else
                return false;

            // Remove numeric part
            $when = preg_replace ("[0-9.]", '', $when);

            // Find AD, BCE etc.
            $typeYears = 'bp';
            if (preg_match ("/(ad|ce|bc|bp|ago)/i", $when, $m) > 0)
                $typeYears = $m[1];
            $typeYears = str_replace (array ('ad', 'bc'), array ('ce', 'bce'), $typeYears);

            echo "$when = $years $typeYears\n";

            // convert resulting value to int years bp
            $years = round ($years);

            switch ($typeYears) {
                case "ago":
                    return ($years - 1950) * -1; ;
                    break;
                case "ce":
                    return ($years - 1950) * -1;
                    break;
                case "bce":
                    return $years + 1950;
                    break;
                default:
                case "bp":
                    return $years;
                    break;

            }

            // return (int) years bp.

        }

        function _desc ($desc) {
            $desc = preg_replace ('/\s{2,}/', ' ', $desc);
            $desc = preg_replace ('/[^a-z0-9\s._-:\'"\[\]()]/i', '', $desc);
            $desc = trim ($desc);
            return (strlen ($desc) == 0) ? FALSE : $desc;
        }

        function _tAliasEvent ($event, $whenOrDesc, $descOrNull = NULL) {
            if ($descOrNull !== NULL)
                $this->_setTimeContext ($whenOrDesc, $descOrNull, 'DEPOSITED', FALSE);
            else
                $this->_setTimeContext ('THEN', $whenOrDesc, 'DEPOSITED', FALSE);
        }

    }


    class EnvironmentalRecord {

        private $yearsBp = NULL;
        private $burialEnvironment;
        private $shading = NULL;
//        private  = NULL;
//        private  = NULL;

    }


    /**

    EnvironmentalRecord class

        The EnvironmentalRecord class contains an instantaneous snapshot of a particular environment (e.g. burial underground in a particular location at a particular time.)

    */

    /**

    MicroEnvironmentalRecord class

        The MicroEnvironmentalRecord object contains facts, datasource references and descriptions/citations which describe what is known about the microenvironment of an archaeological sample (e.g. bone, hair, shells etc.) throughout its entire history - from deposition through excavation and curation. The facts recorded are primarily used for modelling the local temperature of the sample, though future extensions may include other factors important in particular reactions.

        Features:
            *   Supports linear, natural description of the environment over time.
            *   Supports method chaining:- a subset of methods return a reference to the object instance.

        Natural configuration pattern:

        IMPORTANT: The natural pattern of use must start with deposition and method calls which update time context (i.e. to the temporal methods) must follow in chronological order.

        $mer->[TEMPORAL1]->[DATUM1]->[DATUM2 etc.]->[TEMPORAL2]->[DATUM...

        Calling a TEMPORAL method sets the current time context of the object, this is associated with any changes made to the object's state via subsequent calls to non-temporal methods, until the next temporal method call .

        [TEMPORAL] methods:
            at (...):
                Description:
                    Instantaneous data. Any changes made after a call to at() will be reflected only /after/ the date of the call. After this date, the values set may be either constant, or scaled [interpolated] over time if they are updated by a subsequent call to by().
                Args:
                    (when, description, [event])
            by (...):
                Description:
                    Functions similarly to at() with the exception that, where sane, datum method calls made after a call to by() will be interpolated from their value in [at] the last time context.
                Args: [same as at()]
            excavated:
            deposited:
            moved:
                Description:
                    Shortcuts for at() with event=EXCAVATED or DEPOSITED etc. and, if no 'when' param given, value "THEN" given. This is not useful for the DEPOSITED alias, but is useful for excavation or subsequent change of storage conditions e.g. $...->by("2011AD")->->moved("2012AD")
                Args
                    (description)
                    (when="THEN", description)

        [DATUM] methods:



    */

