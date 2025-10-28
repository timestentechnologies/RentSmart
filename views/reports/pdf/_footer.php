    </div> <!-- Close content-wrapper -->
    
    <htmlpagefooter name="reportfooter">
        <div class="pdf-footer">
            <div class="footer-content">
                <div class="footer-left">
                    <span class="footer-company"><?= htmlspecialchars($data['settings']['site_name'] ?? 'RentSmart') ?></span> Property Management System<br>
                    © <?= date('Y') ?> <?= htmlspecialchars($data['settings']['site_name'] ?? 'RentSmart') ?>. All rights reserved.
                </div>
                <div class="footer-right">
                    <strong>Page {PAGENO} of {nbpg}</strong><br>
                    <small>Report ID: <?= strtoupper(substr(md5(time()), 0, 8)) ?></small>
                </div>
            </div>
        </div>
    </htmlpagefooter>
</body>
</html>
