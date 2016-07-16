<?php

use Rippler\Models\User;
use Facebook\Authentication\AccessToken;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

$app->get('/login', function (ServerRequestInterface $request, ResponseInterface $response) {

    $app_id = "423432704517027";
    $app_secret = "b23059f536307fa4ebe8d4a5e6ba7d11";

    $fb = new Facebook\Facebook([
      'app_id' => $app_id,
      'app_secret' => $app_secret,
      'default_graph_version' => 'v2.2',
    ]);

    $access_token_string = $request->getQueryParams()['access_token'];

    $accessToken = new AccessToken($access_token_string);

    $oAuth2Client = $fb->getOAuth2Client();
    $tokenMetadata = $oAuth2Client->debugToken($accessToken);
    $tokenMetadata->validateAppId($app_id);
    $tokenMetadata->validateExpiration();

    if (!$accessToken->isLongLived())
    {
        $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
    }

    $fb_response = $fb->get('/me?fields=email,name,picture,birthday', $accessToken);

    $profile = $fb_response->getGraphNode();

    $user = User::where('fbid', '=', $profile->getField('id'))->first();

    if (empty($user))
    {
        $user = new User();
        $user->fbid = $profile->getField('id');
        $user->name = $profile->getField('name');
        $user->email = $profile->getField('email');
        $user->birthday = $profile->getField('birthday')->format('Y-m-d');
        $user->save();
    }

    $_SESSION['fb_access_token'] = (string) $accessToken;

    return $response->withJson($user);
});

