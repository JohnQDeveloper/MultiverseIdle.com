<?php

    class Controls {
        public static function sanitizeString($str) {
            return filter_var($str, FILTER_SANITIZE_STRING);
        }

        public static function sanitizeInt($int) {
            return filter_var($int, FILTER_SANITIZE_NUMBER_INT);
        }

        public static function validateEmail($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        }

        public static function ResourceSelectBox() {
            $output = '<select name="resource" class="resource-box">';
            foreach (RESOURCES as $resource) {
                $output .= '<option value="' . htmlspecialchars($resource) . '">' . htmlspecialchars($resource) . '</option>';
            }
            $output .= '</select>';
            return $output;
        }
    }
