<?php
$nav = file_get_contents('nav_block.txt');
preg_match_all('/<div class="mx-2 mb-0\.5">.*?<\/div>\s*<\/div>/s', $nav, $matches);
$blocks = $matches[0];

$header_main = '                <p class="sb-section">{{ $rtl ? \'العمليات الأساسية\' : \'Basic Operations\' }}</p>' . "\n";
$order = [
    0, // Clients
    6, // Vendors
    8, // Items
    9, // Labels
    10, // Sourcing
    2, // Price lists
    1, // Receivables
    7, // Payables
    11, // Quotations
    12, // Cost Centers
    5, // Sales Orders
    3, // Sales Invoices
    4, // Purchase Invoices
];

$header_finance = '                <p class="sb-section">{{ $rtl ? \'الإدارة المالية\' : \'Financial Management\' }}</p>' . "\n";
$finance_order = [13, 14];

$header_logs = '                <p class="sb-section">{{ $rtl ? \'السجلات\' : \'Logs & Records\' }}</p>' . "\n";
$logs_order = [15, 16];

$header_groups = '                <p class="sb-section">{{ app()->getLocale() === \'ar\' ? \'المجموعات والدليل\' : \'Groups & Directory\' }}</p>' . "\n";
$groups_order = [17];

$header_system = '                <p class="sb-section">{{ __(\'messages.nav.system\') }}</p>' . "\n";
$system_order = [18, 19];

$new_nav_content = "";
$new_nav_content .= $header_main;
foreach ($order as $i) $new_nav_content .= "                " . trim($blocks[$i]) . "\n\n";

$new_nav_content .= $header_finance;
foreach ($finance_order as $i) $new_nav_content .= "                " . trim($blocks[$i]) . "\n\n";

$new_nav_content .= $header_logs;
foreach ($logs_order as $i) $new_nav_content .= "                " . trim($blocks[$i]) . "\n\n";

$new_nav_content .= $header_groups;
foreach ($groups_order as $i) $new_nav_content .= "                " . trim($blocks[$i]) . "\n\n";

$new_nav_content .= $header_system;
foreach ($system_order as $i) $new_nav_content .= "                " . trim($blocks[$i]) . "\n\n";

// Replace everything between <nav id="sidebar-nav"> and </nav> except the dashboard link
$full_file = file_get_contents('resources/views/layouts/app.blade.php');
$start_pos = strpos($full_file, '<nav id="sidebar-nav"');
$end_pos = strpos($full_file, '</nav>', $start_pos);

// Extract the dashboard link part (from nav start to the first sb-section)
$nav_start_content = substr($full_file, $start_pos, strpos($full_file, '<p class="sb-section">', $start_pos) - $start_pos);

$final_nav = $nav_start_content . $new_nav_content . "            </nav>";

$full_file = substr_replace($full_file, $final_nav, $start_pos, $end_pos - $start_pos + 6);

file_put_contents('resources/views/layouts/app.blade.php', $full_file);
echo "Done";
