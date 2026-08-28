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

    /**
     * Alan adinin karsilastirma ve arama anahtari: kirpilmis, kucuk harfli, punycode.
     *
     * Punycode kismi zorunlu. Cekirdek createAccount()'a ASCII'ye cevrilmis domaini
     * veriyor — orders.php:2838-2840 idn_to_ascii($orderopt["domain"], 0,
     * INTL_IDNA_VARIANT_UTS46) diyor — ama sonucu $orderopt["domain"] icine geri
     * YAZMIYOR. Yani Plesk'te abonelik "xn--..." adiyla acilirken sonraki her islem
     * domaini options.domain'den Unicode haliyle okuyor. Ayni cevrim burada
     * yapilmazsa findWebspace() bir IDN siparisini bir daha asla bulamaz ve askiya
     * alma, askidan indirme, sifre degistirme, paket degistirme, kullanim ve
     * sonlandirma o hizmet icin kalici olarak coker. cPanel etkilenmez; o config.user
     * uzerinden calisir.
     */
    public static function domainKey($domain)
    {
        $domain = rtrim(trim((string) $domain), '.');
        if ($domain === '') {
            return '';
        }

        $lower = function_exists('mb_strtolower') ? mb_strtolower($domain, 'UTF-8') : strtolower($domain);

        // Yalnizca ASCII disi girdi cevrilir: zaten ASCII olan (punycode dahil) adlar
        // oldugu gibi birakilir, boylece mevcut davranis birebir korunur.
        if (function_exists('idn_to_ascii') && preg_match('/[^\x20-\x7f]/', $lower) === 1) {
            $variant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0;
            $ascii   = idn_to_ascii($lower, 0, $variant);
            if (is_string($ascii) && $ascii !== '') {
                return $ascii;
            }
        }

        return $lower;
    }

    public static function formatBytes($bytes)
    {
        $bytes = (float) $bytes;
        if ($bytes <= 0) {
            return '∞';
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $index = 0;
        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        $rounded = round($bytes, 1);
        $text    = ($rounded == (int) $rounded) ? (string) (int) $rounded : (string) $rounded;
        return $text . ' ' . $units[$index];
    }

    public static function percent($used, $limit)
    {
        $used  = (float) $used;
        $limit = (float) $limit;
        if ($limit <= 0 || $used <= 0) {
            return 0;
        }
        $percent = (int) round($used / $limit * 100);
        return $percent > 100 ? 100 : $percent;
    }
}
