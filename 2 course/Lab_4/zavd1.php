<?php
echo '<form>
        Input string: <input name="s"><br>
        <input type="submit" value="Count">
      </form>';

if(isset($_GET['s'])) {
    $s = $_GET['s'];

    $plus = substr_count($s, "+");
    $minus = substr_count($s, "-");
    $equal = substr_count($s, "=");

    $total = $plus + $minus + $equal;

    echo "Result: ".$total;
}
?>
