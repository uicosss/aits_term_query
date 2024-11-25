# University of Illinois
## AITS - Term Query

PHP library for using the AITS Term Query API. Contact AITS for additional implementation details.

## Usage
To use the library, you need to:

### Include library in your program
`require_once 'TermQuery.php';`

### or use composer 
```
composer require uicosss/aits_term_query
require_once 'vendor/autoload.php';
```

### Instantiate an object of the class
```
$apiUrl = 'apiurl.com/without/trailing/slash'; // Contact AITS for this
$subscriptionKey = 'YOUR_SUBSCRIPTION_KEY'; // Contact AITS for this
$personApi = new uicosss\TermQuery($apiUrl, $subscriptionKey);
```

### Getting Results from an API call
The default response will be JSON, but you can also request the raw data which will be an object of StdClass. Contact AITS for additional details on API schema.
```
Required:
$campus = 'uic'; // One of 'uic', 'uis', 'uiuc')

Optional:
$term = 'nextYear'; // Banner Term or term period ('current', 'nextYear', 'pastYear'). Default is `current`

$termApi->findTerm($campus, $term); // Conduct the term query
echo $termApi->getResponse(true); // See raw JSON response
$json = $termApi->getResponse(); // Get decoded JSON array
```

## Examples:
You can use the attached `examples/cli-test.php` file from the command line to test functionality.

`php cli-test.php apiurl.com/without/trailing/slash YOUR_SUBSCRIPTION_KEY campus`