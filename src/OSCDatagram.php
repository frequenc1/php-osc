<?php

/** Open Sound Control (OSC) Client Library for PHP
 * Author: Andy W. Schmeder <andy@a2hd.com>
 * Copyright 2003-2007
 *
 * Version 0.2
 *
 * Requirements: PHP 4.1.0 or later.
 * For information about Open Sound Control,
 * see http://www.opensoundcontrol.org/
 *
 * This is free software.
 * It may contain bugs, design flaws or other unforseeable problems.
 * Please feel free to report problems (or success stories) to the author.
 *
 * License: LGPL version 2.1 or later.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * For questions regarding this library contact
 * Andy W. Schmeder <andy@a2hd.com>
 */

namespace PhpOSC;


/** OSCDatagram is a virtual base class for OSCMessage and OSCBundle.
 */
class OSCDatagram
{
// Virtual private data
    var $bin = null;
    var $data = null;

// Virtual functions
    function get_binary()
    {
    }

    function clear()
    {
    }
// Shared functions
    /** Returns a semi-human readable representation of the binary data.
     * Printable bytes will appear as "_C" where C is the printable character.
     * Non-printable bytes will appear in hex, e.g. '20' for a space (\s) character
     * Bytes are clustered in groups of 4 to show the alignment.
     */
    function get_human_readable($hex_only = false)
    {
        $bin = $this->get_binary();
        $hex = "";
        for ($i = 0; $i < strlen($bin); $i++) {
            if ((!$hex_only) && ord($bin[$i]) >= 33 && ord($bin[$i]) <= 126) { // Printable characters
                $hex .= "_" . chr(ord($bin[$i]));
            } else {
                $hex .= sprintf("%02x", ord($bin[$i]));
            }
            if ($i != 0 && $i < strlen($bin) && ($i + 1) % 4 == 0) {
                $hex .= " ";
            }
        }
        return $hex . "\n";
    }

    /** Pack data into $this->bin as 4-byte aligned, network-byte order.
     */
    function pack_data($data, $type_hint)
    {
        $bin = "";
        switch ($type_hint) {
            case "T":
            case "F":
            case "N":
            case "I":
                return; // These types have no allocated space
            case "A":
                foreach ($data as $arg) {
                    $this->pack_data($arg[0], $arg[1]);
                }
                break;
            case "s":
                $data .= "\0"; // The builtin \0 terminator is ignored... we must explicitly request one.
                $bin = pack("a*" . $this->get_strpad($data), $data);
                break;
            case "b":
                $this->pack_data(strlen($data->bin), "i");
                $bin = pack("a*" . $this->get_strpad($data->bin), $data->bin);
                break;
            case "i":
                $bin = OSCClient::host_to_network_order(pack("i", $data)); // Machine-independent size (4-bytes)
                break;
            case "f":
                $bin = OSCClient::host_to_network_order(pack("f", $data)); // Machine-dependent size
                if (strlen($bin) != 4) {
                    $this->error("Sorry, your machine uses an unsupported single-precision floating point size.");
                }
                break;
            case "d":
                $bin = OSCClient::host_to_network_order(pack("d", $data)); // Machine-dependent size
                if (strlen($bin) != 8) {
                    $this->error("Sorry, your machine uses an unsupported double-precision floating point size.");
                }
                break;
            case "t":
                if (is_null($data)) {
                    $data = new Timetag();
                }
                $bin = OSCClient::host_to_network_order(pack("L", $data->sec)) .
                    OSCClient::host_to_network_order(pack("L", $data->frac_sec));
                break;
        }
        if (strlen($bin) % 4 != 0) {
            $this->error("$data failed to align properly, size is " . strlen($bin) . " bytes.");
        }
        $this->bin .= $bin;
    }

    /** Utility to generate padding for strings
     */
    function get_strpad($str)
    {
        $x = (strlen($str)) % 4;
        if ($x == 0) {
            return '';
        } else {
            $x = 4 - $x;
        }
        switch ($x) {
            case 1:
                return 'x';
            case 2:
                return 'xx';
            case 3:
                return 'xxx';
            default:
                $this->error("Pad calculation is screwy, x = $x");
        }
    }

    /** Report an error
     */
    function error($message)
    {
        throw new OSCException("OSCDatagram Error: $message");
        //trigger_error("OSCDatagram Error: $message", E_USER_ERROR);
    }
}