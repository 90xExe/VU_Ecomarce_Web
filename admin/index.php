<?php
require_once __DIR__ . "/../includes/functions.php";
require_admin();

$section = clean($_GET["section"] ?? "dashboard");
if ($section === "categories") {
    $section = "products";
}
$f = get_flash();

function admin_url(string $section): string {
    return "index.php?section=" . urlencode($section);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = clean($_POST["action"] ?? "");

    if ($action === "save_coupon") {
        $code = strtoupper(clean($_POST["code"] ?? ""));
        $percent = max(0, min(100, (float)($_POST["percent"] ?? 0)));
        $active = isset($_POST["active"]);
        $expires_at = clean($_POST["expires_at"] ?? "");

        if ($code === "") {
            flash("error", "Coupon code required.");
            redirect(admin_url("coupons"));
        }

        $items = coupons();
        $found = false;
        foreach ($items as &$coupon) {
            if (strtoupper($coupon["code"] ?? "") === $code) {
                $coupon["percent"] = $percent;
                $coupon["active"] = $active;
                $coupon["expires_at"] = $expires_at;
                $coupon["updated_at"] = date("d M Y, h:i A");
                $found = true;
                break;
            }
        }
        unset($coupon);

        if (!$found) {
            $items[] = [
                "code" => $code,
                "percent" => $percent,
                "active" => $active,
                "expires_at" => $expires_at,
                "created_at" => date("d M Y, h:i A")
            ];
        }

        save_coupons($items);
        flash("success", "Coupon saved.");
        redirect(admin_url("coupons"));
    }

    if ($action === "delete_coupon") {
        $code = strtoupper(clean($_POST["code"] ?? ""));
        save_coupons(array_values(array_filter(coupons(), fn($c) => strtoupper($c["code"] ?? "") !== $code)));
        flash("success", "Coupon deleted.");
        redirect(admin_url("coupons"));
    }

    if ($action === "save_settings") {
        save_settings([
            "bkash_number" => clean_phone($_POST["bkash_number"] ?? ""),
            "support_whatsapp" => clean_phone($_POST["support_whatsapp"] ?? ""),
            "support_email" => clean_email($_POST["support_email"] ?? ""),
            "site_notice" => clean($_POST["site_notice"] ?? ""),
            "notice_active" => isset($_POST["notice_active"]),
            "active_message" => clean($_POST["active_message"] ?? ""),
            "inactive_message" => clean($_POST["inactive_message"] ?? ""),
            "banned_message" => clean($_POST["banned_message"] ?? "")
        ]);
        flash("success", "Settings saved.");
        redirect(admin_url("settings"));
    }


    if ($action === "save_category") {
        $name = clean($_POST["name"] ?? "");
        $slug = slugify(clean($_POST["slug"] ?? $name));
        $active = isset($_POST["active"]);

        if ($name === "") {
            flash("error", "Category name required.");
            redirect(admin_url("categories"));
        }

        $cats = categories();
        $edit = clean($_POST["edit_slug"] ?? "");

        if ($edit !== "") {
            [$idx, $old] = find_category($edit);
            if ($idx !== null) {
                $cats[$idx] = [
                    "id" => $old["id"] ?? make_id("CAT"),
                    "name" => $name,
                    "slug" => $slug,
                    "active" => $active,
                    "created_at" => $old["created_at"] ?? date("d M Y")
                ];

                $products = products();
                foreach ($products as &$p) {
                    if (($p["category"] ?? "") === $edit) $p["category"] = $slug;
                }
                unset($p);
                save_products($products);
            }
        } else {
            $cats[] = [
                "id" => make_id("CAT"),
                "name" => $name,
                "slug" => $slug,
                "active" => $active,
                "created_at" => date("d M Y")
            ];
        }

        save_categories($cats);
        flash("success", "Category saved.");
        redirect(admin_url("categories"));
    }

    if ($action === "delete_category") {
        $slug = clean($_POST["slug"] ?? "");
        $cats = array_values(array_filter(categories(), fn($c) => ($c["slug"] ?? "") !== $slug));
        save_categories($cats);
        flash("success", "Category deleted.");
        redirect(admin_url("categories"));
    }

    if ($action === "save_product") {
        $name = clean($_POST["name"] ?? "");
        $slug = slugify(clean($_POST["slug"] ?? $name));
        $category = clean($_POST["category"] ?? "free-fire");
        $type = clean($_POST["type"] ?? "Game / Top up");
        $image = clean($_POST["image"] ?? "assets/images/product-1.png");
        $rules = clean($_POST["rules"] ?? "90N.GameShop");
        $how_to_order_url = clean($_POST["how_to_order_url"] ?? "");
        $active = isset($_POST["active"]);
        $packages = parse_packages($_POST["packages"] ?? "");

        if ($name === "" || !$packages) {
            flash("error", "Product name and at least one package required.");
            redirect(admin_url("products"));
        }

        $products = products();
        $edit = clean($_POST["edit_slug"] ?? "");

        $item = [
            "name" => $name,
            "slug" => $slug,
            "category" => $category,
            "type" => $type,
            "image" => $image,
            "rules" => $rules,
            "how_to_order_url" => $how_to_order_url,
            "active" => $active,
            "packages" => $packages
        ];

        if ($edit !== "") {
            [$idx, $old] = find_product($edit);
            if ($idx !== null) {
                $item["created_at"] = $old["created_at"] ?? date("d M Y");
                $products[$idx] = $item;
            }
        } else {
            $item["created_at"] = date("d M Y");
            $products[] = $item;
        }

        save_products($products);
        flash("success", "Product saved.");
        redirect(admin_url("products"));
    }

    if ($action === "delete_product") {
        $slug = clean($_POST["slug"] ?? "");
        $products = array_values(array_filter(products(), fn($p) => ($p["slug"] ?? "") !== $slug));
        save_products($products);
        flash("success", "Product deleted.");
        redirect(admin_url("products"));
    }

    if ($action === "update_order") {
        $id = clean($_POST["id"] ?? "");
        $status = clean($_POST["status"] ?? "pending");
        $orders = orders();

        foreach ($orders as &$order) {
            if (($order["id"] ?? "") === $id) {
                $order["status"] = $status;
                $order["updated_at"] = date("d M Y, h:i A");
                break;
            }
        }
        unset($order);

        save_orders($orders);
        flash("success", "Order status updated.");
        redirect(admin_url("orders"));
    }

    if ($action === "update_deposit") {
        $id = clean($_POST["id"] ?? "");
        $status = clean($_POST["status"] ?? "pending");
        $deposits = deposits();

        foreach ($deposits as &$deposit) {
            if (($deposit["id"] ?? "") === $id) {
                $oldStatus = $deposit["status"] ?? "pending";
                $deposit["status"] = $status;
                $deposit["updated_at"] = date("d M Y, h:i A");

                if ($oldStatus !== "approved" && $status === "approved") {
                    [$uIndex, $u] = find_user($deposit["user_email"] ?? "");
                    if ($uIndex !== null) {
                        $users = users();
                        $users[$uIndex]["balance"] = (float)($users[$uIndex]["balance"] ?? 0) + (float)($deposit["amount"] ?? 0);
                        save_users($users);
                    }
                }

                break;
            }
        }
        unset($deposit);

        save_deposits($deposits);
        flash("success", "Add money request updated.");
        redirect(admin_url("deposits"));
    }

    if ($action === "update_user") {
        $email = clean_email($_POST["email"] ?? "");
        $users = users();

        foreach ($users as &$u) {
            if (($u["email"] ?? "") === $email) {
                $u["name"] = clean($_POST["name"] ?? $u["name"]);
                $u["phone"] = clean_phone($_POST["phone"] ?? ($u["phone"] ?? ""));
                $u["balance"] = (float)($_POST["balance"] ?? ($u["balance"] ?? 0));
                $u["status"] = clean($_POST["status"] ?? "active");
                break;
            }
        }
        unset($u);

        save_users($users);
        flash("success", "User updated.");
        redirect(admin_url("users"));
    }
}

