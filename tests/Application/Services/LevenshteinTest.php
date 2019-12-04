<?php

namespace Application\Services;

use PHPUnit\Framework\TestCase;
use Application\Services\Levenshtein;
use Application\Services\Log;

final class LevenshteinTest extends TestCase {
    public function start($input, $dictionary, $caseInsensitive) {
        Log::debug("START LEVENSHTEIN...");
        $levenshtein = new Levenshtein();        
        $levenshtein->setDictionary($dictionary);
        $levenshtein->setInput($input);
        $levenshtein->setCaseInsensitive($caseInsensitive);            
        $result = $levenshtein->run();
        $levenshtein->printResult($result);
        $closest_word = $result[0];
        return $closest_word;
    }

    public function testRunCaseInsensitive(): void {
        Log::notice('testRunCaseInsensitive with caseInsensitive active');
        $dictionary = array('apple', 'pineapple', 'BAnana', 'orange', 'radish', 'carrot', 'pea', 'bean', 'potato');
        $closest_word = $this->start('BAnana', $dictionary, true);
        $this->assertEquals('BAnana', $closest_word);
        $closest_word = $this->start('banana', $dictionary, true);
        //must return the word in the dictionary as the same case sensitive
        $this->assertEquals('BAnana', $closest_word);
        $closest_word = $this->start('carrrot', $dictionary, true);
        $this->assertEquals('carrot', $closest_word);
        $closest_word = $this->start('app', $dictionary, true);
        $this->assertEquals('apple', $closest_word);
        $closest_word = $this->start('ap', $dictionary, true);
        $this->assertNotEquals('apple', $closest_word);
    }

    public function testRunNoCaseInsensitive(): void {
        Log::notice('testRunNoCaseInsensitive with caseInsensitive deactivate');
        $dictionary = array('apple', 'pineapple', 'BAnana', 'orange', 'radish', 'carrot', 'pea', 'bean', 'potato');
        $closest_word = $this->start('BAnana', $dictionary, false);
        $this->assertEquals('BAnana', $closest_word);
        $closest_word = $this->start('banana', $dictionary, false);
        //must return the word in the dictionary as the same case sensitive
        $this->assertEquals('BAnana', $closest_word);
        $closest_word = $this->start('carrrot', $dictionary, false);
        $this->assertEquals('carrot', $closest_word);
        $closest_word = $this->start('app', $dictionary, false);
        $this->assertEquals('apple', $closest_word);
        $closest_word = $this->start('ap', $dictionary, false);
        $this->assertNotEquals('apple', $closest_word);
    }
}
