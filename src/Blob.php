<?php

namespace PhpOSC;

/** Binary Blob datatype
 * Blob is basically a non-null-terminated string prefixed by a size indicator.
 */
class Blob
{

    function __construct($bin)
    {
        $this->bin = $bin;
    }
}