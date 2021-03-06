<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\HTTPRequest;
use ReflectionMethod;

class HTTPRequestTest extends SapphireTest
{
    protected static $fixture_file = null;

    public function testMatch()
    {
        $request = new HTTPRequest("GET", "admin/crm/add");

        /* When a rule matches, but has no variables, array("_matched" => true) is returned. */
        $this->assertEquals(array("_matched" => true), $request->match('admin/crm', true));

        /* Becasue we shifted admin/crm off the stack, just "add" should be remaining */
        $this->assertEquals("add", $request->remaining());

        $this->assertEquals(array("_matched" => true), $request->match('add', true));
    }

    public function testHttpMethodOverrides()
    {
        $request = new HTTPRequest(
            'GET',
            'admin/crm'
        );
        $this->assertTrue(
            $request->isGET(),
            'GET with no method override'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm'
        );
        $this->assertTrue(
            $request->isPOST(),
            'POST with no method override'
        );

        $request = new HTTPRequest(
            'GET',
            'admin/crm',
            array('_method' => 'DELETE')
        );
        $this->assertTrue(
            $request->isGET(),
            'GET with invalid POST method override'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            array(),
            array('_method' => 'DELETE')
        );
        $this->assertTrue(
            $request->isDELETE(),
            'POST with valid method override to DELETE'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            array(),
            array('_method' => 'put')
        );
        $this->assertTrue(
            $request->isPUT(),
            'POST with valid method override to PUT'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            array(),
            array('_method' => 'head')
        );
        $this->assertTrue(
            $request->isHEAD(),
            'POST with valid method override to HEAD '
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            array(),
            array('_method' => 'head')
        );
        $this->assertTrue(
            $request->isHEAD(),
            'POST with valid method override to HEAD'
        );

        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            array('_method' => 'head')
        );
        $this->assertTrue(
            $request->isPOST(),
            'POST with invalid method override by GET parameters to HEAD'
        );
    }

    public function testRequestVars()
    {
        $getVars = array(
            'first' => 'a',
            'second' => 'b',
        );
        $postVars = array(
            'third' => 'c',
            'fourth' => 'd',
        );
        $requestVars = array(
            'first' => 'a',
            'second' => 'b',
            'third' => 'c',
            'fourth' => 'd',
        );
        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            $getVars,
            $postVars
        );
        $this->assertEquals(
            $requestVars,
            $request->requestVars(),
            'GET parameters should supplement POST parameters'
        );

        $getVars = array(
            'first' => 'a',
            'second' => 'b',
        );
        $postVars = array(
            'first' => 'c',
            'third' => 'd',
        );
        $requestVars = array(
            'first' => 'c',
            'second' => 'b',
            'third' => 'd',
        );
        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            $getVars,
            $postVars
        );
        $this->assertEquals(
            $requestVars,
            $request->requestVars(),
            'POST parameters should override GET parameters'
        );

        $getVars = array(
            'first' => array(
                'first' => 'a',
            ),
            'second' => array(
                'second' => 'b',
            ),
        );
        $postVars = array(
            'first' => array(
                'first' => 'c',
            ),
            'third' => array(
                'third' => 'd',
            ),
        );
        $requestVars = array(
            'first' => array(
                'first' => 'c',
            ),
            'second' => array(
                'second' => 'b',
            ),
            'third' => array(
                'third' => 'd',
            ),
        );
        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            $getVars,
            $postVars
        );
        $this->assertEquals(
            $requestVars,
            $request->requestVars(),
            'Nested POST parameters should override GET parameters'
        );

        $getVars = array(
            'first' => array(
                'first' => 'a',
            ),
            'second' => array(
                'second' => 'b',
            ),
        );
        $postVars = array(
            'first' => array(
                'second' => 'c',
            ),
            'third' => array(
                'third' => 'd',
            ),
        );
        $requestVars = array(
            'first' => array(
                'first' => 'a',
                'second' => 'c',
            ),
            'second' => array(
                'second' => 'b',
            ),
            'third' => array(
                'third' => 'd',
            ),
        );
        $request = new HTTPRequest(
            'POST',
            'admin/crm',
            $getVars,
            $postVars
        );
        $this->assertEquals(
            $requestVars,
            $request->requestVars(),
            'Nested GET parameters should supplement POST parameters'
        );
    }

    public function testIsAjax()
    {
        $req = new HTTPRequest('GET', '/', array('ajax' => 0));
        $this->assertFalse($req->isAjax());

        $req = new HTTPRequest('GET', '/', array('ajax' => 1));
        $this->assertTrue($req->isAjax());

        $req = new HTTPRequest('GET', '/');
        $req->addHeader('X-Requested-With', 'XMLHttpRequest');
        $this->assertTrue($req->isAjax());
    }

    public function testGetURL()
    {
        $req = new HTTPRequest('GET', '/');
        $this->assertEquals('', $req->getURL());

        $req = new HTTPRequest('GET', '/assets/somefile.gif');
        $this->assertEquals('assets/somefile.gif', $req->getURL());

        $req = new HTTPRequest('GET', '/home?test=1');
        $this->assertEquals('home?test=1', $req->getURL(true));
        $this->assertEquals('home', $req->getURL());
    }

    public function testGetIPFromHeaderValue()
    {
        $req = new HTTPRequest('GET', '/');
        $reflectionMethod = new ReflectionMethod($req, 'getIPFromHeaderValue');
        $reflectionMethod->setAccessible(true);

        $headers = array(
            '80.79.208.21, 149.126.76.1, 10.51.0.68' => '80.79.208.21',
            '52.19.19.103, 10.51.0.49' => '52.19.19.103',
            '10.51.0.49, 52.19.19.103' => '52.19.19.103',
            '10.51.0.49' => '10.51.0.49',
            '127.0.0.1, 10.51.0.49' => '127.0.0.1',
        );

        foreach ($headers as $header => $ip) {
            $this->assertEquals($ip, $reflectionMethod->invoke($req, $header));
        }
    }
}
