<?php

namespace Application\Services;

use Application\Services\Levenshtein;
use Application\Services\Tokenizer;

class MakeMatcher {
    private static $MIN_CHARACTER = 2;
    private static $PERCENT_THRESHOLD = 0.6;
    private static $AVOID_WORDS_FILE = '\..\..\..\assets\blacklists\avoid_words.txt';
    private static $MAKES_FILE = '\..\..\..\assets\Makes.json';

    /**
     * Match Makes in a title
     *
     * @param string $title the title to check
     * @return string|null the make_id
     */
    public function run($title) {
        echo PHP_EOL . "START RETRIVE MAKE_ID FROM THE TITLE..." . PHP_EOL;
        //Don't call carqueryapi many time or they block your ip address, use 'Makes' file instead
        //$json = $this->startCarApiCall();
        $json = file_get_contents(dirname(__FILE__) . self::$MAKES_FILE);
        $makes_obj = json_decode($json, true);
        $makes = $makes_obj['Makes'];
        $make_id = null;
        if (empty($makes)) {
            echo 'ERROR: MAKES IS EMPTY, PLEASE CHECK THE URL OF THE API CALLED' . PHP_EOL;
        } else {
            $array_make_display = array();
            foreach ($makes as $make) {
                array_push($array_make_display, $make['make_display']);
            }

            //Clean 'Makes'
            $dictionary = [];
            foreach ($array_make_display as $word) {
                $dictionary[$word] = [$word];
                //Find ' ' and '-'
                $findme = '-';
                $pos = strpos($word, $findme);
                if (!$pos) {
                    $findme = ' ';
                    $pos = strpos($word, $findme);
                }
                //Split word and put it in the dictionary
                if ($pos == true) {
                    $tokens = explode($findme, $word);
                    foreach ($tokens as $token) {
                        if (strlen($token) > self::$MIN_CHARACTER) {
                            $dictionary[$word] = array_merge($dictionary[$word], [$token]);
                        }
                    }
                }
            }

            //Merge dictionary
            $split_dictionary = [];
            foreach ($dictionary as $key => $value_array) {
                $split_dictionary = array_merge($value_array, $split_dictionary);
            }
            //Set dictionary in Levenshtein
            $levenshtein = new Levenshtein();
            $levenshtein->setDictionary($split_dictionary);
            $levenshtein->setCaseInsensitive(true);

            //Title of the announcement
            echo PHP_EOL . "TITLE: $title" . PHP_EOL;
            $valid_title = false;
            if ($title != '') {
                $tokens = $this->tokenization($title);
                $valid_title = $this->isValidTitle($tokens);
            }

            //Check title
            if ($valid_title) {
                //Run matching
                $results = $this->applyMatch($levenshtein, $tokens);

                //Check if has match
                if ($results != null) {
                    print_r($results);
                    //Search best result
                    $best_result = ['', '', 0];
                    foreach ($results as $result) {
                        $closest = $result[0];
                        $num_different_characters = $result[1];
                        $percent = $result[2];
                        if ($percent > self::$PERCENT_THRESHOLD && $percent > $best_result[2]) {
                            $best_result = [$closest, $num_different_characters, $percent];
                        }
                    }

                    //Retrive 'make'
                    $make_display = null;
                    if ($best_result[2] > 0) {
                        print_r($best_result);
                        $make_display = $this->retrieveMakeDisplay($best_result[0], $dictionary);
                        $make_id = $this->retrieveMakeId($make_display, $makes);
                        echo PHP_EOL . "FINISH, RETRIVED MAKE_ID IS: $make_id" . PHP_EOL;
                    } else {
                        echo PHP_EOL . "FINISH, SORRY MATCH PERCENT IS LESS THAN OUR THRESHOLD PERCENT" . PHP_EOL;
                    }
                } else {
                    echo PHP_EOL . "FINISH, SORRY NO MATCH FOUND" . PHP_EOL;
                }
            } else {
                echo PHP_EOL . "FINISH, SORRY TITLE IS EMPTY OR IN BLACKLIST" . PHP_EOL;
            }
        }
        return $make_id;
    }

    /**
     * Divide input into token
     *
     * @param string $txt the text
     * @return array tokens with at least 3 characters
     */
    private function tokenization($txt) {
        $tokenizer = new Tokenizer();
        $regex = "/[\s,-:\.]+/";
        $tokens = $tokenizer->run($regex, $txt);
        $tokens = $tokenizer->filterByLength($tokens, 3);
        return $tokens;
    }

    /**
     * Apply Levenshtein matching
     *
     * @param Levenshtein $levenshtein the Levenshtein algorithm
     * @param array $tokens the tokens
     * @return array the results of Levenshtein algorithm
     */
    private function applyMatch($levenshtein, $tokens) {
        $results = null;
        foreach ($tokens as $token) {
            $levenshtein->setInput($token);
            $result = $levenshtein->run();
            $levenshtein->printResult($result);
            $results[] = $result;
        }
        return $results;
    }

    /**
     * From token to make_display
     *
     * @param string $findme the token
     * @param array $dictionary the dictionary with make_display
     * @return string|null the make_display
     */
    private function retrieveMakeDisplay($findme, $dictionary) {
        $make_display = null;
        foreach ($dictionary as $key => $values) {
            foreach ($values as $value) {
                if (strtolower($findme) == strtolower($value) || $findme == $value) {
                    $make_display = $key;
                    break 2;
                }
            }
        }
        return $make_display;
    }

    /**
     * From make_display to make_id
     *
     * @param string $findme the make_display
     * @param array $makes the makes
     * @return string|null the make_id
     */
    private function retrieveMakeId($findme, $makes) {
        $result = null;
        $make_id = null;
        foreach ($makes as $make) {
            $make_id = $make["make_id"];
            $_make_display = $make["make_display"];
            if ($_make_display == $findme) {
                $result = $make_id;
                break;
            }
        }
        return $result;
    }

    /**
     * Check validity of the title
     *
     * @param array $tokens the tokens to check
     * @return boolean the validity
     */
    private function isValidTitle($tokens) {
        $validity = true;
        try {
            $wordsToAvoid = file_get_contents(dirname(__FILE__) . self::$AVOID_WORDS_FILE);
            $wordsToAvoid = $this->tokenization($wordsToAvoid);
            foreach ($tokens as $token) {
                foreach ($wordsToAvoid as $wordToAvoid) {
                    if (strtolower($token) == strtolower($wordToAvoid)) {
                        $validity = false;
                        break 2;
                    }
                }
            }
        } catch (\Exception $error) {
            echo $error;
        }
        return $validity;
    }
}
