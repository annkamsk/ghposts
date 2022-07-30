<?php
function info(string ...$expressions): void {
    echo "\n";
    foreach ($expressions as $exp) {
        $str = is_string($exp) ? $exp : var_dump($exp);
        echo $str;
    }
    echo "\n";
}

?>