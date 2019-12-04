<?php

namespace Application\Sites;

use Application\Services\Storage;
use Application\WebScraper\WebDriverIdentifierType;
use Application\Services\Log;
use Application\Services\MakeMatcher;

class PriceAnalyzer extends SiteMaster {
    private static $PARTIAL_MOTORI_URL = '';
    private static $PAGE_TO_SCRAPE = 30; //change delay to 5 before change me
    private static $TITLES = 'titles';
    private static $URLS = 'urls';
    //You have to use double quotation marks
    //Css-selector in compound name is better than xpath
    private static $ANNOUNCE_XPATH = "//*[@id=\"layout\"]/main/div[2]/div/div/div/div/div//a";
    private static $CAR_HISTORY_LI_XPATH = "//*[@id=\"layout\"]/main/div[2]/div[1]/section[5]/ul/li";
    private static $PRICE_XPATH = "//*[@id=\"layout\"]/main/div[2]/div[1]/section[3]/div[2]/div[3]/h4";
    private static $RETAILER_XPATH = "//*[@id=\"layout\"]/main/div[2]/div[1]/section[3]/div[3]/div/span/span";

    /**
     * Storage titles and urls of announces on file
     *
     */
    public function storeTitlesAndUrls() {        
        Log::info('START SEARCH TITLES AND URLS...');     
        $totAnnounces = 0;
        $page = 1;   
        while ($page <= self::$PAGE_TO_SCRAPE) {
            Log::debug('PAGE NUMBER: ' . $page);
            $this->ws->goTo(self::$PARTIAL_MOTORI_URL . $page);
            //Take all elements by xpath (not by class because compound name are not permitted)
            $announces = $this->ws->getElements(self::$ANNOUNCE_XPATH, WebDriverIdentifierType::XPATH);
            $infos = $this->takeTitlesAndUrls($announces);
            $titles=$infos[self::$TITLES];
            $urls=$infos[self::$URLS];
            //Store infos
            if(!empty($titles) && !empty($urls)){
                Log::debug('VALID TITLE: '.count($titles).'. VALID URL: '.count($urls));
                foreach ($titles as $title) {
                    Storage::putOnSubitoPATitleCsv($title);
                }
                foreach ($urls as $url) {
                    Storage::putOnSubitoPAUrlCsv($url);
                }
            }
            $countAnnounces = count($announces);
            Log::debug('ANNOUNCES IN THIS PAGE: '.$countAnnounces);
            $totAnnounces += $countAnnounces;
            $this->ws->delay(5);
            $page++;           
        }
        Log::debug('TOTAL ANNOUNCES: '.$totAnnounces);
        Log::info('FINISH, STORAGE TITLES AND URLS COMPLETED.');
        $this->ws->quit();
    }

    /**
     * Storage car data of announces on file
     *
     */
    public function storeCarData() {
        Log::info('START SEARCH CARS HISTORIES...');
        $urls = file(dirname(__FILE__) . '\..\..\..\csv\subito-pa-urls.csv', FILE_IGNORE_NEW_LINES);
        $jsonLines = $this->navigateUrls($urls);
        foreach ($jsonLines as $line) {
            Storage::putOnSubitoPACarsHistoriesJson($line);
        }
        Log::debug('CARS HISTORIES: '.count($jsonLines));
        Log::info('FINISH, STORAGE CARS HISTORIES COMPLETED.');
        $this->ws->quit();
    }

    /**
     * Take titles and urls in announces
     *
     * @param array $announce the announce
     * @return array the titles and urls taken
     */
    private function takeTitlesAndUrls(array $announces){
        $infos = [];
        $titles = [];
        $urls = [];
        $makeMatcher = new MakeMatcher();
        foreach ($announces as $announce) {
            try {                
                $title = $this->takeTitle($announce);
                if (Storage::existsOnSubitoPATitleCsv($title)) {
                    Log::notice('TITLE ALREADY SAVED [' . $title . ']');
                } else {                    
                    $make_id = $makeMatcher->run($title);
                    if ($make_id) {
                        $titles[]=$title;
                        Log::info('TITLE ADD CORRECTLY [' . $title . ']');
                        $url = $announce->getAttribute('href');                        
                        if (Storage::existsOnSubitoPAUrlCsv($url)) {                            
                            Log::notice('URL ALREADY SAVED [' . $url . ']');
                        } else {
                            $urls[]=$url;
                            Log::info('URL ADD CORRECTLY [' . $url . ']');
                        }
                    }
                }
            } catch (\Exception $error) {
                Log::error($error->getMessage());                
            }
        }
        $infos[self::$TITLES]=$titles;
        $infos[self::$URLS]=$urls;
        return $infos;           
    }

    /**
     * Take the title
     *
     * @param \Facebook\WebDriver\WebDriverElement $announce the announce
     * @return string the title
     */
    private function takeTitle(\Facebook\WebDriver\WebDriverElement $announce){
        $anchor = $announce->findElement($this->ws->getItem('h2', WebDriverIdentifierType::TAG_NAME));                
        $title = $anchor->getText();
        return $title;
    }

    /**
     * Visit the urls
     *
     * @param array $urls the urls to visit
     * @return array the cars histories in json
     */
    private function navigateUrls($urls){
        $jsonLines=[];
        foreach ($urls as $url) {
            if (Storage::existsOnSubitoPAUrlVisitedCsv($url)) {
                Log::notice('URL ALREADY VISITED [' . $url . ']');
            }else{
                Storage::putOnSubitoPAUrlVisitedCsv($url);
                $this->ws->goTo($url);
                $line = $this->takeCarHistory();
                if(!empty($line)){
                    $jsonLines[]=$line;
                }
                $this->ws->delay(5); 
            }                      
        }        
        return $jsonLines;
    }

    /**
     * Take car history
     *
     * @return string the car history in json
     */
    private function takeCarHistory(){
        $data = [];
        $line = '';
        $price = 0;
        try{
            $price_elem=$this->ws->getElement(self::$PRICE_XPATH, WebDriverIdentifierType::XPATH);
            $price = $price_elem->getText();
            $price = $this->trasformPrice($price);            
        }catch(\Exception $error){
            Log::error($error->getMessage());
        }
        if($price>=500){
            $listItems = $this->ws->getElements(self::$CAR_HISTORY_LI_XPATH, WebDriverIdentifierType::XPATH);
            $data['Prezzo']=''.$price;
            foreach ($listItems as $listItem) {
                $anchor= [];
                $anchor = $listItem->findElements($this->ws->getItem('span', WebDriverIdentifierType::TAG_NAME));
                $span1=$anchor[0]->getText();
                $span2=$anchor[1]->getText();
                //Name => value  
                $data[$span1]=$span2;                
            }
            //Check if is a retailer
            if(count($this->ws->getElements(self::$RETAILER_XPATH, WebDriverIdentifierType::XPATH))>0){
                Log::info('IS A RETAILER');
                $data['Rivenditore']='si';
            }else{
                $data['Rivenditore']='no';
            }
            $line = json_encode($data, true);
        }                
        return $line;
    }

    /**
     * Trasform the price into a value
     *
     * @param string $price eg: 9.500 € (amount + currency)
     * @return int eg: 9500 (value)
     */
    private function trasformPrice(string $price){
        $value=0;
        preg_match('/(\S*)(\s*)(\S*)/', $price, $result);
        $amount = $result[1];
        $format = numfmt_create( 'it_IT', \NumberFormatter::DECIMAL);
        $value = numfmt_parse($format, $amount);
        return $value;
    }
}