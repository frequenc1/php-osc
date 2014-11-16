<?php

namespace PhpOSC;

/** OSCMessage type
 */
class OSCMessage extends OSCDatagram
{
    var $address = "/";
    var $typetags = ",";
    var $data = array();

    /** Make a new message - Optionally specify address and arguements.
     *
     * e.g. $a = new OSCMessage("/foo", array(1, 2.94, "bar"))
     * It is not possible to provide type-hinting using this initialization method.
     */
    function __construct($address = null, $args = null)
    {
        if (!is_null($address)) {
            $this->address = $address;
        }
        if (is_array($args)) {
            foreach ($args as $arg) {
                $this->add_arg($arg);
            }
        }
    }

    /** Reset internal data structures
     */
    function clear()
    {
        $this->address = "/";
        $this->typetags = ",";
        $this->data = array();
        $this->bin = null;
    }

    /** Set packet address
     * e.g. "/test".
     * See OSC spec for details on allowed characters in an OSC address.
     */
    function set_address($addr)
    {
        $this->bin = null;
        $this->address = $addr;
    }

    /** Add an arg to the OSC message.
     * $data can be an integer, float, string, boolean, NULL, or an array of those types.
     * $type-hint is optional.
     */
    function add_arg($data, $type_hint = null)
    {
        $this->bin = null;
        if ($type_hint == null) {
            $type_hint = $this->get_type($data);
        }
        $data = $this->set_type($data, $type_hint);
        array_push($this->data, array($data, $type_hint));
    }

    /** Try to guess the type of data.
     * If this does not work for you, try using a type-hint.
     */
    function get_type($data)
    {
        switch (gettype($data)) {
            case "integer":
                return "i";
            case "double":
            case "float":
                return "f";
            case "string":
                return "s";
            case "boolean":
                if ($data) {
                    return "T";
                } else {
                    return "F";
                }
            case "array":
// Array type will be handled later... 'A' is not actually an OSC type.
                return "A";
            case "object":
                switch (strtolower(get_class($data))) {
                    case "phposc\\infinitum":
                        return "I";
                    case "phposc\\timetag":
                        return "t";
                    case "phposc\\blob":
                        return "b";
                    default:
                        $this->error("Unknown or unsupported object type." . strtolower(get_class($data)));
                }
            case "NULL":
                return "N";
            default:
                $this->error("Unknown or unsupported data type.");
        }
    }

    /** Cast data to type, and add type info to typetags.
     */
    function set_type($data, $type_tag)
    {
        switch ($type_tag) {
            case "i":
                $this->typetags .= "i";
                return (int)$data;
            case "f";
                $this->typetags .= "f";
                return (double)$data;
            case "d";
                $this->typetags .= "d";
                return (double)$data;
            case "s":
            case "c":
                $this->typetags .= "s";
                return (string)$data;
            case "T":
                $this->typetags .= "T";
                return true;
            case "F":
                $this->typetags .= "F";
                return false;
            case "N":
                $this->typetags .= "N";
                return null;
            case "I":
                $this->typetags .= "I";
                return $data;
            case "t":
                $this->typetags .= "t";
                return $data;
            case "b":
                $this->typetags .= "b";
                return $data;
            case "A":
// Array is now expanded...
                $this->typetags .= "[";
                $data = (array)$data;
                for ($i = 0; $i < count($data); $i++) {
                    $type_tag = $this->get_type($data[$i]);
                    $data[$i] = array($this->set_type($data[$i], $type_tag), $type_tag);
                }
                $this->typetags .= "]";
                return $data;
            default:
                throw new OSCException("Unrecognized type tag, '$type_tag'");
        }
    }

    function get_binary()
    {
// Check for cached binary representation and reuse if found.
        if (!is_null($this->bin)) {
            return $this->bin;
        }
// Pack address...
        $this->pack_data($this->address, "s");
// Pack typetags...
        $this->pack_data($this->typetags, "s");
// Pack args...
        foreach ($this->data as $arg) {
            $this->pack_data($arg[0], $arg[1]);
        }
        return $this->bin;
    }
}
