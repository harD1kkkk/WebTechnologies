<?php
$alpha = 0.3;

$z1 = 2 * pow(sin(3 * M_PI - 2 * $alpha), 2)
        * pow(cos(5 * M_PI + 2 * $alpha), 2);

$z2 = 0.25 - 0.25 * sin(5 * M_PI / 2 - 8 * $alpha);

echo "z1 = " . $z1 . "<br>";
echo "z2 = " . $z2 . "<br>";
?>
