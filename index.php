<?php
require_once __DIR__ . "/includes/functions.php";
$page_title = "90N.GameShop - Free Fire Top Up";
$body_class = "public-page dark-shop";
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/nav.php";

$allProducts = array_values(array_filter(products(), fn($p) => !empty($p["active"])));
$cats = array_values(array_filter(categories(), fn($c) => !isset($c["active"]) || !empty($c["active"])));
?>
<main class="shop-main">
    <section class="dashboard-hero">
        <div>
            <p>GAME TOPUP STORE</p>
            <h1>90N.GameShop</h1>
            <span>Wallet diye fast top-up order korun.</span>
        </div>
        <a class="hero-btn" href="<?= current_user() ? 'profile.php' : 'login.php' ?>">
            <?= current_user() ? 'Open Profile' : 'Login Now' ?>
        </a>
    </section>

    <?php foreach ($cats as $cat): ?>
        <?php
            $items = array_values(array_filter($allProducts, fn($p) => ($p["category"] ?? "free-fire") === ($cat["slug"] ?? "")));
            if (!$items) continue;
        ?>
        <section class="glass-panel" id="<?= e($cat["slug"]) ?>">
            <div class="section-title">
                <div>
                    <p>TOPUP CATEGORY</p>
                    <h2><?= e($cat["name"]) ?></h2>
                </div>
                <span><?= count($items) ?> Services</span>
            </div>

            <div class="dark-product-grid">
                <?php foreach ($items as $product): ?>
                    <a class="dark-product-card" href="topup.php?item=<?= e($product["slug"]) ?>">
                        <div class="card-img-wrap">
                            <img src="<?= e($product["image"]) ?>" alt="<?= e($product["name"]) ?>">
                        </div>
                        <div class="product-info">
                            <h3><?= e($product["name"]) ?></h3>
                            <p><?= e($product["type"] ?? "Game / Top up") ?></p>
                            <span>Order Now →</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if (!$allProducts): ?>
        <section class="glass-panel">
            <div class="section-title">
                <div>
                    <p>EMPTY</p>
                    <h2>No product added yet</h2>
                </div>
            </div>
            <p class="soft-text">Admin panel theke category, product and package add korun.</p>
        </section>
    <?php endif; ?>

    <section class="glass-panel guide-panel">
        <div class="section-title">
            <div>
                <p>HOW IT WORKS</p>
                <h2>Order Process</h2>
            </div>
        </div>
        <div class="steps-grid">
            <div><b>1</b><h3>Create Account</h3><p>Register kore profile open korba.</p></div>
            <div><b>2</b><h3>Add Wallet Money</h3><p>Profile page theke wallet balance add korba.</p></div>
            <div><b>3</b><h3>Select Package</h3><p>Topup service select kore UID diba.</p></div>
            <div><b>4</b><h3>Buy With Wallet</h3><p>Order confirm hole wallet theke taka cut hobe.</p></div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . "/includes/footer.php"; ?>
