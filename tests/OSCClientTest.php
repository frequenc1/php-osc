<?php

use PhpOSC\OSCClient;
use PhpOSC\OSCMessage;
use PhpOSC\Timetag;
use PhpOSC\Infinitum;
use PhpOSC\Blob;
use PhpOSC\OSCBundle;

class OSCClientTest extends PHPUnit_Framework_TestCase
{

    function setUp()
    {

    }

    function testOSCClient()
    {
        $c = new OSCClient();
        $c->set_destination("192.168.11.5", 3980);
        $m1 = new OSCMessage("/test", array(new Timetag(3294967295, 5), new Infinitum(), new Blob("aoeuaoeu!")));
        $m1->add_arg(28658.93, "d");
        $m2 = new OSCMessage("/bar", array(1, 2, array(1, 2, 3)));
        $b = new OSCBundle();
        $b->add_datagram($m1);
        $b->add_datagram($m2);
        $b2 = new OSCBundle(array($m1, $b));
        echo $b2->get_human_readable();
//echo $m1->get_human_readable();
        $c->send($m1);

    }

} 