<?php

    if(isset($_POST['craft'])) {
        $mithral = (int) $_POST['mithral'];
        $attempts = (int) $_POST['attempts'];
        $gear_slot = $_POST['gear-slot'];

        if($mithral < 5 || $attempts < 1 || !in_array($gear_slot, GEAR_SLOTS)) {
            echo "Invalid crafting parameters.";
            exit;

        }
        else {

            // the crafting loop
            while($attempts > 0 && $mithral > 0 && ($_SESSION['mithral'] - $mithral >= 0)) {
                $_SESSION['inventory'][] = Crafting::createItem($gear_slot, $mithral);
                $_SESSION['mithral'] -= $mithral;
                $attempts--;

                // Updating economy stats
                $DAL->w("UPDATE economy_stats SET amt = amt + ? WHERE type = 'mithral_used' ORDER BY id DESC LIMIT 1", [$mithral]);
            }
        }

    }
