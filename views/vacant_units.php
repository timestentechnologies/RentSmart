<?php
// Expect: $vacantUnits, $siteName, $favicon
if (!defined('BASE_URL')) { define('BASE_URL', ''); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?> | Vacant Units</title>
    <?php $faviconUrl = site_setting_image_url('site_favicon', BASE_URL . '/public/assets/images/site_favicon_1750832003.png'); ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($faviconUrl) ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= htmlspecialchars($faviconUrl) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary-color:#6B3E99; --secondary-color:#8E5CC4; --light:#f8f9fa; }
        .hero { background:linear-gradient(135deg, #6B3E99 0%, #8E5CC4 100%); color:#fff; padding:60px 0; position:relative; overflow:hidden; }
        .hero .overlay-icon { position:absolute; right:-40px; bottom:-40px; font-size:220px; opacity:.08; }
        .hero .overlay-icon-secondary { position:absolute; right:140px; top:-30px; font-size:140px; opacity:.06; }
        .hero .overlay-icon-tertiary { position:absolute; right:320px; bottom:-10px; font-size:110px; opacity:.06; }
        .hero .overlay-pattern { position:absolute; inset:0; background: radial-gradient(circle at 20% 30%, rgba(255,255,255,.06), transparent 30%), radial-gradient(circle at 80% 20%, rgba(255,255,255,.04), transparent 30%), radial-gradient(circle at 70% 80%, rgba(255,255,255,.05), transparent 35%); pointer-events:none; }
        .card-unit { border:none; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.08); overflow:hidden; }
        .card-unit img { height:200px; object-fit:cover; width:100%; }
        .badge-rent { background:rgba(107,62,153,.1); color:#6B3E99; }
        .unit-meta i { color:#6B3E99; }
        /* Ensure carousel arrows are always visible on images */
        .card-unit .carousel-control-prev,
        .card-unit .carousel-control-next { 
            opacity: 1; 
            width: 12%;
            z-index: 3;
        }
        .card-unit .carousel-control-prev-icon,
        .card-unit .carousel-control-next-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background-color: rgba(0,0,0,0.45);
            background-size: 60% 60%;
            filter: invert(1);
            box-shadow: 0 2px 6px rgba(0,0,0,0.25);
        }
    </style>
</head>
<body>
    <?php $activePage = 'vacant_units'; require __DIR__ . '/partials/public_header.php'; ?>

    <section class="hero">
        <div class="container">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-door-open-fill"></i>
                <h1 class="display-5 fw-bold mb-0">Vacant Units</h1>
            </div>
            <p class="mb-0">Browse currently available units with pricing, address, and photos.</p>
        </div>
        <i class="bi bi-buildings overlay-icon"></i>
        <i class="bi bi-house-door overlay-icon-secondary"></i>
        <i class="bi bi-geo-alt overlay-icon-tertiary"></i>
        <div class="overlay-pattern"></div>
    </section>

    <main class="py-5">
        <div class="container">
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Location</label>
                            <input type="text" id="filterLocation" class="form-control" placeholder="e.g. Nairobi">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Budget (Ksh)</label>
                            <input type="number" id="filterBudget" class="form-control" min="0" step="1000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select id="filterType" class="form-select">
                                <option value="">Any</option>
                                <option value="studio">Studio</option>
                                <option value="1bhk">1 BHK</option>
                                <option value="2bhk">2 BHK</option>
                                <option value="3bhk">3 BHK</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" id="filterSearch" class="form-control" placeholder="Search property or unit">
                        </div>
                    </div>
                </div>
            </div>
            <?php if (empty($vacantUnits) && empty($publicRealtorListings)): ?>
                <div class="alert alert-info">No vacant units at the moment. Please check back later.</div>
            <?php else: ?>
                <?php if (!empty($publicRealtorListings)): ?>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0"><i class="bi bi-building me-2"></i>Listings</h4>
                    </div>
                    <div class="row g-4 mb-4">
                        <?php foreach (($publicRealtorListings ?? []) as $listing): ?>
                        <?php
                            $listingId = (int)($listing['id'] ?? 0);
                            $listingUserId = (int)($listing['user_id'] ?? 0);
                            $title = (string)($listing['title'] ?? 'Listing');
                            $location = (string)($listing['location'] ?? '');
                            $price = (float)($listing['price'] ?? 0);
                            $type = (string)($listing['listing_type'] ?? '');
                            $st = strtolower((string)($listing['status'] ?? 'active'));
                            $badge = 'secondary';
                            $label = $st ?: 'active';
                            if ($st === 'active') { $badge = 'success'; $label = 'Available'; }
                            elseif ($st === 'inactive') { $badge = 'secondary'; $label = 'Unavailable'; }
                        ?>
                        <div class="col-md-4 unit-card" data-location="<?= htmlspecialchars(strtolower($location)) ?>" data-type="<?= htmlspecialchars(strtolower($type)) ?>" data-rent="<?= $price ?>" data-name="<?= htmlspecialchars(strtolower($title.' '.$location)) ?>">
                            <div class="card card-unit">
                                <div class="d-flex align-items-center justify-content-center" style="height:200px;background:linear-gradient(135deg, rgba(107,62,153,.12) 0%, rgba(142,92,196,.10) 100%);">
                                    <div class="text-center">
                                        <div class="mb-2"><i class="bi bi-building" style="font-size:3rem;color:#6B3E99"></i></div>
                                        <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($label) ?></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($title) ?></h5>
                                        <span class="badge badge-rent">Ksh <?= number_format($price, 2) ?></span>
                                    </div>
                                    <p class="mb-1 unit-meta"><i class="bi bi-tag me-1"></i><strong>Type:</strong> <?= htmlspecialchars(str_replace('_', ' ', $type)) ?></p>
                                    <p class="text-muted mb-3"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($location) ?></p>
                                    <button class="btn btn-outline-primary w-100" onclick="openInquiryModalForListing(<?= $listingId ?>, <?= $listingUserId ?>, '<?= htmlspecialchars(addslashes($title)) ?>')">Contact to Apply</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($vacantUnits)): ?>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0"><i class="bi bi-door-open-fill me-2"></i>Vacant Units</h4>
                    </div>
                    <div class="row g-4">
                        <?php foreach ($vacantUnits as $unit): ?>
                        <?php
                            $addressBits = array_filter([
                                $unit['address'] ?? '',
                                $unit['city'] ?? '',
                                $unit['state'] ?? '',
                                $unit['zip_code'] ?? ''
                            ]);
                            $addressStr = implode(', ', $addressBits);
                            $images = isset($unit['images']) && is_array($unit['images']) ? $unit['images'] : [ $unit['image'] ?? '' ];
                            $carouselId = 'unitCarousel_' . (int)$unit['id'];
                        ?>
                        <div class="col-md-4 unit-card" data-location="<?= htmlspecialchars(strtolower($addressStr)) ?>" data-type="<?= htmlspecialchars(strtolower($unit['type'])) ?>" data-rent="<?= (float)$unit['rent_amount'] ?>" data-name="<?= htmlspecialchars(strtolower($unit['property_name'].' '.$unit['unit_number'])) ?>">
                            <div class="card card-unit">
                                <?php if (count($images) > 1): ?>
                                    <div id="<?= htmlspecialchars($carouselId) ?>" class="carousel slide" data-bs-ride="false">
                                        <div class="carousel-inner">
                                            <?php foreach ($images as $idx => $imgUrl): ?>
                                                <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                                                    <img src="<?= htmlspecialchars($imgUrl) ?>" class="d-block w-100" alt="<?= htmlspecialchars($unit['property_name']) ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#<?= htmlspecialchars($carouselId) ?>" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Previous</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#<?= htmlspecialchars($carouselId) ?>" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($images[0]) ?>" alt="<?= htmlspecialchars($unit['property_name']) ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($unit['property_name']) ?></h5>
                                        <span class="badge badge-rent">Ksh <?= number_format((float)$unit['rent_amount'], 2) ?></span>
                                    </div>
                                    <p class="mb-1 unit-meta"><i class="bi bi-hash me-1"></i><strong>Unit:</strong> <?= htmlspecialchars($unit['unit_number']) ?> (<?= htmlspecialchars(ucfirst($unit['type'])) ?>)</p>
                                    <p class="text-muted mb-3"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($addressStr) ?></p>
                                    <?php if (!empty($unit['caretaker_name']) || !empty($unit['caretaker_contact'])): ?>
                                        <p class="mb-3 unit-meta">
                                            <i class="bi bi-person-badge me-1"></i>
                                            <strong>Caretaker:</strong>
                                            <?= htmlspecialchars($unit['caretaker_name'] ?: 'N/A') ?>
                                            <?php if (!empty($unit['caretaker_contact'])): ?>
                                                — <?= htmlspecialchars($unit['caretaker_contact']) ?>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-primary w-100" onclick="openInquiryModal(<?= (int)$unit['id'] ?>, '<?= htmlspecialchars(addslashes($unit['property_name'])) ?>', '<?= htmlspecialchars(addslashes($unit['unit_number'])) ?>')">Contact to Apply</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Inquiry Modal -->
    <div class="modal fade" id="inquiryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contact to Apply</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="inquiryForm">
                    <div class="modal-body">
                        <input type="hidden" name="unit_id" id="inquiry_unit_id">
                        <input type="hidden" name="realtor_listing_id" id="inquiry_realtor_listing_id">
                        <input type="hidden" name="realtor_user_id" id="inquiry_realtor_user_id">
                        <div class="mb-2"><strong id="inquiry_unit_label"></strong></div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email (optional)</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preferred Date</label>
                            <input type="date" name="preferred_date" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message (optional)</label>
                            <textarea name="message" class="form-control" rows="3"></textarea>
                        </div>
                        <div id="inquiryAlert" class="alert d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/partials/public_footer.php'; ?>
    <script>
        function openInquiryModal(unitId, propertyName, unitNumber) {
            document.getElementById('inquiry_unit_id').value = unitId;
            document.getElementById('inquiry_realtor_listing_id').value = '';
            document.getElementById('inquiry_realtor_user_id').value = '';
            document.getElementById('inquiry_unit_label').textContent = propertyName + ' - Unit ' + unitNumber;
            const m = new bootstrap.Modal(document.getElementById('inquiryModal'));
            m.show();
        }

        function openInquiryModalForListing(listingId, realtorUserId, title) {
            document.getElementById('inquiry_unit_id').value = '';
            document.getElementById('inquiry_realtor_listing_id').value = listingId;
            document.getElementById('inquiry_realtor_user_id').value = realtorUserId;
            document.getElementById('inquiry_unit_label').textContent = title;
            const m = new bootstrap.Modal(document.getElementById('inquiryModal'));
            m.show();
        }

        document.getElementById('inquiryForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true; const original = btn.textContent; btn.textContent = 'Submitting...';
            const formData = new FormData(form);
            try {
                const res = await fetch('<?= BASE_URL ?>/inquiries/store', { method:'POST', body: formData, headers: { 'X-Requested-With':'XMLHttpRequest' } });
                const data = await res.json();
                const alert = document.getElementById('inquiryAlert');
                alert.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
                alert.textContent = data.message || (data.success ? 'Submitted' : 'Failed');
                alert.classList.remove('d-none');
                if (data.success) {
                    setTimeout(()=>{ bootstrap.Modal.getInstance(document.getElementById('inquiryModal')).hide(); form.reset(); alert.classList.add('d-none'); }, 1000);
                }
            } catch (err) {
                console.error(err);
            } finally {
                btn.disabled = false; btn.textContent = original;
            }
        });

        // Filters
        function applyFilters() {
            const loc = (document.getElementById('filterLocation').value || '').toLowerCase();
            const maxBudget = parseFloat(document.getElementById('filterBudget').value || '');
            const type = (document.getElementById('filterType').value || '').toLowerCase();
            const search = (document.getElementById('filterSearch').value || '').toLowerCase();
            const cards = document.querySelectorAll('.unit-card');
            cards.forEach(card => {
                const cLoc = card.getAttribute('data-location');
                const cType = card.getAttribute('data-type');
                const cRent = parseFloat(card.getAttribute('data-rent'));
                const cName = card.getAttribute('data-name');
                let show = true;
                if (loc && (!cLoc || !cLoc.includes(loc))) show = false;
                if (!isNaN(maxBudget) && cRent > maxBudget) show = false;
                if (type && cType !== type) show = false;
                if (search && (!cName || !cName.includes(search))) show = false;
                card.style.display = show ? '' : 'none';
            });
        }
        ['filterLocation','filterBudget','filterType','filterSearch'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', applyFilters);
            if (el && el.tagName === 'SELECT') el.addEventListener('change', applyFilters);
        });
    </script>
  </body>
 </html>


