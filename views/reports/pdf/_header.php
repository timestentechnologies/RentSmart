<?php
// Define BASE_URL if not already defined
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $base_dir = str_replace('\\', '/', dirname($script_name));
    $base_dir = $base_dir !== '/' ? $base_dir : '';
    define('BASE_URL', $protocol . '://' . $host . $base_dir);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.7;
            color: #2c3e50;
            padding: 0;
            margin: 0;
            background: #ffffff;
        }
        
        .content-wrapper {
            padding: 20px 30px;
            padding-bottom: 100px; /* Space for footer */
        }
        
        .pdf-header {
            margin-bottom: 40px;
            padding: 30px 40px;
            background: #ffffff;
            border-bottom: 4px solid #521062;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logo {
            max-width: 150px;
            max-height: 70px;
            height: auto;
        }
        
        .company-info {
            text-align: left;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: 700;
            color: #521062;
            margin: 0 0 5px 0;
            letter-spacing: 0.5px;
        }
        
        .company-tagline {
            font-size: 12px;
            color: #ff8c00;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin: 0;
        }
        
        .header-right {
            text-align: right;
        }
        
        .report-title {
            font-size: 24px;
            color: #521062;
            margin: 0 0 10px 0;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .report-meta {
            font-size: 12px;
            color: #666;
            margin: 4px 0;
            line-height: 1.6;
        }
        
        .report-meta strong {
            color: #521062;
            font-weight: 600;
        }
        
        .summary-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-left: 4px solid #ff8c00;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 35px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        
        .summary-box h2 {
            margin-top: 0 !important;
            border-bottom: none !important;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 30px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border-radius: 10px;
            overflow: hidden;
        }
        
        th, td {
            padding: 16px 18px;
            border-bottom: 1px solid #e9ecef;
            text-align: left;
            font-size: 14px;
        }
        
        td {
            color: #495057;
        }
        
        th {
            background: linear-gradient(135deg, #521062 0%, #6b1a7a 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tbody tr:hover {
            background-color: #fff5e6;
            transition: background-color 0.2s ease;
        }
        
        h2 {
            color: #521062;
            font-size: 22px;
            margin-top: 40px;
            margin-bottom: 25px;
            border-left: 4px solid #ff8c00;
            padding-left: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .pdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: linear-gradient(135deg, #521062 0%, #6b1a7a 100%);
            color: white;
            padding: 20px 40px;
            font-size: 11px;
            box-shadow: 0 -3px 15px rgba(82, 16, 98, 0.3);
            z-index: 1000;
        }
        
        .footer-content {
            display: table;
            width: 100%;
        }
        
        .footer-left {
            display: table-cell;
            text-align: left;
            width: 50%;
            vertical-align: middle;
        }
        
        .footer-right {
            display: table-cell;
            text-align: right;
            width: 50%;
            vertical-align: middle;
        }
        
        .footer-company {
            font-weight: bold;
            color: #ffffff;
            font-size: 13px;
        }
        
        @page {
            margin: 0;
        }
        
        @media print {
            .content-wrapper {
                page-break-inside: avoid;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        
        @media print {
            body {
                background: white;
            }
        }
    </style>
</head>
<body>
    <div class="pdf-header">
        <div class="header-left">
            <?php 
            $siteLogo = $data['settings']['site_logo'] ?? '';
            $siteName = $data['settings']['site_name'] ?? 'RentSmart';
            if (!empty($siteLogo)): 
                $logoPath = BASE_URL . '/public/assets/images/' . $siteLogo;
            ?>
                <img src="<?= $logoPath ?>" alt="<?= htmlspecialchars($siteName) ?> Logo" class="logo">
            <?php endif; ?>
            <div class="company-info">
                <div class="company-name"><?= htmlspecialchars($siteName) ?></div>
                <div class="company-tagline">Property Management System</div>
            </div>
        </div>
        
        <div class="header-right">
            <div class="report-title"><?= htmlspecialchars($title ?? 'Report') ?></div>
            <?php if (isset($startDate) && isset($endDate)): ?>
            <div class="report-meta">
                <strong>Period:</strong> <?= date('M j, Y', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?>
            </div>
            <?php endif; ?>
            <div class="report-meta">
                <strong>Generated:</strong> <?= date('M j, Y g:i A') ?>
            </div>
            <?php if (isset($data['user_info'])): ?>
            <div class="report-meta">
                <strong>User:</strong> <?= htmlspecialchars($data['user_info']['name']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="content-wrapper">
