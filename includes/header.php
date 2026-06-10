<?php
$page_title = $page_title ?? "90N.GameShop";
$body_class = $body_class ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <link rel="stylesheet" href="/Eco_Website/assets/css/style.css?v=5002">
</head>
<body class="<?= e($body_class) ?>">
<?php
$__logged_user = function_exists("current_user") ? current_user() : null;
$__settings = function_exists("site_settings") ? site_settings() : [];
$__support = $__settings["support_whatsapp"] ?? "";
?>

<?php if ($__logged_user && (($__logged_user["status"] ?? "active") === "inactive")): ?>
    <div class="account-status-notice inactive">
        <?= e($__settings["inactive_message"] ?? "আপনার account টি inactive. Regular hon.") ?> — WhatsApp: <?= e($__support) ?>
    </div>
<?php endif; ?>

<?php if ($__logged_user && (($__logged_user["status"] ?? "active") === "banned")): ?>
    <div class="ban-modal-backdrop" id="banModal">
        <div class="ban-modal ban-popup-card">
            <button type="button" class="modal-x" onclick="document.getElementById('banModal').style.display='none'">×</button>
            <h2>Account Banned</h2>
            <p><?= e($__settings["banned_message"] ?? "আপনার account টি banned করা হয়েছে।") ?></p>
            <div class="modal-info-box">WhatsApp: <b><?= e($__support) ?></b></div>
            <?php if ($__support): ?>
                <a class="green-full-btn" target="_blank" href="https://wa.me/88<?= e(preg_replace('/\D+/', '', $__support)) ?>">Contact Support</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($__settings["notice_active"]) && !empty($__settings["site_notice"])): ?>
    <button type="button" class="notification-float-btn" onclick="document.getElementById('noticeModal').classList.add('show')" title="Notifications">
        🔔
        <span></span>
    </button>

    <div class="notice-modal-backdrop" id="noticeModal">
        <div class="notice-modal-card">
            <div class="notice-modal-head">
                <h2>🔔 Notifications</h2>
                <button type="button" onclick="document.getElementById('noticeModal').classList.remove('show')">×</button>
            </div>

            <div class="notice-message-box">
                <small>Server Update</small>
                <p><?= nl2br(e($__settings["site_notice"])) ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>
