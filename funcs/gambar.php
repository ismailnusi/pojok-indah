<?php
function get_gambar_produk($produk_id, $kategori_id) {
    $map = array(
        1 => 'banner.svg',
        2 => 'kartu_nama.svg',
        3 => 'brosur.svg',
        4 => 'stiker.svg',
        5 => 'kaos.svg',
        6 => 'mug.svg',
        7 => 'kalender.svg',
        8 => 'spanduk.svg',
        9 => 'totebag.svg',
        10 => 'neonbox.svg'
    );
    return isset($map[$kategori_id]) ? $map[$kategori_id] : 'default.svg';
}

// Daftar semua gambar produk untuk galeri (maks 3).
// Prioritas: galeri upload admin, fallback ke 1 gambar otomatis.
function get_gambar_list($produk) {
    $out = array();
    if (!empty($produk['id'])) {
        foreach (get_gallery((int)$produk['id']) as $g) {
            $out[] = $g['url'];
        }
    }
    if (empty($out)) {
        $out[] = get_gambar_src($produk);
    }
    return array_values(array_unique($out));
}

function get_gambar_src($produk) {
    // 1) Gambar upload admin (folder assets/img/produk) selalu diprioritaskan
    if (!empty($produk['gambar'])) {
        $g = basename((string)$produk['gambar']);
        if ($g !== '' && is_file(__DIR__ . '/../assets/img/produk/' . $g)) {
            return 'assets/img/produk/' . $g;
        }
        // 2) Kompatibilitas: file lama yang tersimpan langsung di assets/img
        if ($g !== '' && is_file(__DIR__ . '/../assets/img/' . $g)) {
            return 'assets/img/' . $g;
        }
    }

    // 3) Fallback keyword. Urutan penting: yang SPESIFIK dulu,
    // karena 'x-banner' dan 'roll banner' juga mengandung kata 'banner'.
    $ket = strtolower((string)($produk['nama_produk'] ?? ''));

    $maps = array(
        'x-banner'   => 'x_banner.svg',
        'roll'       => 'roll_banner.svg',
        'backdrop'   => 'banner.svg',
        'kartu nama' => 'kartu_nama.svg',
        'brosur'     => 'brosur.svg',
        'flyer'      => 'flyer.svg',
        'leaflet'    => 'flyer.svg',
        'stiker'     => 'stiker.svg',
        'kaos'       => 'kaos.svg',
        'sablon'     => 'kaos.svg',
        'mug'        => 'mug.svg',
        'kalender'   => 'kalender.svg',
        'spanduk'    => 'spanduk.svg',
        'totebag'    => 'totebag.svg',
        'tote bag'   => 'totebag.svg',
        'pin'        => 'pin.svg',
        'lencana'    => 'pin.svg',
        'neon'       => 'neonbox.svg',
        'huruf'      => 'huruf.svg',
        'akrilik'    => 'huruf.svg',
        'banner'     => 'banner.svg',
    );

    foreach ($maps as $kata => $file) {
        if ($kata !== '' && strpos($ket, $kata) !== false) {
            return 'assets/img/' . $file;
        }
    }
    return 'assets/img/default.svg';
}