<?php
namespace MoIsmailzai;

class Clockify
{

    public $apiKey;
    public $apiEndpoint;
    public $workspaceId;

    /**
     * Constructs a Clockify object
     * @param String $apiKey clockify API key (see https://clockify.github.io/clockify_api_docs/)
     * @param String $workspace clockify API key (see https://clockify.github.io/clockify_api_docs/#tag-Workspace)
     * @param String $apiEndpoint you shouldn't have to change this
     * @throws Exception if $apiKey or $workspace are not provided, or an invalid $apiEndpoint is specified
     * @returns Clockify
     */
    public function __construct( $apiKey, $workspace, $apiEndpoint = "https://api.clockify.me/api/" )
    {

        if ( !$apiKey ) {
            throw new Exception( 'You must provide an API key.' );
        } else {
            $this->apiKey = $apiKey;
        }

        if ( !filter_var( $apiEndpoint, FILTER_VALIDATE_URL ) ) {
            throw new Exception('You must provide a valid API endpoint.');
        } else {
            $this->apiEndpoint = $apiEndpoint;
        }

        try {
            $clockfiyWorkspaces = $this->apiRequest( 'workspaces/' );
        } catch( Exception $e ) {
            return $e;
        }

        foreach( json_decode( $clockfiyWorkspaces ) as $clockifyWorkspace ) {

            if ( $clockifyWorkspace->name === $workspace ) {
                $this->workspaceId = $clockifyWorkspace->id;
            }

        }

        if ( !$this->workspaceId ) {
            throw new Exception( 'You must provide a valid workspace.' );
        }

        return $this;
    }

    public function getReportByDay( $day = "2018-01-31" )
    {
        $report = array();

        try {
            $result = json_decode(
                $this->apiRequest( 'workspaces/' . $this->workspaceId . '/reports/summary/',
                    json_encode( array(
                        "archived" => "Active",
                        "billable" => "BOTH",
                        "clientIds" => [],
                        "description" => "",
                        "endDate" => $day . "T23:59:59.999Z",
                        "firstTime" => true,
                        "includeTimeEntries" => true,
                        "me" => false,
                        "name" => "",
                        "projectIds" => [],
                        "startDate" => $day . "T00:00:00.000Z",
                        "tagIds" => [],
                        "taskIds" => [],
                        "userGroupIds" => [],
                        "userIds" => [],
                        "zoomLevel" => "week"
                    ) )
                )
            );
        } catch( Exception $e ) {
            return $e;
        }

        try {
            $report[ 'total' ] = new DateInterval( $result->totalTime );
        } catch ( Exception $e ) {
            return $e;
        }

        $report[ 'date' ] = $day;
        $report[ 'projects' ] = array();
        $report[ 'total' ] = $report[ 'total' ]->format( '%h hours, %i minutes, %S seconds' );

        foreach ( $result->projectAndTotalTime as $project ) {

            try {
                $time = new DateInterval( $project-> duration );
            } catch ( Exception $e ) {
                return $e;
            }

            $report[ 'projects' ][ $project->projectName ] = array();
            $report[ 'projects' ][ $project->projectName ][ 'time' ] = $time->format( '%h hours, %i minutes, %S seconds' );
            $report[ 'projects' ][ $project->projectName ][ 'entries' ] = array();
        }

        foreach ( $result->timeEntries as $timeEntry ) {
            $entry = &$report[ 'projects' ][ $timeEntry->project->name ][ 'entries' ][ $timeEntry->description ];

            if ( !$entry ) {
                $report[ 'projects' ][ $timeEntry->project->name ][ 'entries' ][ $timeEntry->description ] = array();
                $entry = &$report[ 'projects' ][ $timeEntry->project->name ][ 'entries' ][ $timeEntry->description ];
                $entry[ 'intervals' ] = array();
            }

            array_push( $entry[ 'intervals' ], $timeEntry->timeInterval );

            try {
                $timeEntryDuration = ClockifyDateInterval::fromDateInterval( new DateInterval( $timeEntry->timeInterval->duration ) );
            } catch ( Exception $e ) {
                return $e;
            }

            if ( $entry[ 'total' ] ) {

                try {
                    $previousTotalDuration = new DateInterval( $entry[ 'total' ] );
                } catch ( Exception $e ) {
                    return $e;
                }

                $timeEntryDuration->add( $previousTotalDuration );
                $entry[ 'total' ] = $timeEntryDuration->format( 'PT%hH%iM%sS' );
                $entry[ 'totalString' ] = $timeEntryDuration->format( '%h hours, %i minutes, %S seconds' );
            } else {
                $entry[ 'total' ] = $timeEntryDuration->format('PT%hH%iM%sS');
                $entry[ 'totalString' ] = $timeEntryDuration->format( '%h hours, %i minutes, %S seconds' );
            }
        }

        return $report;
    }

    public function formatReport( $report )
    {
        $result = "---------------------------------------------------------\n" ;
        $result .= " Report for " . $report['date'] . " (" . $report['total'] . ")\n";
        $result .= "---------------------------------------------------------\n";

        foreach ($report['projects'] as $key => $project) {
            $result .="\n";
            $result .= $key . " (" . $project['time'] . "): \n\n";

            foreach ($project['entries'] as $key2 => $entry) {
                $result .= "• " . $key2 . " (" . $entry['totalString'] . ")\n";
            }

        }

        $result .="\n";
        return $result;
    }

    public function apiRequest( $apiPath, $payload = false )
    {
        $requestHeaders = array(
            'Content-Type:application/json',
            'X-Api-Key:' . $this->apiKey
        );

        if ( $payload ) {
            $requestHeaders[] = 'Content-Length:' . strlen( $payload );
        }

        $ch = $this->getCurlObject(
            $this->apiEndpoint . $apiPath,
            $requestHeaders,
            isset( $payload ) ? $payload : false
        );

        $result = curl_exec( $ch );

        if ( curl_error( $ch ) ) {
            return curl_error( $ch );
        } else {
            return $result;
        }

    }

    public function getCurlObject( $url, $headers, $payload, $headerFunction = false )
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );

        if ( $headers ) {
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        }

        if ( $headerFunction ) {
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, $headerFunction );
        }

        curl_setopt( $ch, CURLOPT_FAILONERROR, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        if ( $payload ) {
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        } else {
            curl_setopt( $ch, CURLOPT_HTTPGET, 1 );
        }

        return $ch;
    }
}
