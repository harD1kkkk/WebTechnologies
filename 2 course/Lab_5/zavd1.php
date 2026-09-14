<?php

echo '<form method="get">
Enter numbers: <br>
<input name="nums">
<br><input type="submit" value="Find index">
</form>';

if(isset($_GET['nums'])){

    $arr = explode(" ", $_GET['nums']);

    $min = null;
    $index = -1;

    foreach($arr as $i => $value){

        $value = intval($value);

        if($value % 2 != 0){
            if($min === null || $value < $min){
                $min = $value;
                $index = $i;
            }
        }

    }

    if($index != -1)
        echo "Index of smallest odd element: ".$index;
    else
        echo "No odd numbers found";

}
?>
