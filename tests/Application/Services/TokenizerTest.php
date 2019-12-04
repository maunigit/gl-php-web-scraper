<?php

namespace Application\Services;

use PHPUnit\Framework\TestCase;
use Application\Services\Tokenizer;
use Application\Services\Log;

final class TokenizerTest extends TestCase {
    public function testRunAndFilterByLength(): void {
        Log::notice('testRunAndFilterByLength');
        $txt = <<<'EOD'
                Mary had a little lamb,
                Whose fleece was white as snow.
                And everywhere that Mary went,
                The lamb was sure to go. 
EOD;
        Log::debug('Text in input: ' . $txt);
        $tokenizer = new Tokenizer();
        $regex = "/[\s,-:\.]+/";
        $tokens = $tokenizer->run($regex, $txt);
        Log::debug('Tokens:');
        print_r($tokens);
        $filtered_tokens = $tokenizer->filterByLength($tokens, 3);
        Log::debug('Filtered tokens:');
        print_r($filtered_tokens);        
        $this->assertEquals('And', $filtered_tokens[9]);
    }
}