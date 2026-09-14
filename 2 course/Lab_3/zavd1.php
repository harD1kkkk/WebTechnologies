<?php
echo '<form method="get">
        k: <input name="k" step="any"><br>
        <input type="submit" value="Calculate">
      </form>';

if (isset($_GET['k'])) {

    $k = floatval($_GET['k']);

    function factorial($n) {
        if ($n == 0) return 1;
        $res = 1;
        for ($i = 1; $i <= $n; $i++) {
            $res *= $i;
        }
        return $res;
    }

    function z($x) {
        if (abs($x) >= 1) {
            return (cos($x) + 1) / (pow(sin($x), 2) + exp($x));
        } else {
            $sum = 0;
            for ($j = 0; $j <= 7; $j++) {
                $sum += pow(-1, $j) * pow($x, $j) / factorial($j);
            }
            return (1 / exp($x)) * $sum;
        }
    }

    $z1 = z(2*$k + 1);
    $z2 = z(2*$k - 1);
    $z3 = z($k);

    $result = $z1 - pow($z2, 2) + sqrt($z3);

    echo "<br>Result: $result";
}
?>
