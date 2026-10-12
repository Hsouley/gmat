<?php
$code = $_GET['code'] ?? '';
if (empty($code)) exit('No code');
header('Content-Type: image/svg+xml');
$bars = str_split($code);
$w = 350; $h = 80;
echo "<svg xmlns='http://www.w3.org/2000/svg' width='$w' height='$h'>";
echo "<rect width='100%' height='100%' fill='white'/>";
echo "<text x='10' y='25' font-size='14' fill='black'>$code</text>";
$x = 10;
foreach ($bars as $i => $b) {
    $bw = 8 + (ord($b) % 20);
    $fill = ($i % 2 == 0) ? 'black' : 'white';
    echo "<rect x='$x' y='40' width='$bw' height='30' fill='$fill'/>";
    $x += $bw + 2;
}
echo "</svg>";
