<?php
session_start();
require __DIR__ . "/vendor/autoload.php";

$client = new Google\Client;
$client->setClientId("x");
$client->setClientSecret("x");
$client->setRedirectUri("x");

if (isset($_GET['code'])) {
    // Get the authorization code
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    // Set the access token
    $client->setAccessToken($token['access_token']);
    
    // Fetch the user's profile data
    $oauth = new Google\Service\Oauth2($client);
    $userinfo = $oauth->userinfo->get();
    
    // Save user data in session
    $_SESSION['google_id'] = $userinfo->id;
    $_SESSION['access_token'] = $token['access_token'];
    $_SESSION['name'] = $userinfo->name;
    $_SESSION['email'] = $userinfo->email;
    $_SESSION['picture'] = $userinfo->picture;

    // Redirect to the main page or dashboard after login
    header('Location: index.php');
    exit();
} else {
    // If no code is found, redirect to the login page
    header('Location: login.php');
    exit();
}
