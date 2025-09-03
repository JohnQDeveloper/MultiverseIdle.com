<?php

    class SecurityTools {
        static function AddFormCSRFToken($form = 'default') {
            echo '<input type="hidden" name="csrf-request-id" value="'.hash_hmac('sha256', $form, $_SESSION['csrf-token']).'" />';
        }

        static function VerifyCSRFToken($form = 'default') {
            $signature = hash_hmac('sha256', $form, $_SESSION['csrf-token']);
            return hash_equals($signature, $_POST['csrf-request-id']);
        }

    }
