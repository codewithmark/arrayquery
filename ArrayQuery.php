<?php

class ArrayQuery
{
    private array $data;
    private array $originalData;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->originalData = $data;
    }

    public function reset(): self
    {
        $this->data = $this->originalData;
        return $this;
    }

    public function select(array $columns = ['*']): self
    {
        if ($columns === ['*']) return $this;

        $this->data = array_map(fn($row) => array_intersect_key($row, array_flip($columns)), $this->data);
        return $this;
    }

    public function where($condition, array $params = []): self
    {
        $index = 0;
        if (is_string($condition)) {
            $safeExpression = preg_replace_callback('/\?/', function () use (&$index, $params) {
                $value = $params[$index++] ?? '';
                return is_numeric($value) ? $value : "'" . addslashes($value) . "'";
            }, $condition);

            $tokens = preg_split('/\s+(AND|OR)\s+/i', $safeExpression, -1, PREG_SPLIT_DELIM_CAPTURE);

            $this->data = array_filter($this->data, function ($row) use ($tokens) {
                $results = [];
                $logicOps = [];

                foreach ($tokens as $token) {
                    if (preg_match('/^(AND|OR)$/i', $token)) {
                        $logicOps[] = strtoupper($token);
                        continue;
                    }

                    if (preg_match('/([\\w\\.]+)\\s*(>=|<=|!=|=|>|<)\\s*([\'"]?[^\'"]+[\'"]?)/', $token, $m)) {
                        [, $column, $op, $value] = $m;
                        $value = trim($value, "'\"");
                        $actual = $this->getNestedValue($row, $column);
                        $results[] = self::compare($actual, $op, $value);
                    }
                }

                $result = array_shift($results);
                foreach ($results as $i => $r) {
                    $op = $logicOps[$i] ?? 'AND';
                    $result = ($op === 'AND') ? ($result && $r) : ($result || $r);
                }

                return $result;
            });
        }

        return $this;
    }

    private function getNestedValue(array $row, string $column)
    {
        if (str_contains($column, '.')) {
            return $row[$column] ?? null; // flat alias style, e.g. r.name
        }
        return $row[$column] ?? null;
    }

    private static function compare($a, $op, $b): bool
    {
        return match ($op) {
            '>' => $a > $b,
            '<' => $a < $b,
            '=' => $a == $b,
            '>=' => $a >= $b,
            '<=' => $a <= $b,
            '!=' => $a != $b,
            default => false,
        };
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        usort($this->data, function ($a, $b) use ($column, $direction) {
            $aVal = $this->getNestedValue($a, $column);
            $bVal = $this->getNestedValue($b, $column);
            return ($direction === 'asc') ? $aVal <=> $bVal : $bVal <=> $aVal;
        });
        return $this;
    }

    public function limit(int $count, int $offset = 0): self
    {
        $this->data = array_slice($this->data, $offset, $count);
        return $this;
    }

    public function join(array $other, string $localKey, string $operator, string $foreignKey, string $type = 'inner', string $alias = null): self
    {
        $joined = [];

        $foreignIndex = [];
        foreach ($other as $row) {
            $foreignIndex[$row[$foreignKey]][] = $row;
        }

        foreach ($this->data as $row) {
            $localValue = $row[$localKey] ?? null;
            $matches = $foreignIndex[$localValue] ?? [];

            if ($matches) {
                foreach ($matches as $match) {
                    $joinedRow = $row;
                    foreach ($match as $key => $value) {
                        $joinedRow[$alias ? "$alias.$key" : $key] = $value;
                    }
                    $joined[] = $joinedRow;
                }
            } elseif ($type === 'left') {
                foreach ($other[0] ?? [] as $key => $_) {
                    $row[$alias ? "$alias.$key" : $key] = null;
                }
                $joined[] = $row;
            }
        }

        $this->data = $joined;
        return $this;
    }

    public function groupBy(string $column): array
    {
        $grouped = [];
        foreach ($this->data as $row) {
            $key = $this->getNestedValue($row, $column);
            $grouped[$key][] = $row;
        }
        return $grouped;
    }

    public function aggregate(array $fields): array
    {
        $result = [];

        foreach ($fields as $column => $func) {
            $values = array_column($this->data, $column);
            $result[$column] = match (strtoupper($func)) {
                'SUM' => array_sum($values),
                'AVG' => count($values) ? array_sum($values) / count($values) : 0,
                'MAX' => max($values),
                'MIN' => min($values),
                'COUNT' => count($values),
                default => null,
            };
        }

        return $result;
    }

    public function paginate(int $page, int $perPage): array
    {
        $total = count($this->data);
        $pages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        return [
            'data' => array_slice($this->data, $offset, $perPage),
            'total' => $total,
            'pages' => $pages,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->data);
    }

    public function toCsv(string $filename = 'export.csv'): void
    {
        $f = fopen($filename, 'w');
        if (!$f || empty($this->data)) return;

        fputcsv($f, array_keys(reset($this->data)));
        foreach ($this->data as $row) fputcsv($f, $row);

        fclose($f);
    }

    public function get(): array
    {
        return $this->data;
    }
}
?>
