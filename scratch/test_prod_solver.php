<?php
require_once dirname(__DIR__) . '/PuzzleSolver.php';

$text = file_get_contents(dirname(__DIR__) . '/fragments.txt');
$fragments = preg_split('/\s+/', trim($text));

$solver = new PuzzleSolver($fragments, 3.0);
$res = $solver->solve();

echo "Success: " . ($res['success'] ? 'YES' : 'NO') . "\n";
echo "Total Input Fragments: " . $res['total_input_fragments'] . "\n";
echo "Max Fragments: " . $res['max_fragments'] . "\n";
echo "Total Digits: " . $res['total_digits'] . "\n";
echo "Execution Time: " . $res['execution_time_ms'] . " ms\n";
echo "Puzzle Result String:\n" . $res['puzzle_string'] . "\n";
