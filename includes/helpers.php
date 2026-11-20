<?php
declare(strict_types=1);

/** Escape output HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Format angka ribuan gaya Indonesia. */
function angka(int|float $value): string
{
    return number_format((float) $value, 0, ',', '.');
}

const BULAN_ID = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const HARI_ID = [
    1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu',
];

/** 2026-11-20 -> 20 Nov 2026 */
function tgl_id(?string $date): string
{
    if ($date === null || $date === '') {
        return '-';
    }

    $time = strtotime($date);
    if ($time === false) {
        return '-';
    }

    return date('j', $time) . ' ' . substr(BULAN_ID[(int) date('n', $time)], 0, 3) . ' ' . date('Y', $time);
}

/** Contoh: Jumat, 20 November 2026 */
function hari_ini(): string
{
    $now = time();

    return HARI_ID[(int) date('N', $now)] . ', ' . date('j', $now) . ' '
        . BULAN_ID[(int) date('n', $now)] . ' ' . date('Y', $now);
}

/** Lencana status peminjaman. */
function badge_status(string $status): string
{
    $label = [
        'menunggu'  => 'Menunggu',
        'disetujui' => 'Disetujui',
        'ditolak'   => 'Ditolak',
        'selesai'   => 'Selesai',
    ][$status] ?? ucfirst($status);

    $class = preg_match('/^[a-z]+$/', $status) ? $status : 'selesai';

    return '<span class="badge badge-' . $class . '">' . e($label) . '</span>';
}

/** Inisial dari nama, maksimal dua huruf. */
function inisial(string $nama): string
{
    $parts = preg_split('/\s+/', trim($nama)) ?: [];
    $result = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $result .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $result !== '' ? $result : '?';
}

/** Persentase aman (hindari bagi nol). */
function persen(int|float $bagian, int|float $total): int
{
    if ($total <= 0) {
        return 0;
    }

    return (int) round(($bagian / $total) * 100);
}

/** Ikon SVG sederhana (gaya outline). */
function icon(string $name): string
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'box'       => '<path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
        'swap'      => '<path d="M17 3l4 4-4 4"/><path d="M21 7H8a5 5 0 0 0-5 5"/><path d="M7 21l-4-4 4-4"/><path d="M3 17h13a5 5 0 0 0 5-5"/>',
        'history'   => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/>',
        'monitor'   => '<rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/><path d="m6 12 3-3 3 2 4-4"/>',
        'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check'     => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>',
        'alert'     => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'calendar'  => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'menu'      => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'x'         => '<path d="M18 6 6 18M6 6l12 12"/>',
        'layers'    => '<path d="m12 2 10 5-10 5L2 7l10-5Z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/>',
        'wrench'    => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4 2.6-2.6Z"/>',
    ];

    $inner = $paths[$name] ?? '';

    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $inner . '</svg>';
}
