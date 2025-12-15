<?php

namespace classes\data;

class Calculate {

    public static function cpm(float|int $cost, float|int $impressions): float|int {
        return $impressions === 0 ? 0 : round($cost * 1000 / $impressions, 2);
    }

    public static function costByCpm(float|int $impressions, int|float $cpm): float|int {
        return round($impressions * $cpm / 1000,2);
    }

    public static function engagement(int|float $interactions, int $numberOfPeople, $decimal = false, int $precision = 2): float {
        return $numberOfPeople === 0 ? 0 : round($interactions / $numberOfPeople * ($decimal ? 1 : 100), $precision);
    }

    public static function average(int|float $n, float|int $total, int $precision = 2): int|float {
        return (int)$total === 0 ? 0 : round($n / $total, $precision);
    }
    public static function percentage(int|float $n, float|int $total, int $precision = 2): int|float {
        return (int)$total === 0 ? 0 : round(self::fraction($n, $total, $precision) * 100, $precision);
    }
    public static function fraction(int|float $n, float|int $total, int $precision = 2): int|float {
        return (int)$total === 0 ? 0 : round($n / $total, $precision);
    }
    public static function frequency(array $numberValues, string $benchmark = 'week', int $precision = 2): int|float {
        $benchmarks = [
            'second' => 1,  'minute' => 60, 'hour' => 60 * 60,
            'day' => 60 * 60 * 24, 'week' => 60 * 60 * 24 * 7,
            'month' => 60 * 60 * 24 * 365.25 / 12, 'year' => 60 * 60 * 24 * 365.25
        ];
        if(!in_array($benchmark, array_keys($benchmarks))) return 0;
        $count = count($numberValues);
        if ($count < 2) return 0;
        sort($numberValues);
        $diffs = [];
        for ($i = 1; $i < $count; $i++) $diffs[] = $numberValues[$i] - $numberValues[$i - 1];
        $avgInterval = array_sum($diffs) / count($diffs);
        if ($avgInterval <= 0) return 0;

        $freq = $benchmarks[$benchmark] / $avgInterval;
        return round($freq, $precision);
    }

    public static function percentageChange(int|float $oldValue, int|float $newValue, int $precision = 2): int|float {
        if ((int)$oldValue === 0) {
            // If old value is 0 and new value is not 0, return 100% (or -100% if new is negative)
            if ((int)$newValue === 0) return 0;
            return $newValue > 0 ? 100 : -100;
        }
        return round((($newValue - $oldValue) / $oldValue) * 100, $precision);
    }






}