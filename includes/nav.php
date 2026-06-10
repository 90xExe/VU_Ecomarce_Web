<?php
$user = current_user();
?>
<header class="simple-site-header">
    <a href="index.php" class="simple-logo">
        <span>▦</span>
        <strong>90N.GameShop</strong>
    </a>

    <nav class="simple-nav">
        <a href="index.php">Home</a>
        <a href="index.php#free-fire">Topup</a>

        <?php if ($user): ?>
            <a href="profile.php">Profile</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="register.php">Register</a>
            <a class="login-pill" href="login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>
