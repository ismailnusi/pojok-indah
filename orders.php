<?php
// Alihkan ke halaman tracking utama (track.php) dengan query yang sama.
$qs = (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '') ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: track.php' . $qs);
exit;
