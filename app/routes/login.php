<?php

use Rippler\Models\User;
use Rippler\Components\FacebookClient;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

$app->get('/login', function (ServerRequestInterface $request, ResponseInterface $response) {

    $fb = new FacebookClient();

    $access_token_string = $request->getQueryParams()['access_token'];
    $access_token = $fb->getAccessTokenFromString($access_token_string);
    $profile = $fb->getUserProfile($access_token);

    $user = User::where('fbid', '=', $profile->getField('id'))->first();

    if (empty($user))
    {
        $user = new User();
        $user->fbid = $profile->getField('id');
        $user->email = $profile->getField('email');
        $user->last_name = $profile->getField('last_name');
        $user->first_name = $profile->getField('first_name');
        $user->birthday = $profile->getField('birthday')->format('Y-m-d');
        $user->save();
    }

    $this->session->start($user->id);
    $this->session->set('fb_access_token', (string) $access_token);

    return $response->withJson(['session_id' => $this->session->id()]);
});

$app->get('/weblogin', function (ServerRequestInterface $request, ResponseInterface $response) {

    $fb = new FacebookClient();

    $get_token_url = $fb->tokenUrl($request->getUri() . '/adapter');

    return $response->withHeader('Location', $get_token_url)->withStatus(302);
});

$app->get('/weblogin/adapter', function (ServerRequestInterface $request, ResponseInterface $response) {

    $fb = new FacebookClient();

    $base_url = $request->getUri()->getBaseUrl();
    $access_token = $fb->getAccessTokenFromRedirect();
    $login_url = "$base_url/login?access_token=$access_token";

    return $response->withHeader('Location', $login_url)->withStatus(302);
});

$app->get('/test_login', function (ServerRequestInterface $request, ResponseInterface $response) {

    if (!getenv('ALLOW_TEST_LOGIN'))
    {
        return $response->withStatus(401);
    }

    $user = new User();
    $user->fbid = '1243543554';
    $user->email = 'test@test.com';
    $user->last_name = 'McDonald';
    $user->first_name = 'Ronald';
    $user->birthday = '1985-05-25';
    $user->save();

    $this->session->start($user->id);
});

