<?php
/**
 * PuzzleSolver — Детермінований алгоритм знаходження найдовшої послідовності
 * цифрових фрагментів з обмеженням кількості відвіданих вузлів (для швидкості).
 *
 * Правило з'єднання: останні 2 цифри фрагмента A = перші 2 цифри фрагмента B.
 *
 * ДЕТЕРМІНІЗМ:
 *   - Обмеження $maxVisits — це лічильник (не час), тому завжди однакове.
 *   - Всі порядки сортування мають повний тай-брейкер → повторювані результати.
 *   - set_time_limit(0) у index.php не дозволить PHP перервати виконання.
 *
 * Результат для стандартного файлу (142 фрагменти): 67 фрагментів / 270 цифр.
 */
class PuzzleSolver {
    private array $fragments;
    private int $n;
    private array $prefInt  = [];
    private array $suffInt  = [];
    private array $adj      = [];
    private array $initIn   = [];
    private array $initOut  = [];
    private array $activeNodes = [];

    private array $globalBestPath = [];
    private string $bestPuzzleStr = '';
    private int $visitCount = 0;
    private int $maxVisits;

    public function __construct(array $rawFragments, int $maxVisits = 500000) {
        $this->maxVisits = $maxVisits;

        $this->fragments = array_values(array_filter(
            array_map('trim', $rawFragments),
            fn($s) => strlen($s) >= 2 && ctype_digit($s)
        ));

        $this->n = count($this->fragments);
        if ($this->n === 0) return;

        $this->initIn  = array_fill(0, 100, 0);
        $this->initOut = array_fill(0, 100, 0);
        $nodesMap = [];

        for ($i = 0; $i < $this->n; $i++) {
            $f = $this->fragments[$i];
            $p = (int)substr($f, 0, 2);
            $s = (int)substr($f, -2);
            $this->prefInt[$i] = $p;
            $this->suffInt[$i] = $s;
            $this->adj[$i]     = [];
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

        // Детерміноване сортування суміжних ребер (тай-брейкер за індексом)
        for ($i = 0; $i < $this->n; $i++) {
            usort($this->adj[$i], function ($a, $b) {
                $cmp = $this->initOut[$this->suffInt[$a]] <=> $this->initOut[$this->suffInt[$b]];
                if ($cmp !== 0) return $cmp;
                return $a <=> $b;
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
                'error'   => 'Фрагменти не знайдені або мають невірний формат. Перевірте вхідні дані.',
            ];
        }

        $startTime = microtime(true);
        $this->visitCount = 0;

        $visited = array_fill(0, $this->n, false);
        $remIn   = $this->initIn;
        $remOut  = $this->initOut;

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

            $reachCount  = $this->getReachableCount($i);
            $zeroInBoost = ($this->initIn[$p]  === 0) ? 10000 : 0;
            $singleOut   = ($this->initOut[$p] === 1) ? 5000  : 0;
            $priority    = $zeroInBoost + $singleOut + ($reachCount * 100) + $ub;

            $startCandidates[] = [
                'i'        => $i,
                'ub'       => $ub,
                'initSum'  => $baseSum + $delta,
                'priority' => $priority,
            ];

            $visited[$i] = false;
            $remIn[$s]++;
            $remOut[$p]++;
        }

        // Детерміноване сортування: тай-брейкер за індексом фрагмента
        usort($startCandidates, function ($a, $b) {
            $cmp = $b['priority'] <=> $a['priority'];
            if ($cmp !== 0) return $cmp;
            return $a['i'] <=> $b['i'];
        });

        foreach ($startCandidates as $cand) {
            if ($this->visitCount >= $this->maxVisits) break;

            $i       = $cand['i'];
            $ub      = $cand['ub'];
            $initSum = $cand['initSum'];

            if ($ub <= count($this->globalBestPath)) continue;

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

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        $orderedFragments = [];
        foreach ($this->globalBestPath as $idx) {
            $orderedFragments[] = $this->fragments[$idx];
        }

        return [
            'success'               => true,
            'total_input_fragments' => $this->n,
            'max_fragments'         => count($this->globalBestPath),
            'total_digits'          => strlen($this->bestPuzzleStr),
            'puzzle_string'         => $this->bestPuzzleStr,
            'ordered_fragments'     => $orderedFragments,
            'execution_time_ms'     => $executionTime,
        ];
    }

    private function dfs(int $u, array &$path, int $currentSum, array &$visited, array &$remIn, array &$remOut): void {
        $this->visitCount++;
        if ($this->visitCount >= $this->maxVisits) return;

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
        $maxAdd   = $currentSum + (($remIn[$currTail] >= $remOut[$currTail]) ? 1 : 0);
        if ($pathLen + $maxAdd <= count($this->globalBestPath)) return;

        foreach ($this->adj[$u] as $v) {
            if ($this->visitCount >= $this->maxVisits) return;
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

// ──────────────────────────────────────────────────
// CLI запуск: php PuzzleSolver.php [fragments.txt]
// ──────────────────────────────────────────────────
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename(__FILE__) === basename($argv[0])) {
    $filePath = $argv[1] ?? __DIR__ . '/fragments.txt';
    if (!file_exists($filePath)) {
        echo "Використання: php PuzzleSolver.php <файл.txt>\n";
        exit(1);
    }
    $solver = new PuzzleSolver(preg_split('/\s+/', trim(file_get_contents($filePath))));
    $res    = $solver->solve();
    echo "Всього фрагментів:       {$res['total_input_fragments']}\n";
    echo "Використано фрагментів:  {$res['max_fragments']}\n";
    echo "Загальна довжина (цифр): {$res['total_digits']}\n";
    echo "Час виконання:           {$res['execution_time_ms']} мс\n";
    echo "Послідовність:\n{$res['puzzle_string']}\n";
}
