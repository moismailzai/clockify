<?php
namespace MoIsmailzai;

/**
 * Copied almost verbatim from https://gist.github.com/hakre/3142405 (see http://stackoverflow.com/q/11556731/367456)
 */
class ClockifyDateInterval extends DateInterval
{
    public static function fromDateInterval( DateInterval $from )
    {

        try {
            return new ClockifyDateInterval( $from->format( 'P%yY%dDT%hH%iM%sS' ) );
        } catch ( Exception $e ) {
            return $e;
        }

    }

    public function add( DateInterval $interval )
    {

        foreach ( str_split('ymdhis' ) as $prop ) {
            $this->$prop += $interval->$prop;
        }

        $this->i += (int)($this->s / 60);
        $this->s = $this->s % 60;
        $this->h += (int)($this->i / 60);
        $this->i = $this->i % 60;
    }
}
