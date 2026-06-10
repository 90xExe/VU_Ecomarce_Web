<?php $settings = function_exists("site_settings") ? site_settings() : []; ?>
<footer class="site-footer">
    <div class="footer-inner support-footer">
        <p>© <?= date("Y") ?> 90N.GameShop — Fast wallet based top-up system.</p>
        <div>
            <span>Support WhatsApp: <b><?= e($settings["support_whatsapp"] ?? "") ?></b></span>
            <span>Email: <b><?= e($settings["support_email"] ?? "") ?></b></span>
        </div>
    </div>
</footer>
<script src="/Eco_Website/assets/js/app.js?v=5001"></script>
</body>
</html>
