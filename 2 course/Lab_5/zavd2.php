<?php

$n = 3;

$matrix = [
    [1,2,3],
    [4,5,6],
    [7,8,9]
];

echo "Original matrix:<br>";

for($i=0;$i<$n;$i++){
    for($j=0;$j<$n;$j++){
        echo $matrix[$i][$j]." ";
    }
    echo "<br>";
}

for($i=0;$i<$n;$i++){
    for($j=$i+1;$j<$n;$j++){

        $temp = $matrix[$i][$j];
        $matrix[$i][$j] = $matrix[$j][$i];
        $matrix[$j][$i] = $temp;

    }
}

echo "<br>Transposed matrix:<br>";

for($i=0;$i<$n;$i++){
    for($j=0;$j<$n;$j++){
        echo $matrix[$i][$j]." ";
    }
    echo "<br>";
}

/*
Пояснення індексів:
i — номер рядка
j — номер стовпця

Елемент матриці:
matrix[i][j]

При транспонуванні:
matrix[i][j] ↔ matrix[j][i]
міняємо їх місцями.
*/
?>
