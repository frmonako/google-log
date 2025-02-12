<?php
session_start();
require __DIR__ . "/vendor/autoload.php";

// Database connection
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

// Check if user is logged in
$isLoggedIn = isset($_SESSION['google_id']);
$userinfo = null;
if ($isLoggedIn) {
    $client = new Google\Client;
    $client->setClientId("x");
    $client->setClientSecret("x");
    $client->setAccessToken($_SESSION['access_token']);
    $oauth = new Google\Service\Oauth2($client);
    $userinfo = $oauth->userinfo->get();
}

// Fetch products from the database
$stmt = $pdo->prepare("SELECT * FROM products");
$stmt->execute();
$products = $stmt->fetchAll();

// Handle adding to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn && isset($_POST['product_id'])) {
    $productId = $_POST['product_id'];
    $googleId = $_SESSION['google_id'];

    // Check if the product already exists in the user's cart
    $stmt = $pdo->prepare("SELECT id FROM google_cart WHERE google_id = :google_id AND product_id = :product_id");
    $stmt->execute(['google_id' => $googleId, 'product_id' => $productId]);
    $cartItem = $stmt->fetch();

    if ($cartItem) {
        // If item exists, update the quantity
        $stmt = $pdo->prepare("UPDATE google_cart SET quantity = quantity + 1 WHERE id = :id");
        $stmt->execute(['id' => $cartItem['id']]);
    } else {
        // Insert new item into the cart
        $stmt = $pdo->prepare("INSERT INTO google_cart (google_id, product_id, quantity) VALUES (:google_id, :product_id, 1)");
        $stmt->execute(['google_id' => $googleId, 'product_id' => $productId]);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Page</title>
</head>
<body>
    <div style="text-align: right; padding: 10px;">
        <?php if ($isLoggedIn && $userinfo): ?>
            <img src="<?= $userinfo->picture ?>" alt="Profile Picture" style="width: 40px; height: 40px; border-radius: 50%;">
            <span><?= $userinfo->name ?></span>
        <?php else: ?>
            <a href="login.php">Login with Google</a>
        <?php endif; ?>
    </div>

    <h1>Products</h1>
    <ul>
        <?php foreach ($products as $product): ?>
            <li>
                <img src="<?= $product['image'] ?>" alt="<?= $product['name'] ?>" style="width: 100px; height: 100px;">
                <strong><?= $product['name'] ?></strong> - $<?= $product['price'] ?>
                <?php if ($isLoggedIn): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit">Add to Cart</button>
                    </form>
                <?php else: ?>
                    <span>Please log in to add to the cart</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Cart</h2>
    <?php if ($isLoggedIn): ?>
        <ul>
            <?php
            $stmt = $pdo->prepare("SELECT google_cart.*, products.name, products.price, products.image FROM google_cart JOIN products ON google_cart.product_id = products.id WHERE google_cart.google_id = :google_id");
            $stmt->execute(['google_id' => $userinfo->id]);
            $cartItems = $stmt->fetchAll();

            if ($cartItems): 
                foreach ($cartItems as $item):
            ?>
                <li>
                    <img src="<?= $item['image'] ?>" alt="<?= $item['name'] ?>" style="width: 50px; height: 50px;">
                    <?= $item['name'] ?> - $<?= $item['price'] ?> (x<?= $item['quantity'] ?>)
                </li>
            <?php endforeach; else: ?>
                <li>Your cart is empty.</li>
            <?php endif; ?>
        </ul>
    <?php else: ?>
        <p>You must be logged in to see your cart.</p>
    <?php endif; ?>
</body>
</html>
