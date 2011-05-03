<?php

// application specific helpers for drrc


class drrcAppSpec {
    
    function __construct () {
        $this->temps = new temperatures ();
    }
    
    function multiForm ($req) {
        $runStart = microtime (TRUE);
        $report = "\nAPI for testing form \"multiForm\".\n";
        $report .= "DRRC version: " . DRRC_VERSION_STRING . "\n";
        $report .= "Running at " . date ('Y-m-d H:i:s') . " on " . `uname -nm` . "\n";
        log_message ('debug', $report);
        log_message ('debug', "pwd: " . `pwd`);
        $temps = $this->temps;
        
        $sds = $req['stopdate'];
        asort ($sds);
        $ttOrder = array_reverse (array_keys ($sds));
        
        $kinetics = new kinetics ($req['ea'], $req['f'], '');
        log_message ('debug', "Processing form input");
        $tts = array ();
        foreach ($ttOrder as $ttin => $tti) {
            $newTt = new temporothermal ();
            $newTt->setTempSource ($temps);
            @$newTt->desc = '' . $req['ttname'][$tti];
            if (is_numeric ($req['lat_dec'][$tti])) {
                $location = new latLon ($req['lat_dec'][$tti], $req['lon_dec'][$tti]);
                $localisingCorrections = $temps->getPalaeoTemperatureCorrections ($location);
                $newTt->setLocalisingCorrections ($localisingCorrections);
            }
            elseif (is_numeric ($req['constmat'][$tti])) {
                $storageSine = new sine ();
                $storageSine->setGenericSine (
                    scalarFactory::makeCentigradeAbs ($req['constmat'][$tti]),
                    scalarFactory::makeKelvinAnomaly ($req['constamp'][$tti]),
                    scalarFactory::makeDays (0));
                $newTt->setConstantClimate ($storageSine);
            }
            else {
                die ("can't estimate temperatures, not enough info!");
            }
            if (isset ($req['z'][$tti])) {
                $b = new burial ();
                foreach ($req['z'][$tti] as $li => $zf) {
                    $td = scalarFactory::makeThermalDiffusivity ($req['dh'][$tti][$li]);
                    $z = scalarFactory::makeMetres ($zf);
                    $l = new thermalLayer ($z, $td, '');
                    $b->addThermalLayer ($l);
                }
                $newTt->setBurial ($b);
            }
            
            if (isset ($req['vegknown'][$tti]) && $req['vegknown'][$tti] == 'known')
                $newTt->setVegetationCover (($req['vegknown'][$tti] == 'yes') ? TRUE : FALSE, TRUE);
                
            if ($ttin == 0) {
                $oldDate = new palaeoTime ($req['sampleAge']);
            }
            else {
                $oldDate = new palaeoTime ($req['stopdate'][$ttOrder[$ttin - 1]]);
            }
            $newDate = new palaeoTime ($req['stopdate'][$tti]);
            $newTt->setTimeRange ($oldDate, $newDate);
            
            $tts[] = $newTt;
        }
        log_message ('debug', "Running model:");
        $ta = new thermalAge ();
        $ta->setKinetics ($kinetics);
        foreach ($tts as $tt)
            $ta->addTemporothermal ($tt);
        log_message ('debug', " * Added temporothermals");
        $taYrs = $ta->getThermalAge ();
        log_message ('debug', " * Got thermal age.");
        if (!is_object ($taYrs)) {
            log_message ('debug', "Thermal age has had a barry: '$taYrs'");
            $rtn = array (
                'values' => array (
                    'λ' => 1,
                    '(1/λ)+1' => 1 + (1 / $ta->getLambda()),
                    'k (yr)' => -99,
                    'k (sec)' => -99,
                    'Teff' => -99,
                    'Thermal age' => -99,
                ),
                'input' => $req,
            );
        }
        else {
            $rtn = array (
                'values' => array (
                    'λ' => $ta->getLambda(),
                    '(1/λ)+1' => 1 + (1 / $ta->getLambda()),
                    'k (yr)' => $ta->getKYear (),
                    'k (sec)' => $ta->getKSec (),
                    'Teff' => scalarFactory::makeCentigradeAbs ($ta->getTeff ())->getValue(),
                    'Thermal age' => $taYrs->getValue(),
                ),
                'input' => $req,
            );
            log_message ('debug', "Returning.");
        }
        return $rtn;
        
    }
    
