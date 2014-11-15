<?php

namespace PhpOSC;


/** OSCClient uses a connectionless UDP socket to transmit binary to its destination.
 *
 * Example of use:
 *
 * $c = new OSCClient();
 * $c->set_destination("192.168.1.5", 3890);
 * $c->send(new OSCMessage("/foo", array(1,2,3)));
 * ... etc.
 *
 * Since it is connectionless, you can change the destination address/port at any time.
 * If you are having problems establishing communication, it may be due to a bad address,
 * improper setup of the IP routing table, or a problem on the other end.  When in doubt,
 * use tcpdump or ethereal to check that packets are indeed being transmitted.
 */
class OSCClient
{

    var $sock = null;
    var $address = null;
    var $port = null;
    static $_arch_little_endian = false;
    static $_arch_twos_complement = false;

    function __construct($address = null, $port = null)
    {
        $this->checkPlatform();
        $this->address = $address;
        $this->port = $port;

        if (($this->sock = socket_create(AF_INET, SOCK_DGRAM, 0)) < 0) {
            $this->error("Could not create datagram socket.");
        }
    }

    /** Destructor function, usually not needed, provided in case you want to free the socket.
     */
    function destroy()
    {
        socket_close($this->sock);
    }


    // You can enable this part if you have PHP 4.3.0 or later...
    function enable_broadcast() {
        if(($ret = socket_set_option($this->sock, SOL_SOCKET, SO_BROADCAST, 1)) < 0) {
            $this->error("Failed to enable broadcast option.");
        }
    }

    function disable_broadcast() {
        if(($ret = socket_set_option($this->sock, SOL_SOCKET, SO_BROADCAST, 0)) < 0) {
            $this->error("Failed to disable broadcast option.");
        }
    }


    /** Address is an IP address, given as a string.
     * To convert a hostname to IP, use gethostbyname('www.example.com')
     * You must also specify a port as an integer, typically $port is larger than 1024.
     */
    function set_destination($address, $port)
    {
        $this->address = $address;
        $this->port = $port;
    }

    /** send() accepts either an OSCDatagram object or a binary string
     */
    function send($message)
    {
        if (is_null($this->address) || is_null($this->port)) {
            $this->error("Destination is not well-defined.  Please use OSCClient::set_destination().");
        }
        if (is_object($message)) {
            $message = $message->get_binary();
        }
        if (($ret = socket_sendto($this->sock, $message, strlen($message), 0, $this->address, $this->port)) < 0) {
            $this->error("Transmission failure.");
        }
        if ($ret != strlen($message)) {
            $mlen = strlen($message);
            $this->error("Could not send the entire message, only $ret bytes were sent, of $mlen total");
        }
        return $ret;
    }

    /** Report a fatal error.
     */
    function error($message)
    {
        throw new OSCException("OSCClient Error: $message");
    }

    // Test if this machine is a little endian architecture
    function test_little_endian()
    {
        $cpu_int = pack("L", 1); // Machine dependent
        $be_int = pack("N", 1); // Machine independent

        if ($cpu_int[0] == $be_int[0]) {
            return false;
        } else {
            return true;
        }
    }

// Test if this machine uses twos complement representation
    function test_twos_complement()
    {
        $cpu_int = pack("i", -1); // Machine dependent
        if (ord($cpu_int[0]) == 255) {
            return true;
        } else {
            return false;
        }
    }

    function checkPlatform()
    {
        // Take note of the configuration for this machine.
        self::$_arch_little_endian = $this->test_little_endian();
        self::$_arch_twos_complement = $this->test_twos_complement();

        if (!self::$_arch_twos_complement) {
            throw new OSCException("WARNING: This machine does not use twos-complement integers.  " .
                "Negative numbers may not be represented correctly.",
                E_USER_NOTICE);
        }
    }

    /** This is a utility function to convert from CPU byte order to network order (big endian).
     *
     * It is necessary to use this function because PHP's pack() function does not support
     * big endian encoding for most data types. (It only does big endian for unsigned ints).
     */
    static function host_to_network_order($str)
    {

        if (self::$_arch_little_endian) {
            $swstr = "";
            for ($i = 0; $i < strlen($str); $i++) {
                $swstr .= $str[(strlen($str) - 1) - $i];
            }
            return $swstr;
        } else {
            // No conversion necessary for big-endian arch
            return $str;
        }
    }

}