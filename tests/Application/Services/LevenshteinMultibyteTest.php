<?php

namespace Application\Services;

use PHPUnit\Framework\TestCase;
use Application\Services\Log;
use Application\Services\LevenshteinMultibyte;

final class LevenshteinMultibyteTest extends TestCase {
    private function start($input, $demo_dictionary){        
        $levenshtein = new LevenshteinMultibyte();
        Log::debug("START LEVENSHTEINMULTIBYTE...");
        $levenshtein->setDictionary($demo_dictionary);
        $levenshtein->setInput($input);
        $result = $levenshtein->run();
        $levenshtein->printResult($result);
        $closest_word = $result[0];
        return  $closest_word;   
    }

    public function testStrlenAndMBStrlen(){
        Log::notice('testStrlenAndMBStrlen');
        $str = "你好"; //hello        
        $len_str = strlen($str);
        $this->assertEquals(6, $len_str);
        $len_mb = mb_strlen($str);        
        $this->assertEquals(2, $len_mb);
        $str = "♫⚓⚓♥";
        $len_str = strlen($str);
        $this->assertEquals(12, $len_str);
        $len_mb = mb_strlen($str);        
        $this->assertEquals(4, $len_mb);
    }

    public function testRunChinese(): void {   
        Log::notice('testRunChinese');     
        $dictionary = array('好', '非常好', '你好世界'); //good, very good, hello world
        $input = '你好世'; 
        $closest_word = $this->start($input, $dictionary);
        $this->assertEquals('你好世界', $closest_word);
    }

    public function testRunJapanese(): void {   
        Log::notice('testRunJapanese');       
        $dictionary = array('良い', 'とても良い', 'こんにちは世界'); //good, very good, hello world
        $input = 'こんにちは'; 
        $closest_word = $this->start($input, $dictionary);
        $this->assertEquals('こんにちは世界', $closest_word);
    }

    public function testRunRussian(): void {     
        Log::notice('testRunRussian');   
        $dictionary = array('хороший', 'отлично', 'Привет, мир'); //good, very good, hello world
        $input = 'Привет'; 
        $closest_word = $this->start($input, $dictionary);
        $this->assertEquals('Привет, мир', $closest_word);
    }

    public function testRunSymbols(): void {    
        Log::notice('testRunSymbols');    
        $dictionary = array("♫⚓⚓♥", "♫♥⚓♫⚓♫");
        $input = '♫⚓⚓'; 
        $closest_word = $this->start($input, $dictionary);
        $this->assertEquals('♫⚓⚓♥', $closest_word);
    }
}
