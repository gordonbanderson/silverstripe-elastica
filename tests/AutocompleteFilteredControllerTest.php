<?php


/**
 */
class AutocompletFilteredeControllerTest extends ElasticsearchFunctionalTestBase
{
    public static $fixture_file = 'elastica/tests/autocomplete.yml';

    public function testSiteTree()
    {
        $url = 'autocomplete/search?field=Title&filter=1&query=the';
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $result = json_decode($body);

        $this->assertEquals('the', $result->Query);
        $lquery = strtolower($result->Query);
        foreach ($result->suggestions as $suggestion) {
            $value = $suggestion->value;
            $value = strtolower($value);
            $this->assertContains($lquery, $value);
        }

        // make sure there were actually some results
        $this->assertEquals(5, sizeof($result->suggestions));

        //search for different capitlisation, should produce the same result
        $url = 'autocomplete/search?field=Title&filter=1&query=ThE';
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $result2 = json_decode($body);
        $this->assertEquals($result->suggestions, $result2->suggestions);

        //append a space should produce the same result
        $url = 'autocomplete/search?field=Title&filter=1&query=ThE%20';
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $result3 = json_decode($body);
        $this->assertEquals($result2->suggestions, $result3->suggestions);

        //search for part of Moonraker
        $url = 'autocomplete/search?field=Title&filter=1&query=rake';
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $result = json_decode($body);
        $this->assertEquals(1, sizeof($result->suggestions));

        // test a non existent class, for now return blanks so as to avoid extra overhead as this
        // method is called often
        $url = 'autocomplete/search?field=FieldThatDoesNotExist&filter=1&query=the';
        $response = $this->get($url);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $result4 = json_decode($body);
        $this->assertEquals(0, sizeof($result4->suggestions));
    }
}
