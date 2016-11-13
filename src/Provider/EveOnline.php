<?php

namespace Evelabs\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class EveOnline extends AbstractProvider
{
    use BearerAuthorizationTrait;


    /**
     * Default scopes
     *
     * @var array
     */
    public $defaultScopes = [];

    /**
     * Get the string used to separate scopes.
     *
     * @return string
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }

    /**
     * Returns the base URL for authorizing a client.
     *
     * Eg. https://oauth.service.com/authorize
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return 'https://login.eveonline.com/oauth/authorize';
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * Eg. https://oauth.service.com/token
     *
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://login.eveonline.com/oauth/token';
    }

    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param AccessToken $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://login.eveonline.com/oauth/verify';
    }

    /**
     * Returns the default scopes used by this provider.
     *
     * This should only be the scopes that are required to request the details
     * of the resource owner, rather than all the available scopes.
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return $this->defaultScopes;
    }

    /**
     * Checks a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  array|string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        //not throwing anything for 2xx responses
        if (intval(substr($response->getStatusCode(), 0, 1)) === 2) {
            return;
        }

        $message = $this->safeRead($data, 'error_description') || $this->safeRead($data, 'message');
        throw new IdentityProviderException(
            $message ?: $response->getReasonPhrase(),
            $response->getStatusCode(),
            $response
        );
    }

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param  array $response
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new EveOnlineResourceOwner($response);
    }

    /**
     * Internal helper function to safe read from an array
     * @param mixed $array
     * @param string|int $key
     * @return null
     */
    private function safeRead($array, $key)
    {
        return !empty($array[$key]) ? $array[$key] : null;
    }
}
