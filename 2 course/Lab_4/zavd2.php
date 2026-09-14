<?php
echo '<form>
        Input string: <input name="s"><br>
        Input k: <input name="k"><br>
        <input type="submit" value="Find">
      </form>';

if(isset($_GET['s']) && isset($_GET['k'])) {
    $s = $_GET['s'];
    $k = intval($_GET['k']);

    $count = 0;
    $position = -1;

    for($i = $k; $i < strlen($s); $i++) {
        if($s[$i] == ",") {
            $count++;
            if($count == 2) {
                $position = $i + 1;
                break;
            }
        }
    }

    if($position == -1) {
        echo "Result: not found";
    } else {
        echo "Result: ".$position;
    }
}
?>
