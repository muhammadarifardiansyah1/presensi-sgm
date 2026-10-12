</main>

<footer class="main-footer">
    <div>
        © <?= date('Y') ?> <strong><?= Config::APP_NAME ?></strong>. All rights reserved.
    </div>
    <div>
        ❤️ <?= Config::APP_TAGLINE ?>
    </div>
</footer>

<!-- Modal Konfirmasi Global (In-App Dialog) -->
<div id="globalConfirmModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="globalConfirmTitle">
    <div class="modal-card modal-confirm" style="max-width: 440px !important;">
        <div id="globalConfirmIcon" class="modal-icon-danger">
            <!-- Dynamic icon inserted by JS -->
        </div>
        <h3 id="globalConfirmTitle" class="modal-title">Konfirmasi Tindakan</h3>
        <p id="globalConfirmDesc" class="modal-desc">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" id="globalConfirmBtnCancel">Batal</button>
            <button type="button" class="btn btn-danger" id="globalConfirmBtnOk">Lanjutkan</button>
        </div>
    </div>
</div>

<!-- Global Floating Toast Container -->
<div id="sgmToastContainer" class="sgm-toast-container" aria-live="polite"></div>

</body>
</html>

