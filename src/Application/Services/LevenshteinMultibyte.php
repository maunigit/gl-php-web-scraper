<?php

namespace Application\Services;

class LevenshteinMultibyte extends Levenshtein {
    public function run() {
        $closest = null;
        // input misspelled word
        $input = $this->getInput();

        if ($this->getCaseInsensitive()) {
            $input = mb_strtolower($input);
        }

        // array of words to check against
        $words = $this->getDictionary();

        // no shortest distance found, yet
        $shortest = -1;

        // multibytes 
        $k = strlen($input) / mb_strlen($input);
 
        // loop through words to find the closest
        foreach ($words as $word) {
            if ($this->getCaseInsensitive()) {
                $word = mb_strtolower($word);
            }
            // calculate the distance between the input word,
            // and the current word
            $lev = levenshtein($input, $word);
            
            $lev = $lev / $k;
            
            // check for an exact match
            if ($lev == 0) {
                // closest word is this one (exact match)
                $closest = $word;
                $shortest = 0;
                $percent = 1 - $lev / max(mb_strlen($input), mb_strlen($closest));
                // break out of the loop; we've found an exact match
                break;
            }

            // if this distance is less than the next found shortest
            // distance, OR if a next shortest word has not yet been found
            if ($lev <= $shortest || $shortest < 0) {
                // set the closest match, and shortest distance
                $closest = $word;
                $shortest = $lev;
                $percent = 1 - $lev / max(mb_strlen($input), mb_strlen($closest));
            }
        }
        return [$closest, $shortest, $percent];
    }
}
