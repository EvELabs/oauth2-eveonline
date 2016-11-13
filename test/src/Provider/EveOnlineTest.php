<?php

namespace Evelabs\OAuth2\Client\Test\Provider;

use Mockery as m;

class EveOnlineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Evelabs\OAuth2\Client\Provider\EveOnline;
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new \Evelabs\OAuth2\Client\Provider\EveOnline([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    /**
     * @param array $responseHeaders
     * @param $responseStatus
     * @param $responseBody
     * @return \GuzzleHttp\ClientInterface
     */
    protected function buildClient(array $responseHeaders, $responseStatus, $responseBody)
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn($responseBody);
        $response->shouldReceive('getHeader')->andReturn($responseHeaders);
        $response->shouldReceive('getStatusCode')->andReturn($responseStatus);
        $response->shouldReceive('getReasonPhrase')->andReturn('Totally random reason phrase');

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($response);

        return $client;
    }


    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes()
    {
        $options = ['scope' => [uniqid(),uniqid()]];

        $url = $this->provider->getAuthorizationUrl($options);

        $this->assertContains(urlencode(implode(' ', $options['scope'])), $url);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $message = '{"access_token": "mock_access_token", "expires_in": 3600}';
        $headers = ['content-type' => 'application/json'];
        $status = 200;

        $this->provider->setHttpClient($this->buildClient($headers, $status, $message));
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertLessThanOrEqual(time() + 3600, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $characterID = rand(1000,9999);
        $characterName = uniqid();
        $characterOwnerHash = uniqid();

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"access_token": "mock_access_token", "expires_in": 3600}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);


        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn('{"CharacterID": '.$characterID.', "CharacterName": "'.$characterName.'", "CharacterOwnerHash": "'.$characterOwnerHash.'"}');
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($characterID, $user->getCharacterID());
        $this->assertEquals($characterID, $user->toArray()['CharacterID']);

        $this->assertEquals($characterName, $user->getCharacterName());
        $this->assertEquals($characterName, $user->toArray()['CharacterName']);

        $this->assertEquals($characterOwnerHash, $user->getCharacterOwnerHash());
        $this->assertEquals($characterOwnerHash, $user->toArray()['CharacterOwnerHash']);
    }

    public function testCrestGetRequest()
    {
        $message = '{"data1": "some_data", "expires_in": 11111}';
        $headers = ['content-type' => 'application/json'];
        $status = 200;

        $this->provider->setHttpClient($this->buildClient($headers, $status, $message));
        $request = $this->provider->getAuthenticatedRequest(
            'GET',
            'SomeCoolURL',
            'someCoolToken'
        );

        $response = $this->provider->getResponse($request);

        $this->assertTrue(is_array($response));
        $this->assertTrue(!empty($response));
    }

    public function testCrestPostRequest()
    {
        $message = '{"data1": "Fitting saved", "expires_in": 11111}';
        $headers = ['content-type' => 'application/json'];
        $status = 201;

        $this->provider->setHttpClient($this->buildClient($headers, $status, $message));
        $request = $this->provider->getAuthenticatedRequest(
            'POST',
            'SomeCoolURL',
            'someCoolToken',
            ['body' => 'someCoolBody']
        );

        $response = $this->provider->getResponse($request);

        $this->assertTrue(is_array($response));
        $this->assertTrue(!empty($response['data1']));
    }

    /**
     * @expectedException \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     **/
    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $message = '{"error":"invalid_grant","error_description":"The authorization grant could not be found"}';
        $headers = ['content-type' => 'application/json'];
        $status = rand(400,600);

        $this->provider->setHttpClient($this->buildClient($headers, $status, $message));
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    /**
     * @expectedException \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     **/
    public function testExceptionThrownWhenUnauthorizedErrorReceived()
    {
        $message = '{"message": "Authentication scope needed", "key": "authNeeded", "exceptionType": "UnauthorizedError"}';
        $headers = ['content-type' => 'application/json'];
        $status = rand(400,600);


        $this->provider->setHttpClient($this->buildClient($headers, $status, $message));
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    /**
     * @expectedException \UnexpectedValueException
     **/
    public function testExceptionThrownWhenInvalidResponseReceived()
    {
        $message = 'Some invalid non-json response';
        $headers = ['content-type' => 'application/json'];
        $status = 200;

        $this->provider->setHttpClient($this->buildClient($headers, $status, $message));
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    /**
     * @expectedException \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     **/
    public function testExceptionThrownWhenBadResponseCodeReceived()
    {
        $message = '{"message": "Some Error message"}';
        $headers = ['content-type' => 'application/json'];
        $status = 500;

        $this->provider->setHttpClient($this->buildClient($headers, $status, $message));
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
