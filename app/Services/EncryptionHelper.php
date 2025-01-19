<?php

namespace App\Services;

class EncryptionHelper
{
    public function encrypt($data, $salt, $iv)
    {
        $cipher = 'AES-256-CBC';
        $key = hash('sha256', $salt, true);
        return openssl_encrypt($data, $cipher, $key, 0, $iv);
    }

    public function decrypt($data, $salt, $iv)
    {
        $cipher = 'AES-256-CBC';
        $key = hash('sha256', $salt, true);
        return openssl_decrypt($data, $cipher, $key, 0, $iv);
    }
}

