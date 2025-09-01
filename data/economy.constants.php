<?php
    # Set DAU Constant
    $DAU = $redis->get('DAU');
    if($DAU === false || $DAU < 1) {
        // Fix cache miss
        $r = $DAL->r("SELECT COUNT(*) as C FROM perpetual_characters WHERE last_save > NOW() - INTERVAL 1 DAY");
        $DAU = $r[0]['C'];
        $redis->set('DAU', $DAU);
    }
    define('DAU', $DAU);

    # Set Jobs Constants
    $JobsConstants = ["healer", "alchemist", "runeforger", "armorer"];
    foreach($JobsConstants as $JobConstant) {
        $Jobs = $redis->get('Jobs');
        if($Jobs === false || $Jobs < 1) {
            // Fix cache miss
            $r = $DAL->r("SELECT COUNT(*) as C FROM perpetual_characters WHERE last_job=? AND last_save > NOW() - INTERVAL 1 DAY", [$JobConstant]);
            $Jobs = $r[0]['C'];
            $redis->set('Jobs', $Jobs);
        }
        define(strtoupper($JobConstant), max(1, $Jobs));
    }

    $EconomicConstants = ["mithral_used", "healing_done", "runes_forged", "potions_brewed"];
    foreach($EconomicConstants as $EconomicConstant) {
        $EconomicValue = $redis->get($EconomicConstant);
        if($EconomicValue === false || $EconomicValue < 1) {
            // Fix cache miss
            $r = $DAL->r("SELECT COUNT(*) as C FROM economy_stats WHERE type=? AND created_at > NOW() - INTERVAL 1 DAY",
            [$EconomicConstant]);
            $EconomicValue = $r[0]['C'];
            $redis->set($EconomicConstant, $EconomicValue);
        }
        define(strtoupper($EconomicConstant), max(1, $EconomicValue));
    }
