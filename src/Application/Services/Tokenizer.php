<?php

namespace Application\Services;

class Tokenizer {
    public function run($regex, $str) {
        $lines = preg_split($regex, $str, -1, PREG_SPLIT_NO_EMPTY);
        return $lines;
    }

    public function filterByLength($words, $max_length) {
        $array = [];
        foreach ($words as $word) {
            if (strlen($word) >= $max_length) {
                $array[] = $word;
            }
        }
        return $array;
    }
}
