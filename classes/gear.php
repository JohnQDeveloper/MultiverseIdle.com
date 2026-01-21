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

        function GetAllItemsByOwner($owner_id) {
            $gear_records = $this->DAL->r("SELECT * FROM gear WHERE owner_id=:owner_id ORDER BY favorite DESC, created_at DESC", [
                ':owner_id' => $owner_id
            ]);

            $items = [];
            if ($gear_records) {
                foreach ($gear_records as $record) {
                    $item = json_decode($record['details'], true);
                    $item['id'] = $record['id'];
                    $item['name'] = $record['name'];
                    $item['market_price'] = $record['market_price'];
                    $item['owner_id'] = $record['owner_id'];
                    $item['favorite'] = $record['favorite'] ?? 0;
                    $item['created_at'] = $record['created_at'];
                    $items[] = $item;
                }
            }
            return $items;
        }

        function DestroyItem($gear_id, $owner_id) {
            $this->DAL->w("DELETE FROM gear WHERE id=:id AND owner_id=:owner_id", [
                ':id' => $gear_id,
                ':owner_id' => $owner_id
            ]);
            return $this->DAL->rows_affected() > 0;
        }

        function ToggleFavorite($gear_id, $owner_id) {
            $this->DAL->w("UPDATE gear SET favorite = NOT COALESCE(favorite, 0) WHERE id=:id AND owner_id=:owner_id", [
                ':id' => $gear_id,
                ':owner_id' => $owner_id
            ]);
            return $this->DAL->rows_affected() > 0;
        }

        function GetFavoriteItemsByOwnerAndSlot($owner_id, $slot) {
            $gear_records = $this->DAL->r("SELECT * FROM gear WHERE owner_id=:owner_id AND favorite=1 ORDER BY created_at DESC", [
                ':owner_id' => $owner_id
            ]);

            $items = [];
            if ($gear_records) {
                foreach ($gear_records as $record) {
                    $item = json_decode($record['details'], true);
                    if (isset($item['slot']) && $item['slot'] === $slot) {
                        $item['id'] = $record['id'];
                        $item['name'] = $record['name'];
                        $item['market_price'] = $record['market_price'];
                        $item['owner_id'] = $record['owner_id'];
                        $items[] = $item;
                    }
                }
            }
            return $items;
        }

        function VerifyOwnership($gear_id, $owner_id) {
            $gear_record = $this->DAL->r("SELECT id FROM gear WHERE id=:id AND owner_id=:owner_id", [
                ':id' => $gear_id,
                ':owner_id' => $owner_id
            ]);
            return !empty($gear_record);
        }

    }
