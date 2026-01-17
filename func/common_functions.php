<?php

    function ActiveUsers() {
        global $DAL;

        // Must interact every 3 days to be marked as active
        return $DAL->r("SELECT user_id  FROM characters WHERE last_save > DATE_SUB(NOW(), INTERVAL 72 HOUR)");
    }
