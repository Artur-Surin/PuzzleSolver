<?php

class FastBeamSolver {
    private array $fragments;
    private int $n;
    private array $prefInt = [];
    private array $suffInt = [];
    private array $adj = [];

    public function __construct(array $rawFragments) {
        $this->fragments = array_values(array_filter(
            array_map('trim', $rawFragments),
            fn($s) => strlen($s) === 6 && ctype_digit($s)
        ));
        $this->n = count($this->fragments);

        for ($i = 0; $i < $this->n; $i++) {
            $f = $this->fragments[$i];
            $this->prefInt[$i] = (int)substr($f, 0, 2);
            $this->suffInt[$i] = (int)substr($f, -2);
            $this->adj[$i] = [];
        }

        for ($i = 0; $i < $this->n; $i++) {
            for ($j = 0; $j < $this->n; $j++) {
                if ($i !== $j && $this->suffInt[$i] === $this->prefInt[$j]) {
                    $this->adj[$i][] = $j;
                }
            }
        }
    }

    public function solve(): array {
        $startTime = microtime(true);

        $bestPath = [];
        $bestStr = "";

        // Beam Search initialization
        $beam = [];
        for ($i = 0; $i < $this->n; $i++) {
            $visitedStr = str_repeat('0', $this->n);
            $visitedStr[$i] = '1';
            $beam[] = [
                'u' => $i,
                'path' => [$i],
                'v' => $visitedStr
            ];
        }

        $beamWidth = 1000;
        while (!empty($beam)) {
            $nextBeam = [];
            foreach ($beam as $state) {
                if (count($state['path']) > count($bestPath)) {
                    $bestPath = $state['path'];
                    $res = $this->fragments[$bestPath[0]];
                    for ($k = 1; $k < count($bestPath); $k++) {
                        $res .= substr($this->fragments[$bestPath[$k]], 2);
                    }
                    $bestStr = $res;
                }

                $u = $state['u'];
                foreach ($this->adj[$u] as $v) {
                    if ($state['v'][$v] === '0') {
                        $newV = $state['v'];
                        $newV[$v] = '1';
                        $newPath = $state['path'];
                        $newPath[] = $v;

                        $nextBeam[] = [
                            'u' => $v,
                            'path' => $newPath,
                            'v' => $newV,
                            'score' => count($this->adj[$v]) + (lcg_value() * 0.1)
                        ];
                    }
                }
            }

            if (empty($nextBeam)) break;

            // Sort nextBeam by score descending
            usort($nextBeam, fn($a, $b) => $b['score'] <=> $a['score']);
            if (count($nextBeam) > $beamWidth) {
                $nextBeam = array_slice($nextBeam, 0, $beamWidth);
            }
            $beam = $nextBeam;
        }

        $execTime = round((microtime(true) - $startTime) * 1000, 2);

        $orderedFragments = [];
        foreach ($bestPath as $idx) {
            $orderedFragments[] = $this->fragments[$idx];
        }

        return [
            'success' => true,
            'total_input_fragments' => $this->n,
            'max_fragments' => count($bestPath),
            'total_digits' => strlen($bestStr),
            'puzzle_string' => $bestStr,
            'ordered_fragments' => $orderedFragments,
            'execution_time_ms' => $execTime
        ];
    }
}

$text = file_get_contents(dirname(__DIR__) . '/fragments.txt');
$frags = preg_split('/\s+/', trim($text));

$solver = new FastBeamSolver($frags);
$res = $solver->solve();

echo "Success: " . ($res['success'] ? 'YES' : 'NO') . "\n";
echo "Max Fragments: " . $res['max_fragments'] . "\n";
echo "Total Digits: " . $res['total_digits'] . "\n";
echo "Execution Time: " . $res['execution_time_ms'] . " ms\n";
echo "Puzzle Result String:\n" . $res['puzzle_string'] . "\n";
