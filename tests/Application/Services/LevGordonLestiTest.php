<?php

namespace Application\Services;

use PHPUnit\Framework\TestCase;
use GordonLesti\Levenshtein\Levenshtein;
use Application\Services\Log;

class LevGordonLestiTest extends TestCase{
    public function testLevGordonLesti(): void {
        Log::notice('testLevGordonLesti');
        $input = '♫⚓⚓♥⚓♥♥'; 
        $levDist = Levenshtein::levenshtein($input, '♫⚓⚓⚓♥⚓♥♥');        
        $this->assertEquals(1, $levDist);
    }
}
