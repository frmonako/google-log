<?php

require __DIR__ . "/vendor/autoload.php";

$client = new Google\Client;

$client->setClientId("client id");
$client->setClientSecret("client secret");
$client->setRedirectUri("redirect url");

$client->addScope("email");
$client->addScope("profile");

$url = $client->createAuthUrl();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Google Login Example</title>
</head>
<body>

    <a href="<?= $url ?>">Sign in with Google</a>

</body>
</html>