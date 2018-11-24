<?php
$arr = [];
for($i=0; $i<100; $i++) {
    $arr[] = $i;
}

function useYield($array)
{
    yield from $array;
}

foreach(useYield($arr) as $item) {
    echo $item, PHP_EOL;
}
