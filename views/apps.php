<?php
$siteName = $siteName ?? 'RentSmart';
$siteLogo = $siteLogo ?? BASE_URL . '/public/assets/images/logo.svg';
$role = $_SESSION['user_role'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apps - <?= htmlspecialchars($siteName) ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($favicon ?? BASE_URL . '/public/assets/images/site_favicon_1750832003.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --brand-purple: #6B3E99;
            --brand-purple-faded: #b39ddb;
            /* light/faded purple */
            --brand-orange: #fd7e14;
            --bg-bottom: #f3f0f8;
            /* very light lavender-grey */
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: var(--bg-bottom);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            overflow-x: hidden;
            position: relative;
        }

        /* ── Curved top background ───────────────────────────────────────── */
        .hero-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 44vh;
            background: linear-gradient(135deg, rgba(43, 10, 61, 0.03) 0%, rgba(43, 10, 61, 0.06) 55%, rgba(43, 10, 61, 0.09) 100%);
            z-index: 0;
            /* Sine wave curved boundary */
            clip-path: ellipse(120% 65% at 50% 0%);
        }

        /* ── Page content ────────────────────────────────────────────────── */
        .apps-container {
            position: relative;
            z-index: 1;
            max-width: 1040px;
            margin: 0 auto;
            padding: 2.5rem 2rem 4rem;
            animation: fadeIn 0.7s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Header ──────────────────────────────────────────────────────── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-logo img {
            height: 46px;
            width: auto;
        }

        .brand-logo span {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--brand-purple);
            text-shadow: none;
        }

        .header-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            padding: 1.5rem 2rem 0;
            /* Pushes to edges but keeps some breathing room */
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 100%;
            margin: 0;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(107, 62, 153, 0.08);
            padding: 6px 16px;
            border-radius: 50px;
            backdrop-filter: blur(12px);
            border: 1.5px solid rgba(107, 62, 153, 0.15);
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .user-profile:hover {
            background: rgba(107, 62, 153, 0.15);
            border-color: rgba(107, 62, 153, 0.3);
            transform: translateY(-2px);
        }

        .user-profile i {
            font-size: 1.3rem;
            color: var(--brand-purple);
        }

        .user-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--brand-purple);
            line-height: 1;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-radius: 50%;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1.5px solid rgba(220, 53, 69, 0.2);
        }

        .header-logout:hover {
            background: #dc3545;
            color: #fff;
            transform: translateY(-2px);
            border-color: #dc3545;
        }

        /* ── Apps white card panel ───────────────────────────────────────── */
        .apps-panel {
            background: transparent;
            border-radius: 28px;
            padding: 1.5rem 1rem;
            margin-top: 0.5rem;
        }

        /* ── Apps grid ───────────────────────────────────────────────────── */
        .apps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 2rem 1.5rem;
            justify-items: center;
        }

        .app-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 110px;
        }

        .app-icon {
            width: 76px;
            height: 76px;
            background: #fafafa;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            margin-bottom: 0.65rem;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.07);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        .app-name {
            font-size: 0.88rem;
            font-weight: 500;
            color: #5a5870;
            text-align: center;
            white-space: nowrap;
            transition: color 0.2s;
        }

        .app-item:hover {
            transform: translateY(-6px) scale(1.05);
        }

        .app-item:hover .app-icon {
            box-shadow: 0 14px 28px rgba(107, 62, 153, 0.18);
            border-color: rgba(107, 62, 153, 0.12);
        }

        .app-item:hover .app-name {
            color: var(--brand-purple);
            font-weight: 600;
        }

        /* ── Logout button ───────────────────────────────────────────────── */
        .logout-btn {
            display: none;
            /* Removed fixed logout */
        }

        /* ── Mobile ──────────────────────────────────────────────────────── */
        @media (max-width: 576px) {
            .apps-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1.25rem;
            }

            .app-icon {
                width: 60px;
                height: 60px;
                font-size: 1.75rem;
                border-radius: 14px;
            }

            .app-name {
                font-size: 0.8rem;
            }

            .apps-container {
                padding: 1.5rem 1rem 3rem;
            }

            .apps-panel {
                padding: 1.5rem 1rem;
                border-radius: 20px;
            }

            .hero-bg {
                height: 38vh;
                clip-path: ellipse(200% 100% at 50% 0%);
            }
        }

        /* ── Search bar (flexbox pill — no absolute positioning) ─────────── */
        .search-wrap {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: center;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            max-width: 440px;
            background: rgba(255, 255, 255, 0.78);
            border: 1.5px solid rgba(255, 255, 255, 0.5);
            border-radius: 50px;
            padding: 10px 16px;
            backdrop-filter: blur(10px);
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .search-box:focus-within {
            border-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
            background: #fff;
        }

        .search-icon {
            color: var(--brand-purple);
            font-size: 1rem;
            flex-shrink: 0;
            line-height: 1;
        }

        .search-box input {
            flex: 1;
            border: none;
            background: transparent;
            outline: none;
            font-size: 0.95rem;
            color: #4a4565;
            font-family: inherit;
            min-width: 0;
        }

        .search-box input::placeholder {
            color: #9168c6;
        }

        /* Clear button */
        .search-clear {
            flex-shrink: 0;
            color: #bbb;
            cursor: pointer;
            font-size: 1rem;
            display: none;
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
        }

        .search-clear:hover {
            color: var(--brand-purple);
        }

        /* No results message */
        .no-results {
            display: none;
            text-align: center;
            padding: 2rem 0 1rem;
            color: #aaa;
            font-size: 0.95rem;
        }

        .no-results i {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
            color: #ccc;
        }

        /* Hidden app items */
        .app-item.hidden {
            display: none;
        }
    </style>
</head>

<body>

    <div class="hero-bg"></div>

    <!-- Header Wrapper (for full-width header) -->
    <div class="header-wrapper">
        <div class="header">
            <a href="<?= BASE_URL ?>/" class="brand-logo">
                <img src="<?= htmlspecialchars($siteLogo) ?>" alt="Logo">
            </a>

            <div class="header-actions">
                <a href="#" class="user-profile" data-bs-toggle="modal" data-bs-target="#profileModal">
                    <i class="bi bi-person-circle"></i>
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
                </a>

                <a href="<?= BASE_URL ?>/logout" class="header-logout" title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="apps-container">
        <!-- Search bar (outside apps panel) -->
        <div class="search-wrap">
            <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="appSearch" placeholder="Search apps..." autocomplete="off" />
                <button class="search-clear" id="searchClear" title="Clear"><i class="bi bi-x-circle-fill"></i></button>
            </div>
        </div>

        <!-- Apps White Panel -->
        <div class="apps-panel">
            <div class="apps-grid" id="appsGrid">
                <?php foreach ($apps as $index => $app): ?>
                    <?php
                        // Alternate between brand purple and warm orange
                        $brandColors = ['#2b0a3d', '#ff8a1f'];
                        $iconColor = $brandColors[$index % 2];
                    ?>
                    <a href="<?= BASE_URL . $app['url'] ?>" class="app-item"
                        data-name="<?= strtolower(htmlspecialchars($app['name'])) ?>">
                        <div class="app-icon" style="color: <?= $iconColor ?>;">
                            <i class="bi <?= $app['icon'] ?>"></i>
                        </div>
                        <div class="app-name"><?= htmlspecialchars($app['name']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="no-results" id="noResults">
                <i class="bi bi-search"></i>
                No apps found for "<span id="noResultsQuery"></span>"
            </div>
        </div>

    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content"
                style="border-radius: 20px; border: none; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.1);">
                <div class="modal-header" style="background: var(--brand-purple); color: white; border: none;">
                    <h5 class="modal-title" id="profileModalLabel">My Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/settings/updateProfile" autocomplete="off">
                    <div class="modal-body" style="padding: 2rem;">
                        <!-- CSRF placeholder - usually would call csrf_field() if using the framework helper -->
                        <?php if (function_exists('csrf_field'))
                            echo csrf_field(); ?>

                        <div class="mb-3">
                            <label for="profileName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="profileName" name="name"
                                value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required
                                style="border-radius: 10px;">
                        </div>
                        <div class="mb-3">
                            <label for="profileEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="profileEmail" name="email"
                                value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" required
                                style="border-radius: 10px;">
                        </div>
                        <div class="mb-3">
                            <label for="profilePassword" class="form-label">New Password <span
                                    class="text-muted small">(leave blank to keep current)</span></label>
                            <input type="password" class="form-control" id="profilePassword" name="password"
                                autocomplete="new-password" style="border-radius: 10px;">
                        </div>
                    </div>
                    <div class="modal-footer" style="padding: 1.5rem; border-top: 1px solid #f0f0f0;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                            style="border-radius: 10px; padding: 10px 20px;">Cancel</button>
                        <button type="submit" class="btn btn-primary"
                            style="background: var(--brand-purple); border: none; border-radius: 10px; padding: 10px 20px;">Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const searchInput = document.getElementById('appSearch');
        const searchClear = document.getElementById('searchClear');
        const appsGrid = document.getElementById('appsGrid');
        const noResults = document.getElementById('noResults');
        const noResultsQ = document.getElementById('noResultsQuery');
        const appItems = document.querySelectorAll('.app-item');

        function filterApps(query) {
            const q = query.trim().toLowerCase();
            let visibleCount = 0;

            appItems.forEach(item => {
                const name = item.getAttribute('data-name') || '';
                if (!q || name.includes(q)) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });

            // Show/hide no-results message
            if (visibleCount === 0 && q !== '') {
                noResults.style.display = 'block';
                noResultsQ.textContent = query;
            } else {
                noResults.style.display = 'none';
            }

            // Show/hide clear button
            searchClear.style.display = q ? 'block' : 'none';
        }

        searchInput.addEventListener('input', () => filterApps(searchInput.value));

        searchClear.addEventListener('click', () => {
            searchInput.value = '';
            filterApps('');
            searchInput.focus();
        });

        // Keyboard shortcut: press '/' to focus search
        document.addEventListener('keydown', e => {
            if (e.key === '/' && document.activeElement !== searchInput) {
                e.preventDefault();
                searchInput.focus();
            }
            if (e.key === 'Escape') {
                searchInput.value = '';
                filterApps('');
            }
        });
    </script>
</body>

</html>