    function homininFossils (
        $zlat,
        $zlon,
        $zname = 'unnamed',
        $zfoundAd,
        $zanalysedAd,
        $zageBp,
        $zstorageTempC,
        $zaltM,
        burial $zb = NULL
    ) {
        $runStart = microtime (TRUE);
        $report = "\nAPI for hominin fossil spreadsheet.\n";
        $report .= "DRRC version: " . DRRC_VERSION_STRING . "\n";
        $report .= "Running at " . date ('Y-m-d H:i:s') . " on " . `uname -nm` . "\n";
        
        $temps = $this->temps;
        $ttCave = new temporothermal ();
        $ttCave->setTempSource ($temps);
        
        $location = new latLon ($zlat, $zlon);
        
        $localisingCorrections = $temps->getPalaeoTemperatureCorrections ($location);
        $ttCave->setLocalisingCorrections ($localisingCorrections);
        
        if ($zb === NULL || !is_object ($zb) || get_class ($zb) != 'burial') {
            $burial = new burial ();
            $rock = scalarFactory::makeThermalDiffusivity (0.0864);
            $rock->desc = "Generic 'rock' (Wikipedia)";
            $rockDepth = scalarFactory::makeMetres (10.0);
            $cave = new thermalLayer ($rockDepth, $rock, "Assumed value for cave samples");
            $burial->addThermalLayer ($cave);
        }
        $ttCave->setBurial ($burial);
        
        $ptF = new palaeoTime (scalarFactory::_getAdBp ($zfoundAd));
        $ptA = new palaeoTime (scalarFactory::_getAdBp ($zanalysedAd));
        $age = new palaeoTime ($zageBp);
        
        $stored = ($ptF->distanceTo ($ptA) != 0) ? TRUE : FALSE;
        
        if ($stored) {
            $ttStorage = new temporothermal ();
            $storageSine = new sine ();
            $storageSine->setGenericSine (scalarFactory::makeCentigradeAbs ($zstorageTempC), scalarFactory::makeKelvinAnomaly (0), scalarFactory::makeDays (0));
            $ttStorage->setConstantClimate ($storageSine);
            $ttStorage->setTimeRange ($ptF, $ptA);
        }
        
        $ttCave->setTimeRange ($age, $ptF);
        $depurination = new kinetics (126940, 17745329175.856213, "DNA depurination (bone)");
        
        $taC = new thermalAge ();
        $taC->setKinetics ($depurination);
        $taC->addTemporothermal ($ttCave);
        $taCYrs= $taC->getThermalAge ();
        
        
        if ($stored) {
            $taS = new thermalAge ();
            $taS->setKinetics ($depurination);
            $taS->addTemporothermal ($ttStorage);
            $taSYrs= $taS->getThermalAge ();
        }
        
        $ta = new thermalAge ();
        $ta->setKinetics ($depurination);
        $ta->addTemporothermal ($ttCave);
        if ($stored)
            $ta->addTemporothermal ($ttStorage);
        $taYrs= $ta->getThermalAge ();
        
        $report .= sprintf (
            "\nSample information:\n" .
            "\tAge/years bp:\t\t\t\t\t\t%d\n" .
            "\tName/location:\t\t\t\t\t\t\"%s\"\n" .
            "\tYear found AD:\t\t\t\t\t\t%4dAD\n" .
            "\tLocation found:\t\t\t\t\t\t%+08.4fN %+09.4fE\n" .
            "\tYear analysed AD:\t\t\t\t\t%4dAD\n" .
            "\nReaction:\n" .
            "\tDescription:\t\t\t\t\t\t%s\n" . 
            "\tActivation energy/%s:\t\t%08d\n" .
            "\tPre-exponential factor/%s:\t\t%020.7f\n" .
            "",
            $zageBp,
            $zname,
            $zfoundAd,
            $zlat,
            $zlon,
            $zanalysedAd,
            $depurination->desc,
            $depurination->Ea->unitsShort,
            $depurination->Ea->getValue (),
            $depurination->F->unitsShort,
            $depurination->F->getValue ()
            
        );
        
        $report .= sprintf (
            "\nTemporothermal environment:\n" .
            "\tDescription:\t\t\t\t\t\t%s\n" .
            "\tBurial depth, thermal diffusivity:\t%s\n" . 
            "\tEffective temperature/deg. C:\t\t%04.2f\n" .
            "\tAge/years:\t\t\t\t\t\t\t%d\n" .
            "\tThermal age/10C thermal years:\t\t%d\n" .
            "\tLambda:\t\t\t\t\t\t\t\t%011.9f\n" . 
            "\tMean fragment length (DNA):\t\t\t%01.0f\n",
            "Burial under rock",
            "$burial",
            scalarFactory::makeCentigradeAbs ($taC->teffs[0])->getValue(),
            $taC->getAge(),
            $taCYrs->getValue(),
            $taC->getLambda(),
            1 + (1 / $taC->getLambda())
        );
        
        if ($stored)
        $report .= sprintf (
            "\nTemporothermal environment:\n" .
            "\tDescription:\t\t\t\t\t\t%s\n" .
            //"\tBurial conditions: (depth, thermal diffusivity):\t%s\n" . 
            "\tEffective temperature/deg. C:\t\t%04.2f\n" .
            "\tAge/years:\t\t\t\t\t\t\t%d\n" .
            "\tThermal age/10C thermal years:\t\t%d\n" .
            "\tLambda:\t\t\t\t\t\t\t\t%011.9f\n" . 
            "\tMean fragment length (DNA):\t\t\t%01.0f\n" . 
            "",
            
            "Hypothetical constant 15C storage between excavation and analysis",
            //"none",
            scalarFactory::makeCentigradeAbs ($taS->teffs[0])->getValue(),
            $taS->getAge(),
            $taSYrs->getValue(),
            $taS->getLambda(),
            1 + (1 / $taS->getLambda())
        );
        
        $report .= sprintf (
            "\nCompound temporothermal environment (the important bit):\n" .
            "\tDescription:\t\t\t\t\t\t%s\n" .
            //"\tBurial conditions: (depth, thermal diffusivity):\t%s\n" . 
            "\tEffective temperature/deg. C:\t\t%04.2f\n" .
            "\tAge at analysis/years:\t\t\t\t%d\n" .
            "\tThermal age/10C thermal years:\t\t%d\n" .
            "\tLambda:\t\t\t\t\t\t\t\t%011.9f\n" . 
            "\tMean fragment length (DNA):\t\t\t%01.0f\n" . 
            "",
            
            "The sum of reaction over specified time and temperature range",
            //"none",
            scalarFactory::makeCentigradeAbs ($ta->getTeff ())->getValue(),
            $ta->getAge(),
            $taYrs->getValue(),
            $ta->getLambda(),
            1 + (1 / $ta->getLambda())
        );
        
        $rtn = array (
            'values' => array (
                'λ' => $ta->getLambda(),
                '(1/λ)+1' => 1 + (1 / $ta->getLambda()),
                'k (yr)' => $ta->getKYear (),
                'k (sec)' => $ta->getKSec (),
                'Teff' => scalarFactory::makeCentigradeAbs ($ta->getTeff ())->getValue(),
                'Thermal age' => $taYrs->getValue(),
                'reportFilename' => taUtils::filenameFromCrap ($zname) . '.txt',
                
            ),
            'report' => $report
        );
        $runStop = microtime (TRUE);
        
        $rtn['modelRunTimeSec'] = ($runStop - $runStart);
        
        return $rtn;
        
        
    }
    
