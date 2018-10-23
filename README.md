## Summary
Rough PHP bindings for the Clockify API. The main reason I built this is because none of Clockify's built-in reports allow time entries with duplicate titles to be aggregated. 

## Installation

Install via Composer:

```
composer require moismailzai/clockify dev-master
```

Then include it in your PHP code:

```
require_once './vendor/autoload.php';

use MoIsmailzai\Clockify;
```

## Usage

Use by creating a new Clockify object with your API key and the workspace you want to make reports for:

```
$clockify = new Clockify( "<< YOUR API KEY >>", "<< YOUR WORKSPACE NAME >>" );
```

### Making arbitrary API calls

To use this as a PHP wrapper for the API, just pass in the API path to the ```apiRequest``` method:

```
$clockify->apiRequest( 'workspaces/' . $clockify->workspaceId . '/projects/' )
```

You can also make POST requests by including a JSON-encoded payload:

```
$clockify->apiRequest( 'workspaces/' . $clockify->workspaceId . '/reports/summary/',
    json_encode( array(
        "archived" => "Active",
        "billable" => "BOTH",
        "clientIds" => [],
        "description" => "",
        "endDate" => "2018-10-01T23:59:59.999Z",
        "firstTime" => true,
        "includeTimeEntries" => true,
        "me" => false,
        "name" => "",
        "projectIds" => [],
        "startDate" => "2018-10-01T00:00:00.000Z",
        "tagIds" => [],
        "taskIds" => [],
        "userGroupIds" => [],
        "userIds" => [],
        "zoomLevel" => "week"
    ) )
)
```

### Generating daily reports
To generate a daily report, pass in a date to the ```getReportByDay``` method:

```
$report = $clockify->getReportByDay( '2018-10-01' );
```

Which will return an object like:

```
array(3) {
  ["total"]=>
  string(31) "0 hours, 12 minutes, 38 seconds"
  ["date"]=>
  string(10) "2018-10-01"
  ["projects"]=>
  array(1) {
    ["Project Name"]=>
    array(2) {
      ["time"]=>
      string(31) "3 hours, 38 minutes, 43 seconds"
      ["entries"]=>
      array(1) {
        ["First Entry"]=>
        array(3) {
          ["intervals"]=>
          array(1) {
            [0]=>
            object(stdClass)#1 (3) {
              ["start"]=>
              string(20) "2018-10-01T16:10:00Z"
              ["end"]=>
              string(20) "2018-10-01T16:22:38Z"
              ["duration"]=>
              string(8) "PT12M38S"
            }
          }
          ["total"]=>
          string(9) "PT12M38S"
          ["totalString"]=>
          string(31) "0 hours, 12 minutes, 38 seconds"
        }
      }
    }
  }
}
```

And you can format it yourself or pass the result to the built-in formatter:

```
$clockify->formatReport( $report )
```

Which will return something like:

```

---------------------------------------------------------
 Report for 2018-10-01 (0 hours, 12 minutes, 38 seconds)
---------------------------------------------------------

First Project (0 hours, 12 minutes, 38 seconds): 

• First Entry (0 hours, 12 minutes, 38 seconds)
```
