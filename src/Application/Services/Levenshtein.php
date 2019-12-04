<?php

namespace Application\Services;

class Levenshtein {
    private static $THRESHOLD = 0.5;

    private $input = null;
    private $dictionary = null;
    private $case_insensitive = false;
    
    public function getInput() {
        return $this->input;
    }
    public function setInput($input) {
        $this->input = $input;
    }
    public function setDictionary($dictionary) {
        $this->dictionary = $dictionary;
    }
    public function getDictionary() {
        return $this->dictionary;
    }
    public function getCaseInsensitive() {
        return $this->case_insensitive;
    }
    public function setCaseInsensitive($case_insensitive) {
        $this->case_insensitive = $case_insensitive;
    }

    public function run() {
        $closest = null;
        // input misspelled word
        $input = $this->getInput();

        if ($this->getCaseInsensitive()) {
            $input = strtolower($input);
            echo PHP_EOL . "CaseInsensitive active" . PHP_EOL;
        } else {
            echo PHP_EOL . "CaseInsensitive deactivate" . PHP_EOL;
        }

        // array of words to check against
        $words = $this->getDictionary();

        // no shortest distance found, yet
        $shortest = -1;

        // loop through words to find the closest
        foreach ($words as $word) {
            // calculate the distance between the input word,
            // and the current word
            if ($this->getCaseInsensitive()) {
                $lev = levenshtein($input, strtolower($word));
            } else{
                $lev = levenshtein($input, $word);
            }

            // check for an exact match
            if ($lev == 0) {
                // closest word is this one (exact match)
                $closest = $word;
                $shortest = 0;
                $percent = 1 - $lev / max(strlen($input), strlen($closest));
                // break out of the loop; we've found an exact match
                break;
            }

            // if this distance is less than the next found shortest
            // distance, OR if a next shortest word has not yet been found
            if ($lev <= $shortest || $shortest < 0) {
                // set the closest match, and shortest distance
                $closest = $word;
                $shortest = $lev;
                $percent = 1 - $lev / max(strlen($input), strlen($closest));
            }
        }
        return [$closest, $shortest, $percent];
    }

    public function isAGoodResult($result) {
        $b = false;
        $percent = $result[2];
        if ($percent > self::$THRESHOLD) {
            $b = true;
        }
        return $b;
    }

    public function printResult($result) {
        $input = $this->getInput();
        $closest = $result[0];
        $shortest = $result[1];
        $percent = $result[2];
        $info = ' (differenza caratteri ' . $shortest . ', match ' . round($percent * 100, 2) . '%)';
        echo PHP_EOL . "INFO: Input word is '$input'" . PHP_EOL;
        if ($shortest == 0) {
            echo "INFO: Exact match found '$closest'" . $info . PHP_EOL . PHP_EOL;
        } else {
            if (!$this->isAGoodResult($result)) {
                echo "WARNING: It was not possible to determine a good result. Closest word is '$closest' ?" .
                    $info .
                    PHP_EOL .
                    PHP_EOL;
            } else {
                echo "NOTICE: Did you mean '$closest' ?" . $info . PHP_EOL . PHP_EOL;
            }
        }
    }
}
