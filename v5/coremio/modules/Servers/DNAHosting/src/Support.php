<?php
    namespace WISECP\Modules\Servers\DNAHosting;

    defined("CORE_FOLDER") or exit("You can not get in here!");

    class Support
    {
        /**
         * A login both panels accept: starts with a letter, [a-z0-9] only, and the last
         * characters randomised so two orders for the same domain do not collide.
         * cPanel allows 8 characters at most for a reseller-created account; Plesk 16.
         */
        public static function usernameFor(string $domain, string $panel): string
        {
            $label = preg_replace('/^www\./', '', strtolower($domain));
            $label = preg_replace('/[^a-z0-9]/', '', explode('.', $label)[0]);

            if ($label === '' || !preg_match('/^[a-z]/', $label)) $label = 'u' . $label;

            $max = $panel === 'cpanel' ? 8 : 16;
            $min = $panel === 'cpanel' ? 6 : 8;

            $label = substr($label, 0, $max);
            while (strlen($label) < $min) $label .= chr(random_int(97, 122));

            $suffix = $panel === 'cpanel' ? 3 : 4;
            $label  = substr($label, 0, max(1, strlen($label) - $suffix));
            for ($i = 0; $i < $suffix; $i++) $label .= chr(random_int(97, 122));

            return substr($label, 0, $max);
        }

        /** A password with every character class both panels' strength checks ask for. */
        public static function password(int $length = 14): string
        {
            $length = max(4, $length);
            $lower  = 'abcdefghijkmnpqrstuvwxyz';
            $upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            $digit  = '23456789';
            $symbol = '!.#%*+=?@_-';

            $pick  = fn(string $set): string => $set[random_int(0, strlen($set) - 1)];
            $chars = [$pick($lower), $pick($upper), $pick($digit), $pick($symbol)];

            $pool = $lower . $upper . $digit . $symbol;
            for ($i = count($chars); $i < $length; $i++) $chars[] = $pick($pool);

            // shuffle() is not a CSPRNG; a Fisher-Yates pass with random_int is.
            for ($i = count($chars) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
            }

            return implode('', $chars);
        }

        /**
         * Comparison and lookup key for a domain: trimmed, lower case, punycode.
         *
         * Plesk names the subscription after the ASCII form of the domain while the
         * service keeps the Unicode form; without the same conversion here an IDN order
         * could never be found again on Plesk.
         */
        public static function domainKey(string $domain): string
        {
            $domain = rtrim(trim($domain), '.');
            if ($domain === '') return '';

            $lower = mb_strtolower($domain, 'UTF-8');

            if (function_exists('idn_to_ascii') && preg_match('/[^\x20-\x7f]/', $lower) === 1) {
                $ascii = idn_to_ascii($lower, 0, INTL_IDNA_VARIANT_UTS46);
                if (is_string($ascii) && $ascii !== '') return $ascii;
            }

            return $lower;
        }
    }
