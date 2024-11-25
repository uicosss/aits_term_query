<?php

/**
 * Updated cli-test example
 *
 * @author Dan Paz-Horta, Jeremy Jones
 */

use GuzzleHttp\Exception\GuzzleException;
use Uicosss\AITS\TermQuery;

try {
    require_once __DIR__ . '/../vendor/autoload.php';

    print_r($argv);
    echo PHP_EOL;

    // Subscription Key from Azure Gateway API
    if(empty($argv[1])){
        throw new Exception("Error: Specify API URL as the 1st argument.");
    }

    // API URL
    if(empty($argv[2])){
        throw new Exception("Error: Specify Subscription Key from AITS Azure API as the 2nd argument.");
    }

    // Campus
    if(empty($argv[3])){
        throw new Exception("Error: Specify Campus for the 3rd argument. Must be one of: `uic`, `uis`, or `uiuc`");
    }

    // Call the AITS Term Query API
    $termApi = new TermQuery($argv[1], $argv[2]);

    // Get the results of a call
    $termApi->findTerm($argv[3], (empty($argv[4]) ? 'current' : $argv[4]), (empty($argv[5]) ? 'json' : $argv[5]));

    echo PHP_EOL;
    echo "TermQuery Object:" . PHP_EOL;
    print_r($termApi);

    echo "HTTP Code: [" . $termApi->getHttpResponseCode() . "]" . PHP_EOL;

    // Get the raw response
    echo $termApi->getResponse(true) . PHP_EOL;

    echo PHP_EOL;
    echo "Terms:" . PHP_EOL;
    print_r($termApi->getTerms());

    echo PHP_EOL;
    echo "Terms Count:" . PHP_EOL;
    print_r($termApi->getTermsCount());

    echo PHP_EOL;
    echo "Terms Contained in Response:" . PHP_EOL;
    print_r($termApi->getTermsContained());

    echo PHP_EOL;
    echo "Academic Years Contained in Response:" . PHP_EOL;
    print_r($termApi->getAcademicYearsContained());

    echo PHP_EOL;
    echo "Finanicial Aid Processing Years Contained in Response:" . PHP_EOL;
    print_r($termApi->getFinanicialAidProcessingYearsContained());

    echo PHP_EOL;

    // Get the raw response
    echo $termApi->getResponse(true) . PHP_EOL;

    echo PHP_EOL;

} catch (GuzzleException|Exception $e) {
    print_r($e->getMessage());
    echo PHP_EOL;
    echo PHP_EOL;
}