$stats = [
    "users" => count(users()),
    "products" => count(products()),
    "orders" => count(orders()),
    "deposits" => count(deposits()),
    "coupons" => count(coupons())
];

$editProduct = null;
if ($section === "products" && isset($_GET["edit"])) {
    [, $editProduct] = find_product(clean($_GET["edit"]));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control - 90N.GameShop</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=9001">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="index.php">
            <span>▦</span>
            <div>
                <strong>90N.GameShop</strong>
                <small>ADMIN CONTROL</small>
            </div>
        </a>

        <nav>
            <a class="<?= $section === 'dashboard' ? 'active' : '' ?>" href="index.php">Dashboard</a>
            <a class="<?= $section === 'products' ? 'active' : '' ?>" href="<?= admin_url("products") ?>">Products & Packages</a>
            <a class="<?= $section === 'orders' ? 'active' : '' ?>" href="<?= admin_url("orders") ?>">Topup Orders</a>
            <a class="<?= $section === 'deposits' ? 'active' : '' ?>" href="<?= admin_url("deposits") ?>">Add Money</a>
            <a class="<?= $section === 'users' ? 'active' : '' ?>" href="<?= admin_url("users") ?>">Users</a>
            <a class="<?= $section === 'coupons' ? 'active' : '' ?>" href="<?= admin_url("coupons") ?>">Coupons</a>
            <a class="<?= $section === 'settings' ? 'active' : '' ?>" href="<?= admin_url("settings") ?>">Settings</a>
        </nav>

        <div class="admin-bottom">
            <a href="../index.php">View Website</a>
            <a href="logout.php">Logout</a>
        </div>
    </aside>

    <main class="admin-main">
        <section class="admin-hero">
            <div>
                <p>BROADCAST OPERATIONS</p>
                <h1><?= e(ucwords(str_replace("_", " ", $section))) ?></h1>
            </div>
            <span>Logged in: <?= e(current_admin()["name"] ?? "Admin") ?></span>
        </section>

        <?php if ($f): ?><div class="admin-alert <?= e($f["type"]) ?>"><?= e($f["message"]) ?></div><?php endif; ?>

        <?php if ($section === "dashboard"): ?>
            <section class="stats-grid">
                <div><small>Total Users</small><strong><?= $stats["users"] ?></strong></div>
                <div><small>Products</small><strong><?= $stats["products"] ?></strong></div>
                <div><small>Orders</small><strong><?= $stats["orders"] ?></strong></div>
                <div><small>Add Money Requests</small><strong><?= $stats["deposits"] ?></strong></div>
                <div><small>Total Coupons</small><strong><?= $stats["coupons"] ?></strong></div>
            </section>

            <section class="admin-card">
                <h2>Quick Guide</h2>
                <p>Categories banan, tarpor product add korun, product-er packages line by line set korun. User wallet add-money request approve korle balance automatically add hobe.</p>
            </section>
        <?php endif; ?>
        <?php if ($section === "products"): ?>
            <section class="admin-grid two">
                <div class="admin-card">
                    <h2><?= $editProduct ? "Edit Product" : "Add New Product" ?></h2>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="save_product">
                        <input type="hidden" name="edit_slug" value="<?= e($editProduct["slug"] ?? "") ?>">

                        <label>Product Name</label>
                        <input name="name" value="<?= e($editProduct["name"] ?? "") ?>" placeholder="TOP UP BD (UID) AI" required>

                        <label>Slug</label>
                        <input name="slug" value="<?= e($editProduct["slug"] ?? "") ?>" placeholder="top-up-bd-uid-ai">

                        <label>Category</label>
                        <select name="category">
                            <?php foreach (categories() as $cat): ?>
                                <option value="<?= e($cat["slug"]) ?>" <?= (($editProduct["category"] ?? "") === ($cat["slug"] ?? "")) ? "selected" : "" ?>>
                                    <?= e($cat["name"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Type / Subtitle</label>
                        <input name="type" value="<?= e($editProduct["type"] ?? "Game / Top up") ?>">

                        <label>Image Path / URL</label>
                        <input name="image" value="<?= e($editProduct["image"] ?? "assets/images/product-1.png") ?>" placeholder="assets/images/product-1.png">

                        <label>How to Order Button Link</label>
                        <input name="how_to_order_url" placeholder="https://example.com/how-to-order">
                        <small class="soft-text">Ei link dile product page-er button oi link open korbe.</small>

                        <label>Packages <small>One line: Package Name | Price</small></label>
                        <textarea name="packages" rows="9" placeholder="25 Diamonds | 20&#10;50 Diamonds | 35" required><?= e(packages_to_text($editProduct["packages"] ?? [])) ?></textarea>

                        <label>Rules / Conditions</label>
                        <textarea name="rules" rows="4"><?= e($editProduct["rules"] ?? "90N.GameShop") ?></textarea>

                        <label class="check-row"><input type="checkbox" name="active" <?= !isset($editProduct["active"]) || !empty($editProduct["active"]) ? "checked" : "" ?>> Active</label>

                        <button type="submit"><?= $editProduct ? "Update Product" : "Add Product" ?></button>
                    </form>
                </div>

                <div class="admin-card">
                    <h2>All Products</h2>
                    <div class="admin-list product-list">
                        <?php foreach (products() as $product): ?>
                            <div class="list-row product-row">
                                <img src="../<?= e($product["image"] ?? "") ?>" alt="">
                                <div>
                                    <strong><?= e($product["name"]) ?></strong>
                                    <small><?= e($product["slug"]) ?> • <?= count($product["packages"] ?? []) ?> packages</small>
                                </div>
                                <div class="row-actions">
                                    <a href="<?= admin_url("products") ?>&edit=<?= e($product["slug"]) ?>">Edit</a>
                                    <form method="POST" onsubmit="return confirm('Delete product?');">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="slug" value="<?= e($product["slug"]) ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($section === "orders"): ?>
            <section class="admin-card">
                <h2>Topup Orders</h2>
                <div class="admin-table-wrap">
                    <table>
                        <thead><tr><th>Order ID</th><th>User</th><th>Product</th><th>Package</th><th>UID</th><th>Price</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach (array_reverse(orders()) as $order): ?>
                                <tr>
                                    <td><?= e($order["id"] ?? "") ?></td>
                                    <td><?= e($order["user_email"] ?? "") ?></td>
                                    <td><?= e($order["product"] ?? "") ?></td>
                                    <td><?= e($order["package"] ?? "") ?></td>
                                    <td><?= e($order["player_uid"] ?? "") ?></td>
                                    <td><?= money($order["price"] ?? 0) ?></td>
                                    <td><span class="badge <?= status_class($order["status"] ?? "pending") ?>"><?= e($order["status"] ?? "pending") ?></span></td>
                                    <td><?= e($order["created_at"] ?? "") ?></td>
                                    <td>
                                        <form method="POST" class="inline-form">
                                            <input type="hidden" name="action" value="update_order">
                                            <input type="hidden" name="id" value="<?= e($order["id"] ?? "") ?>">
                                            <select name="status">
                                                <?php foreach (["pending","processing","completed","cancelled"] as $s): ?>
                                                    <option value="<?= $s ?>" <?= (($order["status"] ?? "") === $s) ? "selected" : "" ?>><?= $s ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button>Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($section === "deposits"): ?>
            <section class="admin-card">
                <h2>Add Money Requests</h2>
                <div class="admin-table-wrap">
                    <table>
                        <thead><tr><th>Request ID</th><th>User</th><th>Amount</th><th>Method</th><th>TrxID</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach (array_reverse(deposits()) as $deposit): ?>
                                <tr>
                                    <td><?= e($deposit["id"] ?? "") ?></td>
                                    <td><?= e($deposit["user_email"] ?? "") ?></td>
                                    <td><?= money($deposit["amount"] ?? 0) ?></td>
                                    <td><?= e($deposit["method"] ?? "") ?></td>
                                    <td><?= e($deposit["trxid"] ?? "") ?></td>
                                    <td><span class="badge <?= status_class($deposit["status"] ?? "pending") ?>"><?= e($deposit["status"] ?? "pending") ?></span></td>
                                    <td><?= e($deposit["created_at"] ?? "") ?></td>
                                    <td>
                                        <form method="POST" class="inline-form">
                                            <input type="hidden" name="action" value="update_deposit">
                                            <input type="hidden" name="id" value="<?= e($deposit["id"] ?? "") ?>">
                                            <select name="status">
                                                <?php foreach (["pending","approved","rejected"] as $s): ?>
                                                    <option value="<?= $s ?>" <?= (($deposit["status"] ?? "") === $s) ? "selected" : "" ?>><?= $s ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button>Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($section === "users"): ?>
            <section class="admin-card">
                <h2>Users</h2>
                <div class="admin-table-wrap">
                    <table>
                        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Balance</th><th>Status</th><th>Created</th><th>Save</th></tr></thead>
                        <tbody>
                            <?php foreach (users() as $u): ?>
                                <tr>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_user">
                                        <input type="hidden" name="email" value="<?= e($u["email"] ?? "") ?>">
                                        <td><input name="name" value="<?= e($u["name"] ?? "") ?>"></td>
                                        <td><?= e($u["email"] ?? "") ?></td>
                                        <td><input name="phone" value="<?= e($u["phone"] ?? "") ?>"></td>
                                        <td><input name="balance" type="number" step="1" value="<?= e($u["balance"] ?? 0) ?>"></td>
                                        <td>
                                            <select name="status">
                                                <?php foreach (["active","inactive","banned"] as $s): ?>
                                                    <option value="<?= $s ?>" <?= (($u["status"] ?? "active") === $s) ? "selected" : "" ?>><?= $s ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><?= e($u["created_at"] ?? "") ?></td>
                                        <td><button class="mini-save">Save</button></td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($section === "coupons"): ?>
            <section class="admin-grid two">
                <div class="admin-card">
                    <h2>Add / Update Coupon</h2>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="save_coupon">

                        <label>Coupon Code</label>
                        <input name="code" placeholder="E.g. SAVE10" required>

                        <label>Discount Percent</label>
                        <input name="percent" type="number" min="0" max="100" step="1" placeholder="10" required>

                        <label>Validity Date</label>
                        <input name="expires_at" type="date">
                        <small class="soft-text">Empty রাখলে coupon lifetime active থাকবে। Date দিলে ওই date পর্যন্ত valid থাকবে।</small>

                        <label class="check-row">
                            <input type="checkbox" name="active" checked>
                            Active coupon
                        </label>

                        <button>Add / Update Coupon</button>
                    </form>
                </div>

                <div class="admin-card">
                    <h2>All Coupons</h2>
                    <div class="coupon-list-clean">
                        <?php foreach (coupons() as $coupon): ?>
                            <?php
                                $isActive = !empty($coupon["active"]);
                                $expiresAt = trim((string)($coupon["expires_at"] ?? ""));
                                $isExpired = ($expiresAt !== "" && $expiresAt < date("Y-m-d"));
                            ?>
                            <div class="coupon-card-clean">
                                <div>
                                    <div class="coupon-code-clean">
                                        <strong><?= e($coupon["code"] ?? "") ?></strong>
                                        <span class="coupon-pill <?= (!$isActive || $isExpired) ? "off" : "" ?>">
                                            <?= !$isActive ? "Inactive" : ($isExpired ? "Expired" : "Active") ?>
                                        </span>
                                    </div>
                                    <div class="coupon-meta-clean">
                                        <span>Discount: <b><?= e($coupon["percent"] ?? 0) ?>%</b></span>
                                        <span>Valid until: <b><?= $expiresAt !== "" ? e($expiresAt) : "No expiry" ?></b></span>
                                        <span>Created: <?= e($coupon["created_at"] ?? "-") ?></span>
                                    </div>
                                </div>

                                <div class="coupon-actions-clean">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="delete_coupon">
                                        <input type="hidden" name="code" value="<?= e($coupon["code"] ?? "") ?>">
                                        <button class="danger-btn" type="submit">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count(coupons()) === 0): ?>
                            <p class="soft-text">No coupon added yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($section === "settings"): ?>
            <?php $settings = site_settings(); ?>
            <section class="admin-card">
                <h2>Payment & Support Settings</h2>
                <form method="POST" class="admin-form wide-form">
                    <input type="hidden" name="action" value="save_settings">

                    <label>bKash Payment Number</label>
                    <input name="bkash_number" value="<?= e($settings["bkash_number"] ?? "") ?>" placeholder="01349723513">

                    <label>Support WhatsApp Number</label>
                    <input name="support_whatsapp" value="<?= e($settings["support_whatsapp"] ?? "") ?>" placeholder="01349723513">

                    <label>Support Gmail</label>
                    <input type="email" name="support_email" value="<?= e($settings["support_email"] ?? "") ?>" placeholder="support@gmail.com">

                    <label>Notification Message For All Users</label>
                    <textarea name="site_notice" rows="4" placeholder="Server update / maintenance notice likhun"><?= e($settings["site_notice"] ?? "") ?></textarea>

                    <label class="check-row">
                        <input type="checkbox" name="notice_active" <?= !empty($settings["notice_active"]) ? "checked" : "" ?>>
                        Show notification button/message to all users
                    </label>

                    <label>Active User Message</label>
                    <input name="active_message" value="<?= e($settings["active_message"] ?? "") ?>" placeholder="Active user message">

                    <label>Inactive User Message</label>
                    <textarea name="inactive_message" rows="3" placeholder="Inactive user message"><?= e($settings["inactive_message"] ?? "") ?></textarea>

                    <label>Banned User Popup Message</label>
                    <textarea name="banned_message" rows="4" placeholder="Banned user popup message"><?= e($settings["banned_message"] ?? "") ?></textarea>

                    <button>Save Settings</button>
                </form>
            </section>
        <?php endif; ?>

    </main>
</body>
</html>
