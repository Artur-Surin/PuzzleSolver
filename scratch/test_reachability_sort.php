<?php
require_once dirname(__DIR__) . '/PuzzleSolver.php';

// Let's modify PuzzleSolver to sort start candidates by reachable count
class UltraPuzzleSolver {
    private array $fragments;
    private int $n;
    private array $prefInt = [];
    private array $suffInt = [];
    private array $adj = [];
    private array $initIn = [];
    private array $initOut = [];
    private array $activeNodes = [];
    
    private array $globalBestPath = [];
    private string $bestPuzzleStr = "";
    private float $startTime;
    private float $maxExecutionSeconds;

    public function __construct(array $rawFragments, float $maxExecutionSeconds = 5.0) {
        $this->fragments = array_values(array_filter(
            array_map('trim', $rawFragments),
            fn($s) => strlen($s) === 6 && ctype_digit($s)
        ));
        
        $this->n = count($this->fragments);
        $this->maxExecutionSeconds = $maxExecutionSeconds;

        if ($this->n === 0) return;

        $this->initIn = array_fill(0, 100, 0);
        $this->initOut = array_fill(0, 100, 0);
        $nodesMap = [];

        for ($i = 0; $i < $this->n; $i++) {
            $f = $this->fragments[$i];
            $p = (int)substr($f, 0, 2);
            $s = (int)substr($f, -2);
            $this->prefInt[$i] = $p;
            $this->suffInt[$i] = $s;
            $this->adj[$i] = [];

            $this->initOut[$p]++;
            $this->initIn[$s]++;
            $nodesMap[$p] = true;
            $nodesMap[$s] = true;
        }

        for ($i = 0; $i < $this->n; $i++) {
            for ($j = 0; $j < $this->n; $j++) {
                if ($i !== $j && $this->suffInt[$i] === $this->prefInt[$j]) {
                    $this->adj[$i][] = $j;
                }
            }
        }

        for ($i = 0; $i < $this->n; $i++) {
            usort($this->adj[$i], function($a, $b) {
                return $this->initOut[$this->suffInt[$a]] <=> $this->initOut[$this->suffInt[$b]];
            });
        }

        $this->activeNodes = array_keys($nodesMap);
    }

    private function getReachableCount(int $start): int {
        $visited = array_fill(0, $this->n, false);
        $q = [$start];
        $visited[$start] = true;
        $count = 0;
        while (!empty($q)) {
            $curr = array_shift($q);
            $count++;
            foreach ($this->adj[$curr] as $next) {
                if (!$visited[$next]) {
                    $visited[$next] = true;
                    $q[] = $next;
                }
            }
        }
        return $count;
    }

