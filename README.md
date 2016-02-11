# EVE Online Provider for OAuth 2.0 Client
[![Latest Version](https://poser.pugx.org/evelabs/oauth2-eveonline/v/stable)](https://packagist.org/packages/evelabs/oauth2-eveonline)
[![Software License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![Build Status](https://travis-ci.org/EvELabs/oauth2-eveonline.svg?branch=master)](https://travis-ci.org/EvELabs/oauth2-eveonline)
[![Code Coverage](https://scrutinizer-ci.com/g/EvELabs/oauth2-eveonline/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/EvELabs/oauth2-eveonline/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/EvELabs/oauth2-eveonline/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/EvELabs/oauth2-eveonline/?branch=master)
[![Total Downloads](https://poser.pugx.org/evelabs/oauth2-eveonline/downloads)](https://packagist.org/packages/evelabs/oauth2-eveonline)

This package provides Eve Online OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require evelabs/oauth2-eveonline
```

## Docs

[Eve Online CREST&SSO 3rd party documentation](http://eveonline-third-party-documentation.readthedocs.org/en/latest/crest/authentication/)

## Usage

Usage is the same as The League's OAuth client, using `\Evelabs\OAuth2\Client\Provider\EveOnline` as the provider.

See `example/example.php` for more insight.

### Authorization Code Flow

```php
$provider = new Evelabs\OAuth2\Client\Provider\EveOnline([
    'clientId'          => '{eveonline-client-id}',
    'clientSecret'      => '{eveonline-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getCharacterName());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

### Managing Scopes

When creating your EveOnline authorization URL, you can specify the scopes your application may authorize.

```php
$options = [
    'scope' => ['publicData','characterLocationRead'] // array or string
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```

### Refreshing access token

EVE Online Oauth server issues short-lived(around 20 minutes) access tokens so once it expires you have to obtain a new one using long-lived refresh token. 

```php
$new_token = $provider->getAccessToken('refresh_token', [
    'refresh_token' => $old_token->getRefreshToken()
]);
```

### Calling CREST

Once you've obtained both (access & refresh) tokens you can start making requests.

```php
$request = $provider->getAuthenticatedRequest(
    'GET',
    'https://crest-tq.eveonline.com/characters/{characterID}/',
    $accessToken->getToken()
);

$response = $provider->getResponse($request);
```

## Framework integration

Symfony 2

- [KnpUOAuth2ClientBundle](https://github.com/knpuniversity/oauth2-client-bundle)

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/thephpleague/oauth2-linkedin/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Oleg Krasavin](https://github.com/okwinza)
- [All Contributors](https://github.com/evelabs/oauth2-eveonline/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/evelabs/oauth2-eveonline/blob/master/LICENSE) for more information.
