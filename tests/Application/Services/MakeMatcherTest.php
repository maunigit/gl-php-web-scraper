<?php

namespace Application\Services;

use PHPUnit\Framework\TestCase;
use Application\Services\Log;
use Application\Services\MakeMatcher;

final class MakeMatcherTest extends TestCase {
    public function start($title){
        $makeMatcher = new MakeMatcher();
        $res = $makeMatcher->run($title);
        return $res;
    }

    
    public function testRunHyphenAndSpace(): void {
        Log::notice('testRunHyphenAndSpace');
        $title = 'Vendo roll de . ! ? a c s classe A Alf romeO';        
        $actual = $this->start($title);
        $this->assertEquals('alfa-romeo', $actual);
        $title = 'Vendo roll de . ! ? a c s classe A asto Martin';
        $actual = $this->start($title);
        $this->assertEquals('aston-martin', $actual);
        $title = 'Vendo roll de . ! ? a c s classe A de toMaso';
        $actual = $this->start($title);
        $this->assertEquals('de-tomaso', $actual);
        $title = 'Vendo roll de . ! ? a c s classe A la roVer';
        $actual = $this->start($title);
        $this->assertEquals('land-rover', $actual);
        $title = 'Vendo roll de . ! ? a c s classe A ben merceDES';
        $actual = $this->start($title);
        $this->assertEquals('mercedes-benz', $actual);
        $title = 'Vendo roll de . ! ? a c s classe A merceDES-benz';
        $actual = $this->start($title);
        $this->assertEquals('mercedes-benz', $actual);
    }


    public function testRun(): void {
        Log::notice('testRun');
        $title = 'Vendo roll de . ! ? a c s classe A FIAT';
        $actual = $this->start($title);
        $this->assertEquals('fiat', $actual);
    }    

    public function testRunEmptyTitle(): void {
        Log::notice('testRunEmptyTitle');
        $title = '';
        $actual = $this->start($title);
        $this->assertNull($actual);
    }

    public function testRunNoMakeTitle(): void {
        Log::notice('testRunNoMakeTitle');
        $title = 'Vendo de . ! ? a c s classe A';
        $actual = $this->start($title);
        $this->assertNull($actual);
    }

    public function testNoMatchTitle(): void {
        Log::notice('testNoMatchTitle');
        $title = ' a  n   x    z';
        $actual = $this->start($title);
        $this->assertNull($actual);
    }

    public function testSubitoPATitles(): void {
        Log::notice('testSubitoPATitles');
        $csvTitlesFile = file(dirname(__FILE__) . '\..\..\..\csv\subito-pa-titles-test.csv');
        echo 'TITLES FILE:' . PHP_EOL;
        print_r($csvTitlesFile);
        foreach ($csvTitlesFile as $line) {
            $title = $line;
            $actual = $this->start($title);
            if ($actual) {
                $this->assertInternalType("string", $actual);
            } else {
                $this->assertNull($actual);
            }
        }
    }
}