    public function solve(): array {
        if ($this->n === 0) {
            return [
                'success' => false,
                'error' => 'Фрагменти не знайдені або мають невірний формат.'
            ];
        }

        $this->startTime = microtime(true);

        $visited = array_fill(0, $this->n, false);
        $remIn = $this->initIn;
        $remOut = $this->initOut;

        $baseSum = 0;
        foreach ($this->activeNodes as $v) {
            $baseSum += min($remIn[$v], $remOut[$v]);
        }

        $startCandidates = [];
        for ($i = 0; $i < $this->n; $i++) {
            $p = $this->prefInt[$i];
            $s = $this->suffInt[$i];

            $delta = 0;
            if ($remOut[$p] <= $remIn[$p]) $delta--;
            $remOut[$p]--;
            if ($remIn[$s] <= $remOut[$s]) $delta--;
            $remIn[$s]--;

            $visited[$i] = true;
            $ub = 1 + ($baseSum + $delta) + (($remIn[$s] >= $remOut[$s]) ? 1 : 0);
            
            $reachCount = $this->getReachableCount($i);
            $zeroInBoost = ($this->initIn[$p] === 0) ? 1000 : 0;
            $priority = $reachCount + $zeroInBoost + ($ub * 10);

            $startCandidates[] = [
                'i' => $i,
                'ub' => $ub,
                'initSum' => $baseSum + $delta,
                'priority' => $priority
            ];

            $visited[$i] = false;
            $remIn[$s]++;
            $remOut[$p]++;
        }

        usort($startCandidates, fn($a, $b) => $b['priority'] <=> $a['priority']);

        foreach ($startCandidates as $cand) {
            if ((microtime(true) - $this->startTime) > $this->maxExecutionSeconds) {
                break;
            }

            $i = $cand['i'];
            $ub = $cand['ub'];
            $initSum = $cand['initSum'];

            if ($ub <= count($this->globalBestPath)) {
                continue;
            }

            $p = $this->prefInt[$i];
            $s = $this->suffInt[$i];
            $visited[$i] = true;
            $remOut[$p]--;
            $remIn[$s]--;

            $path = [$i];
            $this->dfs($i, $path, $initSum, $visited, $remIn, $remOut);

            $remIn[$s]++;
            $remOut[$p]++;
            $visited[$i] = false;
        }

        $executionTime = round((microtime(true) - $this->startTime) * 1000, 2);

        $orderedFragments = [];
        foreach ($this->globalBestPath as $idx) {
            $orderedFragments[] = $this->fragments[$idx];
        }

        return [
            'success' => true,
            'total_input_fragments' => $this->n,
            'max_fragments' => count($this->globalBestPath),
            'total_digits' => strlen($this->bestPuzzleStr),
            'puzzle_string' => $this->bestPuzzleStr,
            'ordered_fragments' => $orderedFragments,
            'execution_time_ms' => $executionTime
        ];
    }

    private function dfs(int $u, array &$path, int $currentSum, array &$visited, array &$remIn, array &$remOut): void {
        if ((microtime(true) - $this->startTime) > $this->maxExecutionSeconds) {
            return;
        }

        $pathLen = count($path);
        if ($pathLen > count($this->globalBestPath)) {
            $this->globalBestPath = $path;
            $res = $this->fragments[$path[0]];
            for ($k = 1; $k < $pathLen; $k++) {
                $res .= substr($this->fragments[$path[$k]], 2);
            }
            $this->bestPuzzleStr = $res;
        }

        $currTail = $this->suffInt[$u];
        $maxAdd = $currentSum + (($remIn[$currTail] >= $remOut[$currTail]) ? 1 : 0);

        if ($pathLen + $maxAdd <= count($this->globalBestPath)) {
            return;
        }

        foreach ($this->adj[$u] as $v) {
            if (!$visited[$v]) {
                $p = $this->prefInt[$v];
                $s = $this->suffInt[$v];

                $delta = 0;
                if ($remOut[$p] <= $remIn[$p]) $delta--;
                $remOut[$p]--;

                if ($remIn[$s] <= $remOut[$s]) $delta--;
                $remIn[$s]--;

                $visited[$v] = true;
                $path[] = $v;

                $this->dfs($v, $path, $currentSum + $delta, $visited, $remIn, $remOut);

                array_pop($path);
                $visited[$v] = false;

                $remIn[$s]++;
                $remOut[$p]++;
            }
        }
    }
}

$text = file_get_contents(dirname(__DIR__) . '/fragments.txt');
$fragments = preg_split('/\s+/', trim($text));

$solver = new UltraPuzzleSolver($fragments, 3.0);
$res = $solver->solve();

echo "Success: " . ($res['success'] ? 'YES' : 'NO') . "\n";
echo "Total Input Fragments: " . $res['total_input_fragments'] . "\n";
echo "Max Fragments: " . $res['max_fragments'] . "\n";
echo "Total Digits: " . $res['total_digits'] . "\n";
echo "Execution Time: " . $res['execution_time_ms'] . " ms\n";
echo "Puzzle Result String:\n" . $res['puzzle_string'] . "\n";
