/* ============================================================
   Toggle Gelap/Terang — instan, tanpa reload/pindah halaman.
   - Preferensi disimpan di localStorage (persistent per browser).
   - Diterapkan langsung ke <body>, tanpa navigasi ulang.
   - Tombol admin (data-sync="1") juga menyinkronkan default
     situs di server via fetch di latar (tetap tanpa reload).
   ============================================================ */
function ppiTemaCat() {
    var terang = !!(document.body && document.body.classList.contains('terang'));
    var els = document.querySelectorAll('#themeToggle,.theme-toggle');
    for (var i = 0; i < els.length; i++) {
        var b = els[i];
        if (b.classList.contains('theme-icon-only')) {
            b.textContent = terang ? '🌙' : '☀️';
            b.setAttribute('title', terang ? 'Ganti ke mode gelap' : 'Ganti ke mode terang');
        } else {
            b.innerHTML = terang ? '🌙 Mode Gelap' : '☀️ Mode Terang';
        }
    }
    var st = document.getElementById('temaStatus');
    if (st) {
        st.textContent = terang ? 'Terang' : 'Gelap';
    }
}

function ppiTemaAwal() {
    try {
        var t = localStorage.getItem('ppi_tema');
        if (t === 'terang') {
            document.body.classList.add('terang');
        } else if (t === 'gelap') {
            document.body.classList.remove('terang');
        }
    } catch (e) {}
    ppiTemaCat();
}

function toggleTema() {
    if (!document.body) {
        return false;
    }
    var terang = !document.body.classList.contains('terang');
    document.body.classList.toggle('terang', terang);
    try {
        localStorage.setItem('ppi_tema', terang ? 'terang' : 'gelap');
    } catch (e) {}
    ppiTemaCat();
    return terang;
}

/* Dipakai form admin: toggle instan + sync server di latar. */
function temaSubmit() {
    toggleTema();
    try {
        var fd = new FormData();
        fd.append('ganti_tema', '1');
        fd.append('ajax', '1');
        fetch(window.location.pathname + window.location.search, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        }).catch(function () {});
    } catch (e) {}
    return false;
}
