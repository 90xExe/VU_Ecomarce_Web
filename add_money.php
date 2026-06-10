<?php
require_once __DIR__ . "/includes/functions.php";
require_login();

$user = current_user();

if (($user["status"] ?? "active") === "banned") {
    flash("error", "This account is banned. Please contact support.");
    redirect("profile.php");
}

if (($user["status"] ?? "active") === "inactive") {
    flash("error", "This account is inactive. Please contact support / regular hon.");
    redirect("profile.php");
}
$amount = (float)($_GET["amount"] ?? $_POST["amount"] ?? 0);
$settings = site_settings();
$merchant_number = $settings["bkash_number"] ?? "01349723513";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $trxid = clean($_POST["trxid"] ?? "");
    $amount = (float)($_POST["amount"] ?? 0);

    if ($amount <= 0) {
        flash("error", "Please enter a valid amount.");
        redirect("profile.php");
    }

    if ($trxid === "") {
        flash("error", "Please enter your bKash Transaction ID.");
        redirect("add_money.php?amount=" . urlencode((string)$amount));
    }

    $deposits = deposits();
    $deposits[] = [
        "id" => make_id("DEP"),
        "user_email" => $user["email"],
        "user_name" => $user["name"],
        "phone" => $user["phone"] ?? "",
        "amount" => $amount,
        "method" => "bKash Send Money",
        "merchant_number" => $merchant_number,
        "trxid" => $trxid,
        "status" => "pending",
        "created_at" => date("d M Y, h:i A")
    ];
    save_deposits($deposits);

    flash("success", "Add money request submitted. It is now pending.");
    redirect("profile.php");
}

if ($amount <= 0) {
    flash("error", "Please enter amount first.");
    redirect("profile.php");
}

