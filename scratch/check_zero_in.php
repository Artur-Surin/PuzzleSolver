<?php
$text = file_get_contents(dirname(__DIR__) . '/fragments.txt');
$frags = preg_split('/\s+/', trim($text));

$in = [];
$out = [];
foreach ($frags as $f) {
    $p = substr($f, 0, 2);
    $s = substr($f, -2);
    $in[$s] = ($in[$s] ?? 0) + 1;
    $out[$p] = ($out[$p] ?? 0) + 1;
}

echo "Fragments whose prefix has in-degree == 0:\n";
foreach ($frags as $i => $f) {
    $p = substr($f, 0, 2);
    if (!isset($in[$p]) || $in[$p] === 0) {
        echo "Index $i: $f (Prefix $p, Out-degree " . ($out[$p] ?? 0) . ")\n";
    }
}
