<?php

/**
 * University of Illinois - AITS Term Query API Wrapper
 *
 * @author Jeremy Jones
 * @license MIT
 */

namespace Uicosss\AITS;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;

class TermQuery
{
    protected $apiUrl;

    protected $subscriptionKey;

    /**
     * Specific term code (e.g. "120168", "220171", "420168"), or term period ("current", "pastYear", or "nextYear) value to be queried
     *
     * @var String
     */
    protected $term = null;

    /**
     * Desired response format. API supports either "json" (default) or "xml"
     *
     * @var String
     */
    protected $format = 'json';

    /**
     * University filter ("uic", "uis", or "uiuc")
     *
     * @var null|String
     */
    protected $campus = null;

    /**
     * @var \stdClass
     */
    protected $json;
    
    protected $raw = '';

    protected $httpCode = 500;

    /**
     * @var array
     */
    protected $terms = [];

    /**
     * @var int
     */
    protected $termCount = 0;

    /**
     * @var array
     */
    protected $termsContained = [];

    /**
     * @var array
     */
    protected $academicYearsContained = [];

    /**
     * @var array
     */
    protected $financialAidProcessingYearsContained = [];

    /**
     * Sets the two necessary variables for the AITS API call to operate successfully
     *
     * @param string $apiUrl AITS API URL without leading "https:" or trailing "/"
     * @param string $subscriptionKey AITS Subscription Key pulled from the necessary profile
     * @throws Exception
     */
    public function __construct(string $apiUrl, string $subscriptionKey)
    {
        $this->setApiUrl($apiUrl);

        $this->setSubscriptionKey($subscriptionKey);
    }

    /**
     * Executes an AITS API call to find the given parameter.
     * Will throw exception on error, otherwise it will simply
     * assign the API response values to the object variables.
     *
     * @param string $campus
     * @param string $term
     * @return void
     * @throws GuzzleException
     */
    public function findTerm(string $campus, string $term = 'current'): void
    {
        try {
            if (!$this->validateCampus($campus)) {
                throw new Exception('Provided campus was not valid. Must be one of: `uic`, `uis`, or `uiuc`');
            }
            $this->campus = $campus;

            if (!$this->validateTerm($term)) {
                throw new Exception('Provided term was not valid. Must be a valid term period such as: `current`, `pastYear`, or `nextYear`. Or valid 6 digit banner term code.');
            }
            $this->term = $term;

            $client = new Client();

            $request = new Request('GET', $this->apiUrl . '/' . $this->term . '?' . http_build_query(['format' => 'json', 'campus' => $campus]), [
                'Cache-Control' => 'no-cache',
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey
            ]);

            $response = $client->send($request);

            $this->httpCode = $response->getStatusCode();
            $this->raw = $response->getBody();
            $this->json = json_decode($response->getBody(), false);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('AITS API response was not valid JSON');
            }

            if (!isset($this->json->list)) {
                throw new Exception('AITS API did not provide a proper response');
            }

            if (!empty($this->json->list[0]->queryPeriod) || !empty($this->json->list[0]->queryCampus) || !empty($this->json->list[0]->queryTermCode)) {
                $this->terms = $this->json->list[0]->term ?? [];
            } else {
                $this->terms = $this->json->list ?? [];
            }

            if (count($this->terms) == 0) {
                throw new Exception('Terms not found');
            }

            $this->termCount = count($this->terms) ?? 0;

            // Each list element contains a term, parse through it to create high level data
            foreach ($this->terms as $term) {
                $this->termsContained[] = $term->termCode;
                if (!in_array($term->academicYear->code, $this->academicYearsContained)) {
                    $this->academicYearsContained[] = $term->academicYear->code;
                }
                if (!in_array($term->finaidProcYear->code, $this->financialAidProcessingYearsContained)) {
                    $this->financialAidProcessingYearsContained[] = $term->finaidProcYear->code;
                }
            }

        } catch (ClientException $ex) {
            $this->httpCode = $ex->getCode();
            $json = json_decode($ex->getResponse()->getBody(), true);
            throw new Exception(json_last_error() == JSON_ERROR_NONE ? $json['message'] : '');
        } catch (ServerException|BadResponseException|Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function getTerms(): Array
    {
        return $this->terms;
    }

    public function getTermsCount(): int
    {
        return $this->termCount;
    }

    public function getTermsContained(): Array
    {
        return $this->termsContained;
    }

    public function getAcademicYearsContained(): Array
    {
        return $this->academicYearsContained;
    }

    public function getFinanicialAidProcessingYearsContained(): Array
    {
        return $this->financialAidProcessingYearsContained;
    }

    /**
     * @param bool $raw Boolean flag for whether to return raw JSON string or decoded JSON array
     * @return mixed Will return the JSON string or decoded JSON array
     */
    public function getResponse(bool $raw = false)
    {
        return ($raw) ? $this->raw : $this->json;
    }

    public function getHttpResponseCode()
    {
        return $this->httpCode;
    }

    /**
     * @param string $apiUrl AITS API URL with protocol, trailing slash optional
     * @throws Exception
     */
    private function setApiUrl(string $apiUrl)
    {
        if (empty($apiUrl)) {
            throw new Exception("The apiUrl cannot be blank. Please contact AITS for the Azure Gateway API URLs.");
        }

        $this->apiUrl = (substr(trim($apiUrl), -1) == '/') ? trim($apiUrl) : trim($apiUrl) . '/';
    }

    /**
     * @param string $subscriptionKey AITS Subscription Key pulled from the necessary profile
     * @throws Exception
     */
    private function setSubscriptionKey(string $subscriptionKey)
    {
        if (empty($subscriptionKey)) {
            throw new Exception("The subscriptionKey cannot be blank. Refer to the Azure Gateway API profile Subscription Keys.");
        }

        $this->subscriptionKey = trim($subscriptionKey);
    }

    /**
     * Validates the Term code being passed
     *
     * @param string $term
     * @return true
     */
    private function validateTerm(string $term)
    {
        // Must be term period ("current", "pastYear", or "nextYear") value to be queried
        if (in_array(strtolower(preg_replace('/[^A-Za-z]/', '', $term)), ['current', 'pastyear', 'nextyear'])) {
            return true;
        }

        // Must be one of Specific term code (e.g. "120168", "220171", "420168")
        if (strlen($term) === 6
            && is_numeric($term)
            && in_array(intval(substr($term, 0, 1)), [1, 2, 4])) {
            return true;
        }

        return false;
    }

    /**
     * Validates the Campus code being passed
     *
     * @param string $term
     * @return true
     */
    private function validateCampus(string $campus)
    {
        return in_array(strtolower(preg_replace('/[^A-Za-z]/', '', $campus)), ['uic', 'uis', 'uiuc']);
    }

}
