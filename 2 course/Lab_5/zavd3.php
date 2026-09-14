<?php

$students = [

["surname"=>"Ivanenko","physics"=>5,"math"=>4,"inf"=>5],
["surname"=>"Petrenko","physics"=>3,"math"=>4,"inf"=>4],
["surname"=>"Shevchenko","physics"=>4,"math"=>5,"inf"=>5],
["surname"=>"Bondar","physics"=>3,"math"=>3,"inf"=>4]

];

$count = 0;

foreach($students as $st){

    if($st["physics"]>3 && $st["math"]>3 && $st["inf"]>3){
        $count++;
    }

}

echo "Number of students without grade 3: ".$count;

?>
