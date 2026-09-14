<?php
$input = "abcdefghijklmnopqrstuv18340";

if (preg_match("/^abcdefghijklmnopqrstuv18340$/", $input)) {
    echo "Valid string";
} else {
    echo "Invalid string";
}
?>
