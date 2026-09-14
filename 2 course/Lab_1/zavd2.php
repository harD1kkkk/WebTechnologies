<?php

$x = floatval(readline("Введіть x: "));
$R = floatval(readline("Введіть R: "));

if ($x <= -6 - $R) {
    $y = 0;
}
elseif ($x > -6 - $R && $x <= -6) {
    $y = -sqrt($R * $R - pow($x + 6, 2));
}
elseif ($x > -6 && $x <= -$R) {
    $y = ($R / (6 - $R)) * ($x + 6) - $R;
}
elseif ($x > -$R && $x <= 0) {
    $y = sqrt($R * $R - $x * $x);
}
elseif ($x > 0 && $x <= 3) {
    $y = $R - ($R / 3) * $x;
}
elseif ($x > 3 && $x <= 9) {
    $y = ($R / 6) * ($x - 3);
}
else {
    echo "x поза межами графіка";
    exit;
}

echo "y = $y<br>";
?>
