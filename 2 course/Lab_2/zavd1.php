<?php

$sum = 0;

for ($n = 1; $n <= 10; $n++) {

    $product = 1;

    for ($k = 1; $k <= $n; $k++) {
        $product *= 1 / ($k * $k);
    }

    $root = pow($product, 1 / $n);

    $sum += sin($product) / ($n + $root);
}

echo "Result: " . $sum;

?>
