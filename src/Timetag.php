<?php

namespace PhpOSC;


/** 64-bit OSC timetag type, Refer to NTP format for details.
 * $sec is integer seconds since Jan 1 1900, and $frac_sec is fractions of a second.
 * The special timetag $sec = 0, $frac_sec = 1 corresponds to "immediate" in OSC,
 * and should be used in all applications where explicit absolute timetags are not implemented.
 */
class Timetag
{
    function __construct($sec = 0, $frac_sec = 1)
    {
        $this->sec = $sec;
        $this->frac_sec = $frac_sec;
    }
}