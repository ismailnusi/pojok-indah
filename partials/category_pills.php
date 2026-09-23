<?php
// Ambil kategori dari database
$kategori = q("SELECT * FROM kategori ORDER BY id ASC");
?>

<ul class="category-pills">
    <li><a href="<?= base_url() ?>/index.php" class="<?= !isset($_GET['kat']) ? 'active' : '' ?>">Semua</a></li>
    <?php if ($kategori): while ($k = $kategori->fetch_assoc()): ?>
        <li>
            <a href="?kat=<?= (int)$k['id'] ?>" class="<?= (isset($_GET['kat']) && $_GET['kat'] == $k['id']) ? 'active' : '' ?>">
                <?= htmlspecialchars($k['icon'] ?? '') ?> <?= htmlspecialchars($k['nama_kategori']) ?>
            </a>
        </li>
    <?php endwhile; endif; ?>
</ul>