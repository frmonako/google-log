<?php

require __DIR__ . "/vendor/autoload.php";

$client = new Google\Client;

$client->setClientId("client id");
$client->setClientSecret("client secret");
$client->setRedirectUri("redirect url");

if (!isset($_GET["code"])) {
    exit("Login failed");
}

$token = $client->fetchAccessTokenWithAuthCode($_GET["code"]);
$client->setAccessToken($token["access_token"]);

$oauth = new Google\Service\Oauth2($client);
$userinfo = $oauth->userinfo->get();

// Database connection (adjust with your credentials)
$host = 'localhost'; 
$db = 'cnx_db'; 
$user = 'root'; 
$pass = ''; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Check if the user already exists based on their Google ID (optional, to avoid duplicates)
$stmt = $pdo->prepare("SELECT id FROM google_logins WHERE google_id = :google_id");
$stmt->execute(['google_id' => $userinfo->id]);
$userExists = $stmt->fetch();

if (!$userExists) {
    // Save user's information to the database
    $stmt = $pdo->prepare("INSERT INTO google_logins (google_id, email, family_name, given_name, full_name) 
                           VALUES (:google_id, :email, :family_name, :given_name, :full_name)");
    $stmt->execute([
        'google_id' => $userinfo->id,
        'email' => $userinfo->email,
        'family_name' => $userinfo->familyName,
        'given_name' => $userinfo->givenName,
        'full_name' => $userinfo->name
    ]);
}

// Debug output
var_dump(
    $userinfo->email,
    $userinfo->familyName,
    $userinfo->givenName,
    $userinfo->name
);

?>
