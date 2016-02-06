<?php

namespace Evelabs\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\GenericResourceOwner;

class EveOnlineResourceOwner extends GenericResourceOwner
{
    /**
     * Raw response
     *
     * @var array
     */
    protected $response;


    /**
     * Creates new resource owner.
     *
     * @param array  $response
     */
    public function __construct(array $response = array())
    {
        $this->response = $response;
    }

    /**
     * Get user characterID
     *
     * @return string|null
     */
    public function getCharacterID()
    {
        return $this->response['CharacterID'] ?: null;
    }

    /**
     * Get user characterName
     *
     * @return string|null
     */
    public function getCharacterName()
    {
        return $this->response['CharacterName'] ?: null;
    }

    /**
     * Get user CharacterOwnerHash
     *
     * @return string|null
     */
    public function getCharacterOwnerHash()
    {
        return $this->response['CharacterOwnerHash'] ?: null;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