$page_title = "Add Money - 90N.GameShop";
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>

    <style>
        @font-face {
            font-family: "GFF";
            src: url("assets/css/GFF-Latin-Bold.ttf") format("truetype");
            font-display: swap;
        }

        * {
            box-sizing: border-box;
            font-family: "GFF", Arial, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(156, 255, 87, .16), transparent 30%),
                radial-gradient(circle at top right, rgba(77, 163, 255, .18), transparent 35%),
                linear-gradient(135deg, #07111d, #102943);
            color: #eef6ff;
        }

        .payment-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px 15px;
        }

        .payment-card {
            width: min(650px, 100%);
            border-radius: 26px;
            overflow: hidden;
            background: linear-gradient(145deg, rgba(13, 27, 45, .96), rgba(16, 38, 62, .94));
            border: 1px solid rgba(255, 255, 255, .12);
            box-shadow: 0 30px 80px rgba(0, 0, 0, .38);
        }

        .payment-top {
            padding: 18px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(0, 0, 0, .18);
            border-bottom: 1px solid rgba(255, 255, 255, .10);
        }

        .icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            text-decoration: none;
            color: #eef6ff;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .10);
            transition: .2s;
            font-size: 22px;
        }

        .icon-btn:hover {
            background: rgba(156, 255, 87, .15);
            border-color: rgba(156, 255, 87, .35);
            color: #9cff57;
        }

        .payment-title {
            text-align: center;
        }

        .payment-title small {
            display: block;
            color: #9cff57;
            letter-spacing: 1.3px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .payment-title strong {
            display: block;
            margin-top: 4px;
            font-size: 18px;
        }

        .payment-body {
            padding: 28px;
        }

        .brand-logo {
            width: 105px;
            height: 105px;
            margin: 0 auto 22px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at 30% 20%, #ff6aa9, #d62575 55%, #9b174f);
            color: #fff;
            font-size: 25px;
            box-shadow: 0 18px 45px rgba(214, 37, 117, .35);
        }

        .summary-row {
            display: grid;
            grid-template-columns: 1fr 150px;
            gap: 14px;
            margin-bottom: 20px;
        }

        .merchant-box,
        .amount-box {
            min-height: 86px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, .12);
            background: rgba(255, 255, 255, .06);
            display: flex;
            align-items: center;
        }

        .merchant-box {
            gap: 14px;
            padding: 0 18px;
        }

        .merchant-avatar {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #9cff57, #63d24a);
            color: #07111d;
            font-weight: 900;
        }

        .merchant-box small {
            color: #9eb0cc;
            display: block;
            margin-top: 5px;
        }

        .amount-box {
            justify-content: center;
            flex-direction: column;
        }

        .amount-box small {
            color: #9eb0cc;
            margin-bottom: 5px;
        }

        .amount-box strong {
            color: #9cff57;
            font-size: 28px;
        }

        .instruction-panel {
            padding: 22px;
            border-radius: 22px;
            background: linear-gradient(145deg, #d62575, #bd145f);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .18);
        }

        .instruction-panel h2 {
            text-align: center;
            margin: 0 0 18px;
            font-size: 24px;
        }

        .trx-input {
            width: 100%;
            height: 52px;
            border: 0;
            outline: none;
            border-radius: 14px;
            padding: 0 16px;
            background: #fff;
            color: #18233a;
            font-size: 15px;
        }

        .rules {
            list-style: none;
            margin: 18px 0 22px;
            padding: 0;
        }

        .rules li {
            display: flex;
            gap: 10px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(90, 0, 50, .38);
            line-height: 1.45;
            color: #fff;
        }

        .rules li::before {
            content: "•";
            color: #fff45f;
            font-size: 22px;
            line-height: 18px;
        }

        .rules b {
            color: #fff45f;
        }

        .copy-btn {
            border: 0;
            border-radius: 8px;
            padding: 7px 12px;
            margin-left: 6px;
            background: rgba(0, 0, 0, .25);
            color: #fff;
            cursor: pointer;
            font-weight: 700;
        }

        .copy-btn:hover {
            background: rgba(0, 0, 0, .38);
        }

        .verify-btn {
            width: 100%;
            height: 54px;
            border: 0;
            border-radius: 15px;
            background: linear-gradient(135deg, #9cff57, #63d24a);
            color: #07111d;
            cursor: pointer;
            font-size: 16px;
            font-weight: 900;
            transition: .2s;
        }

        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(156, 255, 87, .28);
        }

        .note {
            margin: 16px 0 0;
            text-align: center;
            color: #9eb0cc;
            font-size: 13px;
        }

        @media (max-width: 650px) {
            .payment-body {
                padding: 18px;
            }

            .summary-row {
                grid-template-columns: 1fr;
            }

            .brand-logo {
                width: 88px;
                height: 88px;
                font-size: 21px;
            }

            .instruction-panel {
                padding: 18px;
            }

            .instruction-panel h2 {
                font-size: 21px;
            }
        }
    </style>
</head>

<body>
    <main class="payment-wrapper">
        <section class="payment-card">
            <div class="payment-top">
                <a href="profile.php" class="icon-btn">‹</a>

                <div class="payment-title">
                    <small>Add Money</small>
                    <strong>90N.GameShop Wallet</strong>
                </div>

                <a href="profile.php" class="icon-btn">×</a>
            </div>

            <div class="payment-body">
                <div class="brand-logo">bKash</div>

                <div class="summary-row">
                    <div class="merchant-box">
                        <div class="merchant-avatar">90N</div>
                        <div>
                            <strong>90N.GameShop</strong>
                            <small>Wallet Recharge Request</small>
                        </div>
                    </div>

                    <div class="amount-box">
                        <small>Amount</small>
                        <strong>৳ <?= e(number_format($amount, 0)) ?></strong>
                    </div>
                </div>

                <form method="POST" class="instruction-panel">
                    <input type="hidden" name="amount" value="<?= e($amount) ?>">

                    <h2>ট্রানজেকশন আইডি দিন</h2>

                    <input
                        class="trx-input"
                        type="text"
                        name="trxid"
                        placeholder="এখানে Transaction ID লিখুন"
                        required
                    >

                    <ul class="rules">
                        <li>*247# ডায়াল করে অথবা bKash app থেকে <b>Send Money</b> করুন।</li>
                        <li><b>Send Money</b> অপশন সিলেক্ট করুন।</li>
                        <li>
                            প্রাপক নম্বর:
                            <b><?= e($merchant_number) ?></b>
                            <button type="button" class="copy-btn" data-copy="<?= e($merchant_number) ?>">Copy</button>
                        </li>
                        <li>টাকার পরিমাণ: <b><?= e(number_format($amount, 0)) ?></b></li>
                        <li>নিশ্চিত করুন এবং আপনার bKash PIN দিন।</li>
                        <li>তারপর Transaction ID লিখে নিচের VERIFY বাটনে ক্লিক করুন।</li>
                    </ul>

                    <button class="verify-btn" type="submit">VERIFY PAYMENT</button>
                </form>

                <p class="note">
                    Verify করার পর request pending থাকবে। Admin approve করলে wallet balance add হবে।
                </p>
            </div>
        </section>
    </main>

    <script>
        document.querySelectorAll("[data-copy]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                navigator.clipboard.writeText(btn.dataset.copy);
                btn.textContent = "Copied";
                setTimeout(function () {
                    btn.textContent = "Copy";
                }, 1200);
            });
        });
    </script>
</body>
</html>