    function europeSamples (
        $zlat,
        $zlon,
        $zname = 'unnamed',
        $zfoundAd,
        $zanalysedAd,
        $zageBp,
        $zstorageTempC,
        $zaltM,
        burial $zb = NULL
    ) {
        $runStart = microtime (TRUE);
        $report = "\nAPI for European stuff for paper spreadsheet.\n";
        $report .= "DRRC version: " . DRRC_VERSION_STRING . "\n";
        $report .= "Running at " . date ('Y-m-d H:i:s') . " on " . `uname -nm` . "\n";
        
        $temps = $this->temps;
        $ttGrave = new temporothermal ();
        $ttGrave->setTempSource ($temps);
        
        $location = new latLon ($zlat, $zlon);
        
        $localisingCorrections = $temps->getPalaeoTemperatureCorrections ($location);
        $ttGrave->setLocalisingCorrections ($localisingCorrections);
        
        if ($zb === NULL || !is_object ($zb) || get_class ($zb) != 'burial') {
            $burial = new burial ();
            $soil = scalarFactory::makeThermalDiffusivity (0.18);
            $soil->desc = "Dry, clay soil (Figure from S. Pal Arya (2001) Introduction to Micrometeorology, Academic Press: San Diego, CA.)";
            $soilDepth = scalarFactory::makeMetres (4.0);
            $grave = new thermalLayer ($soilDepth, $soil, "Hypothetical Grave");
            $burial->addThermalLayer ($grave);
        }
        $ttGrave->setBurial ($burial);
        
        $ptF = new palaeoTime (scalarFactory::_getAdBp ($zfoundAd));
        $ptA = new palaeoTime (scalarFactory::_getAdBp ($zanalysedAd));
        $age = new palaeoTime ($zageBp);
        
        $stored = ($ptF->distanceTo ($ptA) != 0) ? TRUE : FALSE;
        
        if ($stored) {
            $ttStorage = new temporothermal ();
            $storageSine = new sine ();
            $storageSine->setGenericSine (scalarFactory::makeCentigradeAbs ($zstorageTempC), scalarFactory::makeKelvinAnomaly (0), scalarFactory::makeDays (0));
            $ttStorage->setConstantClimate ($storageSine);
            $ttStorage->setTimeRange ($ptF, $ptA);
        }
        
        $ttGrave->setTimeRange ($age, $ptF);
        $depurination = new kinetics (126940, 17745329175.856213, "DNA depurination (bone)");
        
        $taC = new thermalAge ();
        $taC->setKinetics ($depurination);
        $taC->addTemporothermal ($ttGrave);
        $taCYrs= $taC->getThermalAge ();
        
        
        if ($stored) {
            $taS = new thermalAge ();
            $taS->setKinetics ($depurination);
            $taS->addTemporothermal ($ttStorage);
            $taSYrs= $taS->getThermalAge ();
        }
        
        $ta = new thermalAge ();
        $ta->setKinetics ($depurination);
        $ta->addTemporothermal ($ttGrave);
        if ($stored)
            $ta->addTemporothermal ($ttStorage);
        $taYrs= $ta->getThermalAge ();
        
        $report .= sprintf (
            "\nSample information:\n" .
            "\tAge/years bp:\t\t\t\t\t\t%d\n" .
            "\tName/location:\t\t\t\t\t\t\"%s\"\n" .
            "\tYear found AD:\t\t\t\t\t\t%4dAD\n" .
            "\tLocation found:\t\t\t\t\t\t%+08.4fN %+09.4fE\n" .
            "\tYear analysed AD:\t\t\t\t\t%4dAD\n" .
            "\nReaction:\n" .
            "\tDescription:\t\t\t\t\t\t%s\n" . 
            "\tActivation energy/%s:\t\t%08d\n" .
            "\tPre-exponential factor/%s:\t\t%020.7f\n" .
            "",
            $zageBp,
            $zname,
            $zfoundAd,
            $zlat,
            $zlon,
            $zanalysedAd,
            $depurination->desc,
            $depurination->Ea->unitsShort,
            $depurination->Ea->getValue (),
            $depurination->F->unitsShort,
            $depurination->F->getValue ()
            
        );
        
        $report .= sprintf (
            "\nTemporothermal environment:\n" .
            "\tDescription:\t\t\t\t\t\t%s\n" .
            "\tBurial depth, thermal diffusivity:\t%s\n" . 
            "\tEffective temperature/deg. C:\t\t%04.2f\n" .
            "\tAge/years:\t\t\t\t\t\t\t%d\n" .
            "\tThermal age/10C thermal years:\t\t%d\n" .
            "\tLambda:\t\t\t\t\t\t\t\t%011.9f\n" . 
            "\tMean fragment length (DNA):\t\t\t%01.0f\n",
            "Burial under 4m of dry, clay soil",
            "$burial",
            scalarFactory::makeCentigradeAbs ($taC->teffs[0])->getValue(),
            $taC->getAge(),
            $taCYrs->getValue(),
            $taC->getLambda(),
            1 + (1 / $taC->getLambda())
        );
        
        if ($stored)
        $report .= sprintf (
            "\nTemporothermal environment:\n" .
            "\tDescription:\t\t\t\t\t\t%s\n" .
            //"\tBurial conditions: (depth, thermal diffusivity):\t%s\n" . 
            "\tEffective temperature/deg. C:\t\t%04.2f\n" .
            "\tAge/years:\t\t\t\t\t\t\t%d\n" .
            "\tThermal age/10C thermal years:\t\t%d\n" .
            "\tLambda:\t\t\t\t\t\t\t\t%011.9f\n" . 
            "\tMean fragment length (DNA):\t\t\t%01.0f\n" . 
            "",
            
            "Hypothetical constant 16C storage between excavation and analysis",
            //"none",
            scalarFactory::makeCentigradeAbs ($taS->teffs[0])->getValue(),
            $taS->getAge(),
            $taSYrs->getValue(),
            $taS->getLambda(),
            1 + (1 / $taS->getLambda())
        );
        
        $report .= sprintf (
            "\nCompound temporothermal environment (the important bit):\n" .
            "\tDescription:\t\t\t\t\t\t%s\n" .
            //"\tBurial conditions: (depth, thermal diffusivity):\t%s\n" . 
            "\tEffective temperature/deg. C:\t\t%04.2f\n" .
            "\tAge at analysis/years:\t\t\t\t%d\n" .
            "\tThermal age/10C thermal years:\t\t%d\n" .
            "\tLambda:\t\t\t\t\t\t\t\t%011.9f\n" . 
            "\tMean fragment length (DNA):\t\t\t%01.0f\n" . 
            "",
            
            "The sum of reaction over specified time and temperature range",
            //"none",
            scalarFactory::makeCentigradeAbs ($ta->getTeff ())->getValue(),
            $ta->getAge(),
            $taYrs->getValue(),
            $ta->getLambda(),
            1 + (1 / $ta->getLambda())
        );
        
        $rtn = array (
            'values' => array (
                'λ' => $ta->getLambda(),
                '(1/λ)+1' => 1 + (1 / $ta->getLambda()),
                'k (yr)' => $ta->getKYear (),
                'k (sec)' => $ta->getKSec (),
                'Teff' => scalarFactory::makeCentigradeAbs ($ta->getTeff ())->getValue(),
                'Thermal age' => $taYrs->getValue(),
                'reportFilename' => taUtils::filenameFromCrap ($zname) . '.txt',
                
            ),
            'report' => $report
        );
        $runStop = microtime (TRUE);
        
        $rtn['modelRunTimeSec'] = ($runStop - $runStart);
        
        return $rtn;
        
        
    }
    
}



?>