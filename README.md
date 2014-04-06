Guzzle-oAuth
============

Guzzle oAuth is a oAuth 1 and oAuth 2 library based on Guzzle with the big four (google, linkedin, twitter, facebook) baked in.

### Example with other provider.
See https://github.com/FransvanderMeer/Guzzle-oAuth-Meetup for an example that uses another provider then one of the big four. The example is an implementation of meetup.com as provider.

### Usage
Guzzle oAuth makes the flow to authorize and the user info abstract. That means that you can use the same code for all providers to authorize, get an access token and get the account information.

Send user to provider.
```php
// Authorize flow, send user to provider to authorize
$config = array(
  'consumer_key' => '--YOUR-APP-ID-FROM-FACEBOOK--',
  'consumer_secret' => '--YOUR-APP-SECRET-FROM-FACEBOOK--',
  'scope' => 'email,manage_pages', // scopes for this connection
);
$provider = 'facebook';
$client = \GuzzleOauth\Consumers::get($provider, $config);

// return path after authorization.
$callback_uri = 'http://test.com/callback/uri';

// state param, that makes the round trip to facebook and must be the same on return.
$state = '--some-random-string--'; //optional

// oAuth2 does not use request tokens, we use it here to be consistent with oAuth1
// This makes it possible to replace 'facebook' with 'twitter' in $provider without changing the code.
$request_token = $client->getRequestToken($callback_uri);

// get the redirect url
$url = $client->getAuthorizeUrl($request_token, $callback_uri, $state);

// Note!
// You need to store $request_token (and $state) in a session or db, so that you can pickit up on return.
$_SESSION['REQUEST_TOKEN_' . $provider] = serialize($request_token);

// send the user to facebook to login and authorize your app.
header('Location: ' . $url);
exit;
```
On the callback router
```php
// Return (callback) after authorize
// In our example we are on $callback_uri (http://test.com/callback/uri)

// We setup our client again
$config = array(
  'consumer_key' => '--YOUR-APP-ID-FROM-FACEBOOK--',
  'consumer_secret' => '--YOUR-APP-SECRET-FROM-FACEBOOK--',
  'scope' => 'email,manage_pages', // scopes for this connection
);
$provider = 'facebook';
$client = \GuzzleOauth\Consumers::get($provider, $config);

// Let's get the request token
$request_token = unserialize($_SESSION['REQUEST_TOKEN_' . $provider]);

// we could test if $_GET['state'] is the same as the $state from above.

// Let us get the access_token
$access_token = $client->getAccessToken($_GET, $request_token);

// Store access token in session, unset request token
unset($_SESSION['REQUEST_TOKEN_' . $provider]);
$_SESSION['ACCESS_TOKEN_' . $provider] = serialize($access_token);
```
When we have a valid access token
```php
// Now that we have an access token, do calls to facebook
$provider = 'facebook';

// Get access token
$access_token = unserialize($_SESSION['ACCESS_TOKEN_' . $provider]);

// Add access token to config
$config = array(
  'consumer_key' => '--YOUR-APP-ID-FROM-FACEBOOK--',
  'consumer_secret' => '--YOUR-APP-SECRET-FROM-FACEBOOK--',
  'scope' => 'email,manage_pages', // scopes for this connection
) + $access_token;

// create client
$client = \GuzzleOauth\Consumers::get($provider, $config);

// Get a collection with all user info
$info = $client->getUserInfo();

// Get remote user id without making a new http call
$facebook_id = $client->getUserId($info);

// Get remote user id making a new http call
$facebook_id = $client->getUserId();

// Get email (if provider provides one (note FB needs the scope 'email'))
$email = $client->getUserEmail($info);

// Get user account label (name)
$label = $client->getUserLabel($info);

// $client is a normal guzzle client, so we can talk to any endpoint.
$data = $client->get('me/likes')->send()->json();
```
