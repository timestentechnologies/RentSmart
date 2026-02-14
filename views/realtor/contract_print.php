<?php
ob_start();

$contractId = (int)($contract['id'] ?? 0);
$termsType = (string)($contract['terms_type'] ?? 'one_time');
$totalAmount = (float)($contract['total_amount'] ?? 0);
$duration = (int)($contract['duration_months'] ?? 0);
$monthly = (float)($contract['monthly_amount'] ?? 0);
$startMonth = (string)($contract['start_month'] ?? '');
$instructions = (string)($contract['instructions'] ?? '');

$clientName = (string)($client['name'] ?? '');
$clientPhone = (string)($client['phone'] ?? '');
$clientEmail = (string)($client['email'] ?? '');

$listingTitle = (string)($listing['title'] ?? '');
$listingLocation = (string)($listing['location'] ?? '');

?>

<style>
@media print {
  .no-print { display: none !important; }
  body { background: #fff !important; }
}

.print-wrap{
  max-width: 900px;
  margin: 0 auto;
  padding: 24px;
  background: #fff;
}

.hdr{
  display:flex;
  justify-content: space-between;
  gap:16px;
  align-items:flex-start;
  border-bottom:1px solid #e5e7eb;
  padding-bottom:12px;
  margin-bottom:16px;
}

.hdr h1{ font-size:20px; margin:0; }
.small{ color:#6b7280; font-size:12px; }

.kv{ margin:0; padding:0; list-style:none; }
.kv li{ display:flex; gap:10px; padding:4px 0; }
.kv b{ width: 160px; }

.section{ margin-top:16px; }
.section h2{ font-size:14px; margin:0 0 8px 0; }
.box{ border:1px solid #e5e7eb; border-radius:8px; padding:12px; }

</style>

<div class="print-wrap">
  <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/realtor/contracts/show/<?= (int)$contractId ?>">Back</a>
    <button class="btn btn-sm btn-primary" onclick="window.print()">Print</button>
  </div>

  <div class="hdr">
    <div>
      <h1>Contract #<?= (int)$contractId ?></h1>
      <div class="small">Generated: <?= date('Y-m-d H:i') ?></div>
    </div>
    <div class="small" style="text-align:right;">
      <div><?= htmlspecialchars((string)(($_SESSION['user_name'] ?? 'Realtor'))) ?></div>
      <div><?= htmlspecialchars((string)(($_SESSION['user_email'] ?? ''))) ?></div>
    </div>
  </div>

  <div class="section">
    <h2>Client</h2>
    <div class="box">
      <ul class="kv">
        <li><b>Name</b><span><?= htmlspecialchars($clientName) ?></span></li>
        <li><b>Phone</b><span><?= htmlspecialchars($clientPhone) ?></span></li>
        <li><b>Email</b><span><?= htmlspecialchars($clientEmail) ?></span></li>
      </ul>
    </div>
  </div>

  <div class="section">
    <h2>Listing</h2>
    <div class="box">
      <ul class="kv">
        <li><b>Title</b><span><?= htmlspecialchars($listingTitle) ?></span></li>
        <li><b>Location</b><span><?= htmlspecialchars($listingLocation) ?></span></li>
      </ul>
    </div>
  </div>

  <div class="section">
    <h2>Payment Terms</h2>
    <div class="box">
      <ul class="kv">
        <li><b>Mode</b><span><?= htmlspecialchars($termsType === 'monthly' ? 'Monthly' : 'One Time') ?></span></li>
        <?php if ($termsType === 'monthly'): ?>
          <li><b>Start Month</b><span><?= htmlspecialchars(substr($startMonth, 0, 7)) ?></span></li>
          <li><b>Duration</b><span><?= (int)$duration ?> months</span></li>
          <li><b>Monthly Amount</b><span>Ksh<?= number_format((float)$monthly, 2) ?></span></li>
        <?php endif; ?>
        <li><b>Total Amount</b><span>Ksh<?= number_format((float)$totalAmount, 2) ?></span></li>
      </ul>
    </div>
  </div>

  <div class="section">
    <h2>Instructions / Notes</h2>
    <div class="box" style="min-height:90px; white-space:pre-wrap;">
      <?= htmlspecialchars($instructions) ?>
    </div>
  </div>

  <div class="section">
    <h2>Signatures</h2>
    <div class="box">
      <div style="display:flex; gap:24px;">
        <div style="flex:1;">
          <div class="small">Client Signature</div>
          <div style="border-bottom:1px solid #111; height:24px;"></div>
          <div class="small" style="margin-top:6px;">Date</div>
          <div style="border-bottom:1px solid #111; height:24px;"></div>
        </div>
        <div style="flex:1;">
          <div class="small">Realtor Signature</div>
          <div style="border-bottom:1px solid #111; height:24px;"></div>
          <div class="small" style="margin-top:6px;">Date</div>
          <div style="border-bottom:1px solid #111; height:24px;"></div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
