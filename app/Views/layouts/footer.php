</main>

<footer class="main-footer">
    <div>
        © <?= date('Y') ?> <strong><?= Config::APP_NAME ?></strong>. All rights reserved.
    </div>
    <div>
        ❤️ <?= Config::APP_TAGLINE ?>
    </div>
</footer>

<!-- Leaflet JS (Local Asset) -->
<script src="<?= Config::baseUrl('assets/vendor/leaflet/leaflet.js') ?>"></script>

<!-- Main App Scripts -->
<script src="<?= Config::baseUrl('assets/js/app.js') ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/app.js') ?>"></script>

</body>
</html>
