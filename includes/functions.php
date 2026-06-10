<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Dhaka");

function data_path(string $file): string {
    return __DIR__ . "/../data/" . $file;
}

function read_json(string $file, array $default = []): array {
    $path = data_path($file);

    if (!file_exists($path)) {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $default;
    }

    $content = file_get_contents($path);
    $data = json_decode($content, true);

    return is_array($data) ? $data : $default;
}

function write_json(string $file, array $data): void {
    $path = data_path($file);

    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function clean($value): string {
    return trim((string)$value);
}

function clean_email($email): string {
    return strtolower(trim((string)$email));
}

function clean_phone($phone): string {
    return preg_replace('/[^0-9+]/', '', trim((string)$phone));
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim($text, '-');
    return $text ?: "item-" . time();
}

function redirect(string $url): never {
    header("Location: " . $url);
    exit;
}

function flash(string $type, string $message): void {
    $_SESSION["flash"] = ["type" => $type, "message" => $message];
}

function get_flash(): ?array {
    if (!isset($_SESSION["flash"])) return null;
    $flash = $_SESSION["flash"];
    unset($_SESSION["flash"]);
    return $flash;
}


function show_flash(): void {
    $flash = get_flash();
    if (!$flash) return;
    $type = e($flash["type"] ?? "info");
    $message = e($flash["message"] ?? "");
    echo '<div class="flash flash-' . $type . '">' . $message . '</div>';
}

function make_id(string $prefix): string {
    return $prefix . date("YmdHis") . random_int(100, 999);
}

function money($amount): string {
    $amount = (float)$amount;
    if (floor($amount) == $amount) return number_format($amount, 0) . " tk";
    return number_format($amount, 2) . " tk";
}

function status_class(string $status): string {
    $status = strtolower(trim($status));
    if (in_array($status, ["completed", "approved", "active"], true)) return "success";
    if (in_array($status, ["cancelled", "rejected", "banned", "inactive"], true)) return "danger";
    if ($status === "processing") return "info";
    return "warning";
}

/* USERS */
function users(): array { return read_json("users.json", []); }
function save_users(array $users): void { write_json("users.json", $users); }

function find_user(string $email): array {
    foreach (users() as $index => $user) {
        if (($user["email"] ?? "") === $email) return [$index, $user];
    }
    return [null, null];
}

function current_user(): ?array {
    if (empty($_SESSION["user_email"])) return null;
    [, $user] = find_user($_SESSION["user_email"]);
    return $user ?: null;
}

function require_login(): void {
    if (!current_user()) {
        flash("error", "Please login first.");
        redirect("login.php");
    }
}

/* ADMIN */
function admins(): array { return read_json("admins.json", []); }
function save_admins(array $admins): void { write_json("admins.json", $admins); }

function find_admin(string $username): array {
    foreach (admins() as $index => $admin) {
        if (($admin["username"] ?? "") === $username) return [$index, $admin];
    }
    return [null, null];
}

function current_admin(): ?array {
    if (empty($_SESSION["admin_username"])) return null;
    [, $admin] = find_admin($_SESSION["admin_username"]);
    return $admin ?: null;
}

function require_admin(): void {
    if (!current_admin()) {
        flash("error", "Admin login required.");
        redirect("login.php");
    }
}

/* CATEGORIES */
function categories(): array { return read_json("categories.json", []); }
function save_categories(array $categories): void { write_json("categories.json", $categories); }

function find_category(string $slug): array {
    foreach (categories() as $index => $cat) {
        if (($cat["slug"] ?? "") === $slug) return [$index, $cat];
    }
    return [null, null];
}

/* PRODUCTS */
function products(): array { return read_json("products.json", []); }
function save_products(array $products): void { write_json("products.json", $products); }

function find_product(string $slug): array {
    foreach (products() as $index => $product) {
        if (($product["slug"] ?? "") === $slug) return [$index, $product];
    }
    return [null, null];
}

function parse_packages(string $text): array {
    $items = [];
    $lines = preg_split("/\r\n|\n|\r/", $text);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "") continue;

        $parts = array_map("trim", explode("|", $line));
        $name = $parts[0] ?? "";
        $price = (float)($parts[1] ?? 0);

        if ($name !== "" && $price >= 0) {
            $items[] = ["name" => $name, "price" => $price];
        }
    }

    return $items;
}

function packages_to_text(array $packages): string {
    $lines = [];
    foreach ($packages as $pkg) {
        $lines[] = ($pkg["name"] ?? "") . " | " . ($pkg["price"] ?? 0);
    }
    return implode("\n", $lines);
}

/* ORDERS */
function orders(): array { return read_json("orders.json", []); }
function save_orders(array $orders): void { write_json("orders.json", $orders); }

function user_orders(string $email): array {
    return array_values(array_filter(orders(), fn($order) => ($order["user_email"] ?? "") === $email));
}

/* DEPOSITS / ADD MONEY */
function deposits(): array { return read_json("deposits.json", []); }
function save_deposits(array $items): void { write_json("deposits.json", $items); }

function user_deposits(string $email): array {
    return array_values(array_filter(deposits(), fn($deposit) => ($deposit["user_email"] ?? "") === $email));
}


/* SETTINGS */
function default_settings(): array {
    return [
        "bkash_number" => "01349723513",
        "support_whatsapp" => "01349723513",
        "support_email" => "support@90ngameshop.com",
        "site_notice" => "",
        "notice_active" => false,
        "active_message" => "আপনার account active আছে।",
        "inactive_message" => "আপনার account টি inactive. Regular hon.",
        "banned_message" => "আপনার account টি banned করা হয়েছে। এই account দিয়ে আর order/add money করা যাবে না। বিস্তারিত জানতে support number এ contact করুন।"
    ];
}
function site_settings(): array {
    return array_merge(default_settings(), read_json("settings.json", []));
}
function save_settings(array $settings): void {
    write_json("settings.json", array_merge(site_settings(), $settings));
}

/* COUPONS */
function coupons(): array { return read_json("coupons.json", []); }
function save_coupons(array $coupons): void { write_json("coupons.json", array_values($coupons)); }
function find_coupon(string $code): ?array {
    $code = strtoupper(trim($code));
    foreach (coupons() as $coupon) {
        if (strtoupper($coupon["code"] ?? "") === $code && !empty($coupon["active"])) {
            return $coupon;
        }
    }
    return null;
}
function coupon_discount(float $price, string $code): array {
    $coupon = find_coupon($code);
    if (!$coupon) {
        return [0, null];
    }
    if (function_exists("coupon_is_valid") && !coupon_is_valid($coupon)) {
        return [0, null];
    }
    $percent = max(0, min(100, (float)($coupon["percent"] ?? 0)));
    $discount = round(($price * $percent) / 100, 2);
    return [$discount, $coupon];
}


function coupon_is_valid(array $coupon): bool {
    if (array_key_exists("active", $coupon) && !$coupon["active"]) {
        return false;
    }
    $expires = trim((string)($coupon["expires_at"] ?? ""));
    if ($expires !== "") {
        $today = date("Y-m-d");
        if ($expires < $today) {
            return false;
        }
    }
    return true;
}
