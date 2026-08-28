<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Support
{
    public static function usernameFor($domain, $panel)
    {
        $label = strtolower((string) $domain);
        $label = preg_replace('/^www\./', '', $label);
        $parts = explode('.', $label);
        $label = preg_replace('/[^a-z0-9]/', '', $parts[0]);

        if ($label === '' || !preg_match('/^[a-z]/', $label)) {
            $label = 'u' . $label;
        }

        $max = $panel === 'cpanel' ? 8 : 16;
        if (strlen($label) > $max) {
            $label = substr($label, 0, $max);
        }

        $min = $panel === 'cpanel' ? 6 : 8;
        while (strlen($label) < $min) {
            $label .= chr(random_int(97, 122));
        }

        // Sondaki birkac karakteri rastgeleleyerek ayni domainin cakismasini onle.
        $suffixLength = $panel === 'cpanel' ? 3 : 4;
        $keep         = max(1, strlen($label) - $suffixLength);
        $label        = substr($label, 0, $keep);
        for ($i = 0; $i < $suffixLength; $i++) {
            $label .= chr(random_int(97, 122));
        }

        return substr($label, 0, $max);
    }

    public static function password($length = 14)
    {
        if ($length < 4) {
            $length = 4;
        }

        $lower  = 'abcdefghijkmnpqrstuvwxyz';
        $upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $digit  = '23456789';
        $symbol = '!.#%*+=?@_-';

        $chars = array(
            $lower[random_int(0, strlen($lower) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
            $digit[random_int(0, strlen($digit) - 1)],
            $symbol[random_int(0, strlen($symbol) - 1)],
        );

        $pool = $lower . $upper . $digit . $symbol;
        for ($i = count($chars); $i < $length; $i++) {
            $chars[] = $pool[random_int(0, strlen($pool) - 1)];
        }

        shuffle($chars);
        return implode('', $chars);
    }

    public static function domainKey($domain)
    {
        $domain = rtrim(trim((string) $domain), '.');
        return function_exists('mb_strtolower') ? mb_strtolower($domain, 'UTF-8') : strtolower($domain);
    }
}
