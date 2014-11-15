<?php


namespace PhpOSC;

/** OSCBundle datagram type
 * This object can contain any number of other OSCDatagram objects.
 */
class OSCBundle extends OSCDatagram
{
    var $data = array();
    var $timetag = null;

    /** Create a new OSCBundle datagram
     *
     * $init may be an array of OSCDatagram objects,
     * e.g. $b = new OSCBundle(new OSCMessage(...), new OSCBundle(...))
     *
     * Otherwise, add messages at runtime using OSCBundle::add_datagram.
     */
    function __construct($init = null)
    {
        if (is_array($init)) {
            foreach ($init as $d) {
                $this->add_datagram($d);
            }
        }
    }

    /** Set time tag as whole seconds since July 1, 1970, and fraction of a second.
     * This feature is not tested, but it should work if you need it.
     *
     * If timetag is not set, it will default to "Immediate".
     */
    function set_timetag($timetag_obj)
    {
        $this->timetag = $timetag_obj;
    }

    /** Add an OSCDatagram object to a bundle.
     * This can be either an OSCMessage or an OSCBundle.
     * However, you cannot reasonably add a bundle to itself.
     */
    function add_datagram($osc_datagram)
    {
        $this->bin = null;
        array_push($this->data, $osc_datagram);
    }

    function clear()
    {
        $this->bin = null;
        $this->data = null;
    }

    function get_binary()
    {
        if ($this->bin != null) {
            return $this->bin;
        }
        $this->bin = "";
        $this->pack_data("#bundle", "s");
        $this->pack_data($this->timetag, "t");
        foreach ($this->data as $datagram) {
            $bin = $datagram->get_binary();
            $this->pack_data((int)strlen($bin), "i");
            $this->bin .= $bin;
        }
        return $this->bin;
    }
}
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
 * improper setup of the IP routing table, or a problem on the other end. When in doubt,
 * use tcpdump or ethereal to check that packets are indeed being transmitted.
 */