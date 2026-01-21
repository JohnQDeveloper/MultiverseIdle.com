<?php

    class Gear {
        private $DAL;
        public $Data;

        public function __construct() {
            global $DAL;
            $this->DAL = $DAL;
        }

        function CreateItem($name, $gear_details) {
            // remember that 0 market price items aren't listed or < 1 I suppose.
            $this->DAL->w("INSERT INTO gear SET created_at=NOW(), name=:name, details=:details, market_price=0,
            owner_id=:owner_id", [
                ':name' => $name,
                ':details' => json_encode($gear_details),
                ':owner_id' => $_SESSION['auth_user_id'] ?? null
            ]);

            // return new gear ID
            return $this->DAL->last_insert_id();
        }

        function LoadItemByGearID($gear_id) {
            $gear_record = $this->DAL->r("SELECT * FROM gear WHERE id=:id", [
                ':id' => $gear_id
            ]);

            if ($gear_record) {
                $this->Data = json_decode($gear_record['details'], true);
                $this->Data['id'] = $gear_record['id'];
                $this->Data['market_price'] = $gear_record['market_price'];
                $this->Data['owner_id'] = $gear_record['owner_id'];
                return true;
            } else {
                return false;
            }
        }

    }
