<?php
// Widget Live Chat pelanggan (floating kanan bawah).
// Opsional: set $CHAT_KODE sebelum include untuk melampirkan kode pesanan.
$CHAT_KODE = isset($CHAT_KODE) ? (string)$CHAT_KODE : '';
$CHAT_WA_TEKS = 'Halo Admin Percetakan Pojok Indah, saya butuh bantuan.'
    . ($CHAT_KODE !== '' ? ' Kode pesanan saya: ' . $CHAT_KODE . '.' : '');
$CHAT_WA = 'https://wa.me/' . wa_admin() . '?text=' . urlencode($CHAT_WA_TEKS);
?>
<button type="button" class="chat-fab" id="chatFab" title="Butuh bantuan? Chat kami">
    💬<span class="chat-fab-dot" id="chatFabDot" hidden></span>
</button>
<div class="chat-panel" id="chatPanel" data-kode="<?= htmlspecialchars($CHAT_KODE) ?>" hidden>
    <div class="chat-head">
        <div><strong>💬 Bantuan Cepat</strong><small>Admin online • fast respon</small></div>
        <button type="button" id="chatClose" title="Tutup">✕</button>
    </div>
    <div class="chat-ident" id="chatIdent">
        <p>Isi nama &amp; No. HP untuk mulai chat.</p>
        <input type="text" id="chatNama" class="form-control" placeholder="Nama kamu" maxlength="100">
        <input type="tel" id="chatHp" class="form-control" placeholder="No. WhatsApp / HP" maxlength="20">
        <button type="button" id="chatMulai" class="btn btn-primary btn-sm btn-block">Mulai Chat</button>
    </div>
    <div class="chat-body" id="chatBody" hidden></div>
    <form class="chat-foot" id="chatForm" hidden autocomplete="off">
        <input type="text" id="chatPesan" placeholder="Tulis pesan..." maxlength="1000">
        <button type="submit" class="btn btn-primary btn-sm" title="Kirim">➤</button>
    </form>
    <a class="chat-wa" href="<?= htmlspecialchars($CHAT_WA) ?>" target="_blank" rel="noopener">💬 Lanjut via WhatsApp</a>
</div>

<script>
(function () {
    var fab = document.getElementById('chatFab'),
        panel = document.getElementById('chatPanel'),
        dot = document.getElementById('chatFabDot'),
        ident = document.getElementById('chatIdent'),
        body = document.getElementById('chatBody'),
        form = document.getElementById('chatForm'),
        namaI = document.getElementById('chatNama'),
        hpI = document.getElementById('chatHp'),
        pesanI = document.getElementById('chatPesan');
    if (!fab || !panel) return;

    var me = null, lastId = 0, timer = null, seen = {};
    try { me = JSON.parse(localStorage.getItem('ppi_chat') || 'null'); } catch (e) { me = null; }
    var presetKode = panel.getAttribute('data-kode') || '';

    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function jam(w) {
        try {
            var d = new Date(String(w).replace(' ', 'T'));
            var h = d.getHours(), m = d.getMinutes();
            return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
        } catch (e) { return ''; }
    }
    function addMsg(r) {
        if (!r || seen[r.id]) return;
        seen[r.id] = 1;
        if (r.id > lastId) lastId = r.id;
        var d = document.createElement('div');
        d.className = 'chat-msg ' + (r.dari === 'admin' ? 'in' : 'out');
        var cap = r.dari === 'admin' ? 'Admin' : esc(r.nama || 'Kamu');
        if (r.kode_pesanan) cap += ' • ' + esc(r.kode_pesanan);
        d.innerHTML = '<span class="chat-who">' + cap + '</span><span class="chat-txt">' + esc(r.pesan) + '</span><span class="chat-time">' + jam(r.created_at) + '</span>';
        body.appendChild(d);
        body.scrollTop = body.scrollHeight;
        if (r.dari === 'admin' && panel.hidden) {
            dot.hidden = false;
            fab.classList.add('ring');
        }
    }
    function poll() {
        if (!me) return;
        fetch('chat_api.php?aksi=ambil&no_hp=' + encodeURIComponent(me.no_hp) + '&last=' + lastId)
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (j && j.ok && j.rows) {
                    for (var i = 0; i < j.rows.length; i++) addMsg(j.rows[i]);
                }
            })
            .catch(function () {});
    }
    function siap() {
        ident.hidden = true;
        body.hidden = false;
        form.hidden = false;
        if (!timer) timer = setInterval(poll, 5000);
        poll();
    }
    fab.addEventListener('click', function () {
        panel.hidden = !panel.hidden;
        if (!panel.hidden) {
            dot.hidden = true;
            fab.classList.remove('ring');
            if (me) siap();
        }
    });
    document.getElementById('chatClose').addEventListener('click', function () {
        panel.hidden = true;
    });
    document.getElementById('chatMulai').addEventListener('click', function () {
        var n = namaI.value.trim(), h = hpI.value.replace(/[^0-9]/g, '');
        if (n === '' || h === '') {
            alert('Isi nama dan No. HP dulu ya.');
            return;
        }
        me = { nama: n, no_hp: h };
        try { localStorage.setItem('ppi_chat', JSON.stringify(me)); } catch (e) {}
        siap();
    });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var t = pesanI.value.trim();
        if (!me || t === '') return;
        pesanI.value = '';
        var fd = new FormData();
        fd.append('nama', me.nama);
        fd.append('no_hp', me.no_hp);
        fd.append('kode', presetKode);
        fd.append('pesan', t);
        fetch('chat_api.php?aksi=kirim', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function () { setTimeout(poll, 600); })
            .catch(function () {});
    });
    if (me) {
        namaI.value = me.nama || '';
        hpI.value = me.no_hp || '';
    }
})();
</script>