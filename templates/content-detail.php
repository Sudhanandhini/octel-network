<?php
/**
 * Shared detail-page renderer for both distributor and solution pages.
 * The including wrapper must set, before requiring this file:
 *   $item                   - the record to render (array)
 *   $allItems               - all published items in this collection (for sidebar + prev/next)
 *   $fileMap                - [slug => filename] for links within this collection
 *   $sidebarTitle           - e.g. "Distribution Partners" or "Our Solutions"
 *   $breadcrumbParentLabel  - e.g. "Distribution" or "Solutions"
 *   $breadcrumbParentHref   - e.g. "distribution.html" or "services.html"
 */
require_once __DIR__ . '/../includes/content.php';

if (empty($item) || !is_array($item)) {
    http_response_code(404);
    exit('Not found.');
}

$paragraphs = parse_paragraphs($item['intro_paragraphs'] ?? '');
$portfolioCards = parse_pipe_rows($item['portfolio_cards'] ?? '', ['icon', 'title', 'desc']);
$portfolioStyle = $item['portfolio_style'] ?? 'default';
$cloudJourneySteps = parse_pipe_rows($item['cloud_journey'] ?? '', ['icon', 'title', 'desc']);
$whatWeDoItems = parse_pipe_rows($item['whatwedo_items'] ?? '', ['title', 'desc']);
// Solution pages get the animated portfolio / "why" layouts; distributor and partner pages keep the classic ones.
$isSolutionPage = ($breadcrumbParentHref ?? '') === 'services.html';
// Distributor pages share the animated "why" panel but keep the classic portfolio cards.
$useAnimatedWhy = $isSolutionPage || ($breadcrumbParentHref ?? '') === 'distribution.html';

$pos = array_search($item['slug'], array_column($allItems, 'slug'));
$prev = $pos !== false && $pos > 0 ? $allItems[$pos - 1] : null;
$next = $pos !== false && $pos < count($allItems) - 1 ? $allItems[$pos + 1] : null;
$prevHref = $prev ? ($fileMap[$prev['slug']] ?? $breadcrumbParentHref) : $breadcrumbParentHref;
$nextHref = $next ? ($fileMap[$next['slug']] ?? $breadcrumbParentHref) : $breadcrumbParentHref;
$prevLabel = $prev ? $prev['nav_name'] : 'All ' . $sidebarTitle;
$nextLabel = $next ? $next['nav_name'] : 'All ' . $sidebarTitle;

// Each detail page (solution, distributor, partner) uses its own image as the banner;
// the shared banner is only a fallback for items without an image.
$defaultBanner = 'assets/img/uploads/block-bmto1e60f0idhx5-1788591945.jpg';
// Distributors can set a separate banner_image (also used on the Distribution and home pages).
$itemBanner = ($item['banner_image'] ?? '') ?: ($item['hero_image'] ?? '');
$useItemBanner = $itemBanner !== '';
$bannerImage = $useItemBanner ? $itemBanner : $defaultBanner;

// Sidebar enquiry form ("Submit").
require_once __DIR__ . '/../includes/contacts.php';
$contactErrors = [];
$contactOld = array_fill_keys(['name', 'email', 'phone', 'message'], '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    $pageUrl = basename(strtok($_SERVER['REQUEST_URI'] ?? '', '?')) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    $result = submit_contact($_POST, $breadcrumbParentLabel . ': ' . $item['nav_name'], $pageUrl);
    if ($result['ok']) {
        header('Location: ' . contact_redirect_url('enquiry-box'));
        exit;
    }
    $contactErrors = $result['errors'];
    $contactOld = $result['old'];
}
?>
<!DOCTYPE html>
<html lang="zxx">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Link of CSS files -->
        <link rel="stylesheet" href="assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
        <link rel="stylesheet" href="assets/css/scrollcue.min.css">
        <link rel="stylesheet" href="assets/css/remixicon.css">
        <link rel="stylesheet" href="assets/css/header.css?v=20261105">
        <link rel="stylesheet" href="assets/css/style.css?v=20261110e">
        <link rel="stylesheet" href="assets/css/footer.css?v=20261110">
        <link rel="stylesheet" href="assets/css/responsive.css">
        <link rel="stylesheet" href="assets/css/dark-theme.css?v=20261028b">

        <title><?= e($item['page_title']) ?> – Octel Networks</title>
        <link rel="icon" type="image/png" href="assets/img/octel-favicon.png">
        <style>
            a.navbar-brand {
                position: relative;
                display: inline-block;
            }
            a.navbar-brand .logo-default,
            a.navbar-brand .logo-hover {
                transition: opacity 0.4s ease, transform 0.4s ease;
            }
            a.navbar-brand .logo-hover {
                position: absolute;
                top: 0;
                left: 0;
                opacity: 0;
                transform: scale(0.9);
            }
            a.navbar-brand:hover .logo-default,
            .navbar-area.sticky a.navbar-brand .logo-default {
                opacity: 0;
                transform: scale(0.9);
            }
            a.navbar-brand:hover .logo-hover,
            .navbar-area.sticky a.navbar-brand .logo-hover {
                opacity: 1;
                transform: scale(1);
            }

            .cloud-service-card {
                position: relative;
                height: 100%;
                background-color: var(--whiteColor);
                border: 1px solid #e2e2e2;
                border-radius: 20px;
                overflow: hidden;
                transition: var(--transition);
                display: flex;
                flex-direction: column;
            }
            .cloud-service-card:hover {
                box-shadow: 0 15px 40px rgba(0, 37, 44, 0.12);
                transform: translateY(-5px);
            }
            .cloud-service-card .csc-media {
                position: relative;
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 26px 26px 0;
            }
            .cloud-service-card .csc-icon {
                font-size: 32px;
                color: rgba(255, 0, 0, 0.85);
                flex-shrink: 0;
            }
            .cloud-service-card .csc-media h3 {
                color: #000;
                font-size: 17px;
                font-weight: 700;
                letter-spacing: .2px;
                margin-bottom: 0;
            }
            .cloud-service-card .csc-body {
                padding: 16px 26px 24px;
            }
            .cloud-service-card .csc-body p {
                color: var(--paraColor);
                margin-bottom: 0;
            }

            /* Product range section, rendered from the admin "Product range" field by render_product_range() */
            .dp-section { margin-bottom: 40px; }
            .dp-section .row { --bs-gutter-x: 20px; }

            /* Group heading: number badge + title + rule */
            .dp-section .dp-group {
                display: flex;
                align-items: center;
                gap: 14px;
                font-size: 20px;
                font-weight: 700;
                color: var(--titleColor);
                margin: 16px 0 22px;
            }
            .dp-section .dp-group::after {
                content: "";
                flex: 1 1 auto;
                /* 2px so the rule doesn't vanish at fractional zoom / display scaling */
                height: 2px;
                border-radius: 2px;
                background: linear-gradient(90deg, rgba(227, 0, 22, .55), rgba(227, 0, 22, .05));
            }
            .dp-section .dp-num {
                flex-shrink: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 34px;
                height: 34px;
                border-radius: 10px;
                background: var(--secondaryColor);
                color: var(--whiteColor);
                font-size: 15px;
                font-weight: 700;
                box-shadow: 0 6px 14px rgba(227, 0, 22, .25);
            }
            .dp-section .dp-card .dp-num {
                width: 26px;
                height: 26px;
                border-radius: 8px;
                font-size: 13px;
                margin-right: 10px;
                box-shadow: none;
            }

            .dp-section .dp-lead {
                font-size: 17px;
                color: var(--titleColor);
                padding-left: 16px;
                border-left: 3px solid var(--secondaryColor);
                margin-bottom: 28px;
            }

            /* Cards: tinted header strip + compact list body */
            .dp-section .dp-card {
                position: relative;
                width: 100%;
                display: flex;
                flex-direction: column;
                background-color: var(--whiteColor);
                border: 1px solid #ececec;
                border-radius: 16px;
                overflow: hidden;
                margin-bottom: 20px;
                transition: box-shadow .3s ease, transform .3s ease, border-color .3s ease;
            }
            .dp-section .dp-card::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 3px;
                background: var(--secondaryColor);
                transform: scaleX(0);
                transform-origin: left;
                transition: transform .4s ease;
            }
            .dp-section .dp-card:hover {
                border-color: transparent;
                box-shadow: 0 15px 40px rgba(0, 37, 44, .10);
                transform: translateY(-4px);
            }
            .dp-section .dp-card:hover::before { transform: scaleX(1); }
            .dp-section .dp-card h4,
            .dp-section .dp-card h5 {
                display: flex;
                align-items: center;
                color: var(--primaryColor);
                font-size: 16px;
                font-weight: 700;
                line-height: 1.35;
                margin: 0;
                padding: 16px 22px;
                background: #fafafa;
                border-bottom: 1px solid #f0f0f0;
            }
            .dp-section .dp-card ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            .dp-section .dp-card li {
                position: relative;
                padding: 9px 0 9px 20px;
                font-size: 15px;
                line-height: 1.55;
                color: var(--paraColor);
                border-bottom: 1px dashed #ececec;
            }
            .dp-section .dp-card li:last-child { border-bottom: 0; }
            .dp-section .dp-card li::before {
                content: "";
                position: absolute;
                left: 2px;
                top: 17px;
                width: 7px;
                height: 7px;
                border-radius: 2px;
                background: var(--secondaryColor);
                transform: rotate(45deg);
            }
            .dp-section .dp-card strong { color: var(--titleColor); font-weight: 600; }

            /* Icon header + paragraph body (Matrix) */
            .dp-section .dp-icon {
                flex-shrink: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                margin-right: 14px;
                border-radius: 12px;
                background: rgba(227, 0, 22, .08);
                color: var(--secondaryColor);
                font-size: 21px;
                transition: background .3s ease, color .3s ease;
            }
            .dp-section .dp-card:hover .dp-icon {
                background: var(--secondaryColor);
                color: var(--whiteColor);
            }
            .dp-section .dp-card .dp-icon + * { font-size: 17px; }
            .dp-section .dp-body { padding: 10px 22px 16px; }
            .dp-section .dp-body > p:first-child { margin-top: 8px; }
            .dp-section .dp-body p {
                font-size: 15px;
                line-height: 1.7;
                color: var(--paraColor);
                margin-bottom: 14px;
            }
            .dp-section .dp-body .dp-families {
                margin-bottom: 0;
                padding: 14px 18px;
                border-radius: 12px;
                background: #fafafa;
                border-left: 3px solid var(--secondaryColor);
            }
            .dp-section .dp-body .dp-families strong {
                color: var(--titleColor);
                font-weight: 700;
            }

            /* Source tags as small pills */
            .dp-section .dp-src {
                display: inline-block;
                margin-left: 6px;
                padding: 1px 9px;
                border-radius: 20px;
                background: rgba(227, 0, 22, .08);
                color: var(--secondaryColor);
                font-size: 11px;
                font-weight: 600;
                line-height: 1.7;
                letter-spacing: .3px;
                vertical-align: middle;
            }

            .dp-section .dp-note {
                background: var(--whiteColor);
                border: 1px solid #ececec;
                border-left: 3px solid var(--secondaryColor);
                border-radius: 12px;
                padding: 16px 20px;
                margin: 4px 0 0;
            }
            .dp-section .dp-note strong { color: var(--titleColor); }

            @media (max-width: 767px) {
                .dp-section .dp-group { font-size: 18px; }
                .dp-section .dp-card h4,
                .dp-section .dp-card h5 { padding: 14px 18px; }
                .dp-section .dp-body { padding: 8px 18px 12px; }
            }

            .theme-dark .dp-section .dp-card,
            .theme-dark .dp-section .dp-note { background-color: #0F0F0F; border-color: rgba(255, 255, 255, .1); }
            .theme-dark .dp-section .dp-note { border-left-color: var(--secondaryColor); }
            .theme-dark .dp-section .dp-card h4,
            .theme-dark .dp-section .dp-card h5 { background: #161616; border-bottom-color: rgba(255, 255, 255, .08); color: var(--whiteColor); }
            .theme-dark .dp-section .dp-card li { border-bottom-color: rgba(255, 255, 255, .08); }
            .theme-dark .dp-section .dp-group,
            .theme-dark .dp-section .dp-lead,
            .theme-dark .dp-section .dp-card strong,
            .theme-dark .dp-section .dp-note strong { color: var(--whiteColor); }

            /* Detail page image slider (admin "Listing card slider images"), shown in place of the hero image */
            .detail-slider {
                position: relative;
                width: 100%;
                aspect-ratio: 12 / 5;
                background: #05080c;
            }
            .detail-slider .swiper-wrapper,
            .detail-slider .swiper-slide {
                height: 100%;
            }
            .detail-slider img {
                display: block;
                width: 100%;
                height: 100%;
                object-fit: contain;
            }
            .detail-slider-nav {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                z-index: 3;
                width: 42px;
                height: 42px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
                color: #fff;
                background: rgba(255, 255, 255, .15);
                border: 1px solid rgba(255, 255, 255, .4);
                backdrop-filter: blur(4px);
                cursor: pointer;
                transition: background .15s ease, border-color .15s ease;
            }
            .detail-slider-nav:hover { background: var(--secondaryColor); border-color: var(--secondaryColor); }
            .detail-slider-prev { left: 18px; }
            .detail-slider-next { right: 18px; }
            .detail-slider-pagination {
                position: absolute;
                left: 0;
                right: 0;
                bottom: 16px;
                z-index: 3;
                display: flex;
                justify-content: center;
                gap: 6px;
            }
            .detail-slider-pagination .swiper-pagination-bullet {
                width: 8px;
                height: 8px;
                margin: 0 !important;
                background: rgba(255, 255, 255, .5);
                opacity: 1;
                transition: width .3s ease, background .3s ease;
            }
            .detail-slider-pagination .swiper-pagination-bullet-active {
                width: 22px;
                border-radius: 4px;
                background: var(--secondaryColor);
            }
            @media (max-width: 767px) {
                .detail-slider-nav { display: none; }
            }

            .custom-blocks-wrap { margin-top: 10px; }
            .custom-block-heading { margin: 30px 0 14px; }
            .custom-block-paragraph { margin-bottom: 16px; }
            .custom-block-image { margin: 20px 0; }
            .custom-block-image img { display: block; }
            .custom-block-image figcaption { color: var(--paraColor); font-size: 14px; margin-top: 8px; }
            .custom-block-btn {
                display: inline-block;
                padding: 12px 28px;
                background: var(--secondaryColor);
                color: #fff;
                border-radius: 50px;
                font-weight: 700;
                text-decoration: none;
                margin: 6px 0 20px;
                transition: var(--transition);
            }
            .custom-block-btn:hover { background: #c40013; color: #fff; }
            .custom-block-table { margin-bottom: 20px; }
            .custom-block-table table { width: 100%; }
            .custom-block-table th, .custom-block-table td { padding: 10px 14px; border: 1px solid #e5e7eb; }
            .custom-block-row { margin: 10px 0 20px; }

            .cloud-portfolio-heading {
                display: flex;
                align-items: center;
                gap: 22px;
                margin: 10px 0 30px;
            }
            .cloud-portfolio-heading .cph-line {
                flex: 1;
                height: 1px;
                background: #dcdcdc;
            }
            .cloud-portfolio-heading h5 {
                margin: 0;
                text-align: center;
                white-space: nowrap;
                letter-spacing: 1px;
                font-weight: 700;
            }
            .cloud-card {
                position: relative;
                height: 100%;
                background-color: var(--whiteColor);
                border: 1px solid #e2e2e2;
                border-radius: 14px;
                overflow: hidden;
                transition: var(--transition);
                display: flex;
                flex-direction: column;
            }
            .cloud-card:hover {
                box-shadow: 0 15px 40px rgba(0, 37, 44, 0.12);
                transform: translateY(-5px);
            }
            .cloud-card-media {
                position: relative;
                height: 150px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                background: linear-gradient(135deg, #0c2233 0%, #123249 60%, #0a1a28 100%);
            }
            .cloud-card-icon {
                font-size: 60px;
                color: rgba(255, 255, 255, 0.25);
            }
            .cloud-card-num {
                position: absolute;
                top: 14px;
                left: 14px;
                background: var(--secondaryColor);
                color: #fff;
                font-weight: 700;
                font-size: 13px;
                line-height: 1;
                padding: 6px 11px;
                border-radius: 4px;
                letter-spacing: .5px;
            }
            .cloud-card-body {
                padding: 22px 24px 26px;
            }
            .cloud-card-title {
                color: var(--secondaryColor);
                font-size: 16px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .3px;
                margin-bottom: 10px;
            }
            .cloud-card-body p {
                color: var(--paraColor);
                font-size: 14.5px;
                margin-bottom: 0;
            }

            .cloud-journey-section {
                margin: 10px 0 30px;
                padding: 34px 24px;
                background: #f7f7f7;
                border-radius: 14px;
            }
            .cloud-journey-heading {
                display: flex;
                align-items: center;
                gap: 22px;
                margin: 0 0 34px;
            }
            .cloud-journey-heading .cph-line {
                flex: 1;
                height: 1px;
                background: #dcdcdc;
            }
            .cloud-journey-heading h5 {
                margin: 0;
                text-align: center;
                white-space: nowrap;
                letter-spacing: 1px;
                font-weight: 700;
                position: relative;
                padding-bottom: 10px;
            }
            .cloud-journey-heading h5::after {
                content: "";
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 36px;
                height: 3px;
                background: var(--secondaryColor);
                border-radius: 2px;
            }
            .cloud-journey-track {
                position: relative;
                display: grid;
                grid-template-columns: repeat(9, 1fr);
                gap: 18px 8px;
            }
            .cloud-journey-track::before {
                content: "";
                position: absolute;
                top: 34px;
                left: calc(100% / 18);
                right: calc(100% / 18);
                border-top: 2px dashed rgba(255, 0, 0, 0.35);
                z-index: 0;
            }
            .cloud-journey-step {
                position: relative;
                z-index: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .cloud-journey-icon {
                width: 68px;
                height: 68px;
                border-radius: 50%;
                background: var(--whiteColor);
                border: 1px solid #e6e6e6;
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 26px;
                color: var(--secondaryColor);
                margin-bottom: 14px;
                flex-shrink: 0;
            }
            .cloud-journey-step h6 {
                color: var(--titleColor, #000);
                font-size: 14.5px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .3px;
                margin-bottom: 6px;
            }
            .cloud-journey-step p {
                color: var(--paraColor);
                font-size: 12.5px;
                line-height: 1.5;
                margin-bottom: 0;
            }
            @media (max-width: 1199px) {
                .cloud-journey-track { grid-template-columns: repeat(5, 1fr); }
                .cloud-journey-track::before { display: none; }
            }
            @media (max-width: 767px) {
                .cloud-journey-track { grid-template-columns: repeat(3, 1fr); }
            }
            @media (max-width: 479px) {
                .cloud-journey-track { grid-template-columns: repeat(2, 1fr); }
                .cloud-journey-heading h5 { white-space: normal; }
            }

            .intro-heading {
                margin-bottom: 14px;
                font-weight: 700;
            }
            .solution-summary-box {
                margin-top: 22px;
                padding: 20px 24px;
                background: #f7f7f7;
                border-left: 3px solid var(--secondaryColor);
            }
            .solution-summary-box p {
                color: var(--paraColor);
                font-size: 15px;
            }

            /* Solution pages: animated "Our Solutions" cards and "Why" panel */
            .sx-portfolio { margin: 10px 0 30px; }
            .sx-head { margin-bottom: 30px; }
            .sx-label {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 7px 15px;
                margin-bottom: 14px;
                border-radius: 50px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: 1.5px;
                text-transform: uppercase;
                color: var(--secondaryColor);
                background: rgba(227, 0, 22, .08);
            }
            .sx-dot {
                position: relative;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--secondaryColor);
            }
            .sx-dot::after {
                content: "";
                position: absolute;
                inset: 0;
                border-radius: 50%;
                background: var(--secondaryColor);
                animation: sxPing 1.8s ease-out infinite;
            }
            .sx-title {
                position: relative;
                padding-bottom: 16px;
                margin-bottom: 14px;
                font-size: 28px;
                font-weight: 700;
                line-height: 1.3;
            }
            .sx-title::after {
                content: "";
                position: absolute;
                left: 0;
                bottom: 0;
                width: 70px;
                height: 3px;
                border-radius: 3px;
                background: linear-gradient(90deg, var(--secondaryColor), #ff7a85, var(--secondaryColor));
                background-size: 200% 100%;
                animation: sxShimmer 3s linear infinite;
            }
            .sx-sub {
                margin-bottom: 0;
                font-size: 17px;
                color: var(--paraColor);
            }
            .sx-card {
                position: relative;
                overflow: hidden;
                padding: 28px 26px 30px;
                border-radius: 20px;
                background: var(--whiteColor);
                border: 1px solid #e8e8e8;
                transition: transform .4s ease, box-shadow .4s ease, border-color .4s ease;
            }
            .sx-card::before {
                content: "";
                position: absolute;
                top: -80px;
                right: -80px;
                width: 180px;
                height: 180px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(227, 0, 22, .12), transparent 70%);
                transition: transform .6s ease;
            }
            .sx-card:hover {
                transform: translateY(-8px);
                border-color: rgba(227, 0, 22, .35);
                box-shadow: 0 22px 45px rgba(0, 37, 44, .12);
            }
            .sx-card:hover::before { transform: scale(2.4); }
            .sx-num {
                position: absolute;
                top: 18px;
                right: 22px;
                font-size: 46px;
                font-weight: 800;
                line-height: 1;
                color: transparent;
                -webkit-text-stroke: 1.5px rgba(0, 37, 44, .15);
                transition: color .4s ease, -webkit-text-stroke-color .4s ease;
            }
            .sx-card:hover .sx-num {
                color: rgba(227, 0, 22, .12);
                -webkit-text-stroke-color: var(--secondaryColor);
            }
            .sx-icon {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 60px;
                height: 60px;
                margin-bottom: 20px;
                overflow: hidden;
                border-radius: 16px;
                font-size: 28px;
                color: var(--whiteColor);
                background: linear-gradient(135deg, var(--secondaryColor), #ff4d5e);
                box-shadow: 0 10px 22px rgba(227, 0, 22, .28);
                transition: transform .4s ease;
            }
            .sx-icon::after {
                content: "";
                position: absolute;
                top: 0;
                left: -75%;
                width: 50%;
                height: 100%;
                background: linear-gradient(120deg, transparent, rgba(255, 255, 255, .55), transparent);
                transform: skewX(-20deg);
                animation: sxShine 4s ease-in-out infinite;
            }
            .sx-card:hover .sx-icon { transform: rotate(-8deg) scale(1.08); }
            .sx-card h4 {
                position: relative;
                margin-bottom: 10px;
                font-size: 19px;
                font-weight: 700;
                color: var(--titleColor);
            }
            .sx-card p {
                position: relative;
                margin-bottom: 0;
                font-size: 15.5px;
                color: var(--paraColor);
            }
            .sx-bar {
                position: absolute;
                left: 0;
                bottom: 0;
                width: 100%;
                height: 3px;
                background: linear-gradient(90deg, var(--secondaryColor), var(--primaryColor));
                transform: scaleX(0);
                transform-origin: left;
                transition: transform .5s ease;
            }
            .sx-card:hover .sx-bar { transform: scaleX(1); }

            .sx-why {
                position: relative;
                overflow: hidden;
                margin: 20px 0 30px;
                padding: 40px 34px 24px;
                border-radius: 22px;
                background:
                    radial-gradient(rgba(255, 255, 255, .06) 1px, transparent 1px) 0 0 / 20px 20px,
                    #737474;
            }
            .sx-why::before {
                content: "";
                position: absolute;
                top: -120px;
                right: -120px;
                width: 320px;
                height: 320px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(227, 0, 22, .35), transparent 70%);
                animation: sxDrift 10s ease-in-out infinite alternate;
            }
            .sx-why > * { position: relative; }
            .sx-why-head { margin-bottom: 28px; }
            .sx-label-light {
                color: var(--whiteColor);
                background: rgba(255, 255, 255, .1);
                border: 1px solid rgba(255, 255, 255, .15);
            }
            .sx-label-light i { font-size: 15px; color: #ff6b78; }
            .sx-why .sx-title { color: var(--whiteColor); }
            .sx-why .sx-sub { color: rgba(255, 255, 255, .7); }
            .sx-step {
                display: flex;
                align-items: flex-start;
                gap: 16px;
                padding: 20px;
                border-radius: 16px;
                background: rgba(255, 255, 255, .04);
                border: 1px solid rgba(255, 255, 255, .1);
                transition: transform .35s ease, background .35s ease, border-color .35s ease;
            }
            .sx-step:hover {
                transform: translateY(-5px);
                background: rgba(227, 0, 22, .12);
                border-color: rgba(227, 0, 22, .5);
            }
            .sx-step-num {
                position: relative;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 46px;
                height: 46px;
                border-radius: 50%;
                font-size: 15px;
                font-weight: 700;
                color: var(--whiteColor);
                background: var(--secondaryColor);
            }
            .sx-step-num::after {
                content: "";
                position: absolute;
                inset: -6px;
                border-radius: 50%;
                border: 1.5px dashed rgba(255, 255, 255, .35);
                animation: sxSpin 12s linear infinite;
            }
            .sx-step:hover .sx-step-num::after {
                border-color: var(--secondaryColor);
                animation-duration: 3s;
            }
            .sx-step h5 {
                margin-bottom: 6px;
                font-size: 17px;
                font-weight: 700;
                color: var(--whiteColor);
            }
            .sx-step p {
                margin-bottom: 0;
                font-size: 15px;
                line-height: 1.6;
                color: rgba(255, 255, 255, .7);
            }
            .sx-summary { margin-top: 10px; }
            .sx-built,
            .sx-outcome {
                padding: 22px 24px;
                border-radius: 16px;
            }
            .sx-built {
                background: rgba(255, 255, 255, .06);
                border: 1px solid rgba(255, 255, 255, .12);
            }
            .sx-built h6,
            .sx-outcome h6 {
                margin-bottom: 14px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: 1.5px;
                text-transform: uppercase;
                color: rgba(255, 255, 255, .7);
            }
            .sx-chips {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .sx-chips span {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 7px 14px;
                border-radius: 50px;
                font-size: 14px;
                font-weight: 600;
                color: var(--primaryColor);
                background: var(--whiteColor);
                animation: sxFloat 3.5s ease-in-out infinite;
                animation-delay: calc(var(--i) * .4s);
            }
            .sx-chips span i { color: var(--secondaryColor); }
            .sx-outcome {
                position: relative;
                overflow: hidden;
                background: linear-gradient(135deg, var(--secondaryColor), #b30012);
            }
            .sx-outcome h6 { color: rgba(255, 255, 255, .85); }
            .sx-outcome .sx-quote {
                position: absolute;
                top: 6px;
                right: 16px;
                font-size: 64px;
                line-height: 1;
                color: rgba(255, 255, 255, .18);
            }
            .sx-outcome p {
                position: relative;
                margin-bottom: 0;
                font-size: 17px;
                font-style: italic;
                font-weight: 500;
                line-height: 1.6;
                color: var(--whiteColor);
            }
            .theme-dark .sx-card {
                background: #171717;
                border-color: rgba(255, 255, 255, .1);
            }
            .theme-dark .sx-card h4 { color: var(--whiteColor); }
            .theme-dark .sx-num { -webkit-text-stroke-color: rgba(255, 255, 255, .2); }
            .theme-dark .sx-why { border: 1px solid rgba(255, 255, 255, .08); }
            @keyframes sxPing {
                0% { transform: scale(1); opacity: .8; }
                100% { transform: scale(3); opacity: 0; }
            }
            @keyframes sxShimmer {
                to { background-position: -200% 0; }
            }
            @keyframes sxShine {
                0%, 60% { left: -75%; }
                100% { left: 130%; }
            }
            @keyframes sxSpin {
                to { transform: rotate(360deg); }
            }
            @keyframes sxDrift {
                to { transform: translate(-60px, 60px) scale(1.2); }
            }
            @keyframes sxFloat {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-4px); }
            }
            @media (max-width: 767px) {
                .sx-title { font-size: 23px; }
                .sx-why { padding: 30px 18px 12px; }
                .sx-card { padding: 24px 20px 26px; }
                .sx-num { font-size: 38px; }
            }
            @media (prefers-reduced-motion: reduce) {
                .sx-portfolio *,
                .sx-portfolio *::before,
                .sx-portfolio *::after,
                .sx-why,
                .sx-why *,
                .sx-why::before,
                .sx-why *::after {
                    animation: none !important;
                }
            }
        </style>
    </head>
    <body>

       <!--  Preloader Start -->
        <div class="preloader-area" id="preloader">
            <div class="preloader-inner">
                <img src="assets/img/new/logo-white.png" alt="Octel Networks" class="preloader-logo" width="220" height="68">
                <div class="preloader-bar"><span></span></div>
            </div>
        </div>
        <!--  Preloader End -->

        <!-- Custom Cursor -->
        <div class="cursor">
            <span class="cursor-text d-flex flex-column align-items-center justify-content-center rounded-circle bg-white text-title fs-14 fw-semibold"></span>
        </div>
        <div class="cursor-inner"></div>

        <div id="smooth-wrapper">
            <div id="smooth-content">

                <div class="bg-albastor position-relative z-1">

                    <!-- Navbar Area Start -->
                    <div class="navbar-area style-one position-relative z-2" id="navbar">
                        <div class="container style-two">
                            <div class="navbar-wrapper d-flex justify-content-between align-items-center">
                                <a href="index.html" class="navbar-brand">
                                    <img src="assets/img/logo-white.png" alt="Logo" class="logo-default">
                                    <img src="assets/img/new/logo-white.png" alt="Logo" class="logo-hover">
                                </a>
                                <div class="menu-area mx-auto">
                                    <div class="overlay"></div>
                                    <nav class="menu">
                                        <div class="menu-mobile-header">
                                            <button type="button" class="menu-mobile-arrow bg-transparent border-0"><i class="ri-arrow-left-s-line"></i></button>
                                            <div class="menu-mobile-title"></div>
                                            <button type="button" class="menu-mobile-close bg-transparent border-0"><i class="ri-close-line"></i></button>
                                        </div>
                                        <ul class="menu-section p-0 mb-0 lh-1">
                                            <li class="menu-item-has-children">
                                                <a href="services.html">SOLUTIONS</a>
                                            </li>
                                            <li class="menu-item-has-children">
                                                <a href="distribution.html">DISTRIBUTION</a>
                                            </li>
                                            <li class="menu-item-has-children">
                                                <a href="partners.html">OUR PARTNERS</a>
                                            </li>
                                            <li class="menu-item-has-children">
                                                <a href="javascript:void(0)">WHY OCTEL?<i class="ri-arrow-down-s-line"></i></a>
                                                <ul class="menu-subs menu-column-1">
                                                    <li><a href="why-octel.html">Why Octel</a></li>
                                                    <li><a href="about.html">About Us</a></li>
                                                    <li><a href="our-leaders.html">Our Leaders</a></li>
                                                    <li><a href="clients.html">Our Clients</a></li>
                                                    <li><a href="case-studies.html">Case Studies</a></li>
                                                    <li><a href="careers.html">Careers</a></li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                <div class="other-options d-flex flex-wrap align-items-center justify-content-end">
                                    <div class="option-item position-relative d-flex align-items-center">
                                        <div class="mobile-options position-relative d-lg-none me-3">
                                            <button class="dropdown-toggle  text-center bg-transparent border-0 p-0 transition" type="button" data-bs-toggle="dropdown" aria-expanded="true">
                                                <i class="ri-more-fill"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-centered mobile-option-list top-1 border-0" data-bs-popper="static">
                                                <a href="contact.html" class="btn style-one d-inline-flex align-items-center">
                                                    <span class="btn-icon-one d-flex flex-column align-items-center justify-content-center rounded-circle transition"><img src="assets/img/new/right-arrow.svg" alt="Icon"></span>
                                                    <span class="btn-text fw-bold d-flex flex-column align-items-center justify-content-center transition">Contact Us</span>
                                                    <span class="btn-icon-two d-flex flex-column align-items-center justify-content-center rounded-circle transition"><img src="assets/img/new/right-arrow.svg" alt="Icon"></span>
                                                </a>
                                            </div>
                                        </div>
                                        <button class="search-btn bg_gradient border-0 rounded-circle d-flex flex-column align-items-center justify-content-center dropdown-toggle transition" type="button" data-bs-toggle="dropdown" aria-expanded="true">
                                            <i class="ri-search-line"></i>
                                            <i class="ri-close-line"></i>
                                        </button>
                                        <div class="search-dropdown dropdown-menu dropdown-menu-right top-1 border-0" data-bs-popper="static">
                                            <form class="search-popup position-relative" action="search.html" method="get" role="search">
                                                <input type="search" name="q" class="form-control text-para" placeholder="Search Here" aria-label="Search the site" required>
                                                <button type="submit" class="position-absolute top-0 end-0 h-100 border-0 bg-transparent d-flex flex-column align-items-center justify-content-center"><i class="ri-search-2-line"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="option-item d-lg-block d-none">
                                        <a href="contact.html" class="btn style-one d-inline-flex align-items-center">
                                            <span class="btn-icon-one d-flex flex-column align-items-center justify-content-center rounded-circle transition"><img src="assets/img/new/right-arrow.svg" alt="Icon"></span>
                                            <span class="btn-text fw-bold d-flex flex-column align-items-center justify-content-center transition">Contact Us</span>
                                            <span class="btn-icon-two d-flex flex-column align-items-center justify-content-center rounded-circle transition"><img src="assets/img/new/right-arrow.svg" alt="Icon"></span>
                                        </a>
                                    </div>
                                    <div class="option-item d-lg-none">
                                        <button type="button" class="menu-mobile-trigger">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Navbar Area End -->

                    <!-- Breadcrumb Area Start -->
                    <div class="breadcrumb-area style-one position-relative z-1">
                        <div class="br-bg img-container position-absolute top-0 start-0 w-100">
                            <img src="<?= e($bannerImage) ?>" alt="<?= e($item['nav_name']) ?>" class="w-100 h-100 object-fit-cover">
                            <?php if ($useItemBanner): ?>
                            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(90deg, rgba(2,10,20,.88) 0%, rgba(2,10,20,.7) 50%, rgba(2,10,20,.45) 100%);"></div>
                            <?php endif; ?>
                        </div>
                        <div class="container style-one">
                            <div class="row">
                                <div class="col-xxl-10 col-lg-8">
                                    <h2 class="section-title style-three text-white fw-bold mb-20 reveal-text"><?= e($item['page_title']) ?></h2>
                                    <ul class="br-menu list-unstyled mb-0">
                                        <li class="fs-xxl-18"><a href="index.html">Home</a></li>
                                        <li class="fs-xxl-18"><a href="<?= e($breadcrumbParentHref) ?>"><?= e($breadcrumbParentLabel) ?></a></li>
                                        <li class="fs-xxl-18"><?= e($item['nav_name']) ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Breadcrumb Area End -->

                    <!-- Service Details Section Start -->
                    <div class="bg-albastor">
                        <div class="container style-two ptb-120">
                            <div class="row">
                                <div class="col-xxl-8 col-xl-8 pe-xxl-1">
                                    <div class="case-desc mb-30">
                                        <div class="single-para">
                                            <h1><?= e($item['page_title']) ?></h1>
                                            <?php if (empty($item['published'])): ?>
                                                <p><em>Details for this page are coming soon. Please check back shortly, or contact us for more information.</em></p>
                                            <?php else: ?>
                                                <?php if (!empty($item['intro_heading'])): ?>
                                                <h4 class="intro-heading"><?= e($item['intro_heading']) ?></h4>
                                                <?php endif; ?>
                                                <?php foreach ($paragraphs as $para): ?>
                                                    <p><?= e($para) ?></p>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($item['card_slides'])): ?>
                                        <div class="detail-slider swiper overflow-hidden round-20 mb-35">
                                            <div class="swiper-wrapper">
                                                <?php foreach ($item['card_slides'] as $slide): ?>
                                                <div class="swiper-slide">
                                                    <img src="<?= e($slide) ?>" alt="<?= e($item['nav_name']) ?>">
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="detail-slider-nav detail-slider-prev"><i class="ri-arrow-left-s-line"></i></div>
                                            <div class="detail-slider-nav detail-slider-next"><i class="ri-arrow-right-s-line"></i></div>
                                            <div class="detail-slider-pagination"></div>
                                        </div>
                                        <?php elseif (!empty($item['hero_image'])): ?>
                                        <div class="single-img img-container overflow-hidden round-20 mb-35" data-cue="anim-top" data-duration="800">
                                            <img src="<?= e($item['hero_image']) ?>" alt="<?= e($item['nav_name']) ?>" class="w-100 h-auto object-fit-cover round-20">
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($item['published']) && !empty($portfolioCards) && $portfolioStyle === 'numbered'): ?>
                                        <div class="cloud-portfolio-heading">
                                            <span class="cph-line"></span>
                                            <h5><?= e($item['portfolio_heading']) ?></h5>
                                            <span class="cph-line"></span>
                                        </div>
                                        <div class="row mb-15">
                                            <?php foreach ($portfolioCards as $idx => $card): ?>
                                            <div class="col-lg-4 col-md-6 mb-25">
                                                <div class="cloud-card">
                                                    <div class="cloud-card-media">
                                                        <span class="cloud-card-num"><?= e(str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                                                        <i class="<?= e($card['icon']) ?> cloud-card-icon"></i>
                                                    </div>
                                                    <div class="cloud-card-body">
                                                        <h4 class="cloud-card-title"><?= e($card['title']) ?></h4>
                                                        <p><?= e($card['desc']) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php elseif (!empty($item['published']) && !empty($portfolioCards) && $isSolutionPage): ?>
                                        <div class="sx-portfolio">
                                            <div class="sx-head" data-cue="slideInUp">
                                                <span class="sx-label"><span class="sx-dot"></span>What We Offer</span>
                                                <h3 class="sx-title"><?= e($item['portfolio_heading']) ?></h3>
                                                <?php if (!empty($item['portfolio_subtitle'])): ?>
                                                <p class="sx-sub"><?= e($item['portfolio_subtitle']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="row">
                                                <?php $cardCount = count($portfolioCards); ?>
                                                <?php foreach ($portfolioCards as $idx => $card): ?>
                                                <div class="<?= ($cardCount % 2 === 1 && $idx === $cardCount - 1) ? 'col-12' : 'col-md-6' ?> mb-25 d-flex">
                                                    <div class="sx-card w-100" data-cue="slideInUp" data-delay="<?= ($idx % 2) * 150 ?>">
                                                        <span class="sx-num"><?= e(str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                                                        <span class="sx-icon"><i class="<?= e($card['icon'] ?: 'ri-checkbox-circle-line') ?>"></i></span>
                                                        <h4><?= e($card['title']) ?></h4>
                                                        <p><?= e($card['desc']) ?></p>
                                                        <span class="sx-bar"></span>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php elseif (!empty($item['published']) && !empty($portfolioCards)): ?>
                                        <div class="single-para">
                                            <h5><?= e($item['portfolio_heading']) ?></h5>
                                            <p><?= e($item['portfolio_subtitle']) ?></p>
                                        </div>
                                        <?php if (empty($item['hide_portfolio_cards']) || empty($item['product_range'])): ?>
                                        <div class="row mb-15">
                                            <?php foreach ($portfolioCards as $card): ?>
                                            <div class="col-md-6 col-lg-6 mb-25">
                                                <div class="cloud-service-card">
                                                    <div class="csc-media">
                                                        <i class="<?= e($card['icon']) ?> csc-icon"></i>
                                                        <h3><?= e($card['title']) ?></h3>
                                                    </div>
                                                    <div class="csc-body">
                                                        <p><?= e($card['desc']) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if (!empty($item['published']) && !empty($item['product_range'])): ?>
                                        <?php render_product_range($item['product_range']); ?>
                                        <?php endif; ?>

                                        <?php if (!empty($item['published']) && !empty($cloudJourneySteps)): ?>
                                        <div class="cloud-journey-section">
                                            <div class="cloud-journey-heading">
                                                <span class="cph-line"></span>
                                                <h5><?= e($item['cloud_journey_heading'] ?? 'Our Cloud Journey') ?></h5>
                                                <span class="cph-line"></span>
                                            </div>
                                            <div class="cloud-journey-track">
                                                <?php foreach ($cloudJourneySteps as $step): ?>
                                                <div class="cloud-journey-step">
                                                    <div class="cloud-journey-icon">
                                                        <i class="<?= e($step['icon']) ?>"></i>
                                                    </div>
                                                    <h6><?= e($step['title']) ?></h6>
                                                    <p><?= e($step['desc']) ?></p>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($item['published']) && !empty($whatWeDoItems) && $useAnimatedWhy): ?>
                                        <div class="sx-why">
                                            <div class="sx-why-head" data-cue="slideInUp">
                                                <span class="sx-label sx-label-light"><i class="ri-shield-star-line"></i>Why Octel</span>
                                                <h3 class="sx-title"><?= e(($item['whatwedo_heading'] ?? '') ?: 'What Octel Networks Does') ?></h3>
                                                <?php if (!empty($item['whatwedo_intro'])): ?>
                                                <p class="sx-sub"><?= e(rtrim($item['whatwedo_intro'], " :")) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="row">
                                                <?php $whyCount = count($whatWeDoItems); ?>
                                                <?php foreach ($whatWeDoItems as $idx => $wItem): ?>
                                                <div class="<?= ($whyCount % 2 === 1 && $idx === $whyCount - 1) ? 'col-12' : 'col-md-6' ?> mb-20 d-flex">
                                                    <div class="sx-step w-100" data-cue="slideInUp" data-delay="<?= $idx * 100 ?>">
                                                        <span class="sx-step-num"><?= e(str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                                                        <div>
                                                            <h5><?= e($wItem['title']) ?></h5>
                                                            <?php if (!empty($wItem['desc'])): ?>
                                                            <p><?= e($wItem['desc']) ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php if (!empty($item['built_for']) || !empty($item['outcome'])): ?>
                                            <div class="row sx-summary">
                                                <?php if (!empty($item['built_for'])): ?>
                                                <div class="<?= !empty($item['outcome']) ? 'col-md-5' : 'col-12' ?> mb-20 d-flex">
                                                    <div class="sx-built w-100" data-cue="slideInUp">
                                                        <h6>Built for</h6>
                                                        <div class="sx-chips">
                                                            <?php foreach (array_filter(array_map('trim', explode('|', $item['built_for']))) as $cIdx => $chip): ?>
                                                            <span style="--i:<?= (int) $cIdx ?>"><i class="ri-check-line"></i><?= e($chip) ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($item['outcome'])): ?>
                                                <div class="<?= !empty($item['built_for']) ? 'col-md-7' : 'col-12' ?> mb-20 d-flex">
                                                    <div class="sx-outcome w-100" data-cue="slideInUp" data-delay="150">
                                                        <i class="ri-double-quotes-l sx-quote"></i>
                                                        <h6>Outcome</h6>
                                                        <p><?= e($item['outcome']) ?></p>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php elseif (!empty($item['published']) && !empty($whatWeDoItems)): ?>
                                        <div class="single-para">
                                            <h5><?= e(($item['whatwedo_heading'] ?? '') ?: 'What Octel Networks Does') ?></h5>
                                            <?php if (!empty($item['whatwedo_intro'])): ?>
                                            <p><?= e($item['whatwedo_intro']) ?></p>
                                            <?php endif; ?>
                                            <ul class="features-list style-three list-unstyled">
                                                <?php foreach ($whatWeDoItems as $wItem): ?>
                                                <li class="position-relative"><b class="text-title fw-semibold"><?= e($wItem['title']) ?>:</b><?= e($wItem['desc']) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php if (!empty($item['built_for']) || !empty($item['outcome'])): ?>
                                            <div class="solution-summary-box round-16">
                                                <?php if (!empty($item['built_for'])): ?>
                                                <p class="mb-2"><b class="text-title fw-semibold">Built for:</b> <?= e($item['built_for']) ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($item['outcome'])): ?>
                                                <p class="mb-0"><b class="text-title fw-semibold">Outcome:</b> <i><?= e($item['outcome']) ?></i></p>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($item['published']) && !empty($item['content_blocks'])): ?>
                                        <div class="custom-blocks-wrap">
                                            <?php render_content_blocks($item['content_blocks']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="post-pagination d-flex flex-wrap justify-content-between">
                                        <a href="<?= e($prevHref) ?>" class="prev-post fs-16 fw-bold text-title hover-text-primary transition"><img src="assets/img/icons/right-arrow-black.svg" alt="Icon" class="me-2 transition" style="transform: scaleX(-1);"><?= e($prevLabel) ?></a>
                                        <a href="<?= e($nextHref) ?>" class="next-post fs-16 fw-bold text-title hover-text-primary transition"><?= e($nextLabel) ?> <img src="assets/img/icons/right-arrow-black.svg" alt="Icon" class="ms-2 transition"></a>
                                    </div>
                                </div>
                                <div class="col-xxl-3 offset-xxl-1 col-xl-3 ps-xxl-3">
                                    <aside class="sidebar mt-lg-50">
                                        <div class="sidebar-widget round-20">
                                            <h3 class="sidebar-widget-title fs-24 fw-semibold text-title mb-20"><?= e($sidebarTitle) ?></h3>
                                            <ul class="service-list list-unstyled mb-0">
                                                <?php foreach ($allItems as $i): ?>
                                                <li class="fs-xxl-18"><a href="<?= e($fileMap[$i['slug']] ?? $breadcrumbParentHref) ?>"><?= e($i['nav_name']) ?><img src="assets/img/icons/right-arrow-black.svg" alt="Icon"></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <div class="sidebar-widget round-20">
                                            <h3 class="sidebar-widget-title fs-24 fw-semibold text-title mb-25">Working Hours</h3>
                                            <ul class="service-list list-unstyled mb-0">
                                                <li class="fs-xxl-18 d-flex flex-wrap align-items-center justify-content-between">
                                                    <span class="fw-semibold text-title">Monday - Thursday:</span>
                                                    <span class="text-end">09am - 08pm</span>
                                                </li>
                                                <li class="fs-xxl-18 d-flex flex-wrap align-items-center justify-content-between">
                                                    <span class="fw-semibold text-title">Friday - Saturday:</span>
                                                    <span class="text-end">09am - 08pm</span>
                                                </li>
                                                 <li class="fs-xxl-18 d-flex flex-wrap align-items-center justify-content-between">
                                                    <span class="fw-semibold text-title">Sunday:</span>
                                                    <span class="text-end">Closed</span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="sidebar-widget tags-widget round-20">
                                            <h3 class="sidebar-widget-title fs-24 fw-semibold text-title mb-20" id="enquiry-box"><?= e($item['cta_heading']) ?></h3>
                                            <p><?= e($item['cta_text']) ?></p>
                                            <?php if (isset($_GET['sent'])): ?>
                                            <div class="alert alert-success fs-xxl-18 mb-0" role="alert">Thank you! Your request has been received. Our team will get in touch with you shortly.</div>
                                            <?php else: ?>
                                            <?php if ($contactErrors): ?>
                                            <div class="alert alert-danger mb-30" role="alert">
                                                <?php foreach ($contactErrors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                            <form method="post" action="#enquiry-box" class="contact-form style-two">
                                                <input type="hidden" name="contact_form" value="1">
                                                <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
                                                <div class="form-group mb-45">
                                                    <input type="text" name="name" value="<?= e($contactOld['name']) ?>" maxlength="120" class="w-100 bg-transparent fs-xxl-18 text-title outline-0" placeholder="Full Name*" required>
                                                </div>
                                                <div class="form-group mb-45">
                                                    <input type="email" name="email" value="<?= e($contactOld['email']) ?>" class="w-100 bg-transparent fs-xxl-18 text-title outline-0" placeholder="Email*" required>
                                                </div>
                                                <div class="form-group mb-45">
                                                    <input type="tel" name="phone" value="<?= e($contactOld['phone']) ?>" maxlength="20" pattern="[0-9+\s\-]{7,20}" title="Enter a valid phone number" class="w-100 bg-transparent fs-xxl-18 text-title outline-0" placeholder="Phone Number*" required>
                                                </div>
                                                <div class="form-group mb-22">
                                                    <textarea name="message" maxlength="5000" class="w-100 bg-transparent fs-xxl-18 text-title outline-0 resize-0" placeholder="Comment"><?= e($contactOld['message']) ?></textarea>
                                                </div>
                                                <button type="submit" class="btn style-one d-flex align-items-center">
                                                    <span class="btn-icon-one d-flex flex-column align-items-center justify-content-center rounded-circle transition"><img src="assets/img/icons/right-arrow.svg" alt="Icon" class="d-block mx-auto"></span>
                                                    <span class="btn-text fw-bold d-flex flex-column align-items-center justify-content-center transition">Submit</span>
                                                    <span class="btn-icon-two d-flex flex-column align-items-center justify-content-center rounded-circle transition"><img src="assets/img/icons/right-arrow.svg" alt="Icon" class="d-block mx-auto"></span>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </aside>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Service Details Section End -->


                </div>

                <!-- Footer Section Start -->
                <footer class="footer-area style-one">
                    <div class="container style-one">
                        <div class="footer-top">
                            <div class="row g-4 g-xl-5">
                                <div class="col-xl-3 col-md-6">
                                    <div class="footer-widget" data-cue="slideInLeft">
                                        <a href="index.html" class="logo d-inline-block mb-20">
                                            <img src="assets/img/new/logo-white.png" alt="Octel Networks">
                                        </a>
                                        <p class="footer-about mb-25">Building the IT, network and security infrastructure businesses rely on, since 1999.</p>
                                        <ul class="social-profile style-one list-unstyled mb-0">
                                            <li><a href="https://www.facebook.com/p/Octel-Networks-100057436510684/" target="_blank" class="d-flex flex-column align-items-center justify-content-center rounded-circle"><i class="ri-facebook-fill"></i></a></li>
                                            <li><a href="https://x.com/?lang=en" target="_blank" class="d-flex flex-column align-items-center justify-content-center rounded-circle"><i class="ri-twitter-x-line"></i></a></li>
                                            <li><a href="https://www.linkedin.com/company/octel-networks-pvt-ltd/" target="_blank" class="d-flex flex-column align-items-center justify-content-center rounded-circle"><i class="ri-linkedin-fill"></i></a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="footer-widget" data-cue="slideInLeft" data-delay="100">
                                        <h3 class="footer-widget-title">Our Solutions</h3>
                                        <ul class="footer-menu list-unstyled mb-0">
                                            <li><a href="cyber-security.html">Cybersecurity</a></li>
                                            <li><a href="cloud-solutions-support.html">Cloud Services &amp; Migration</a></li>
                                            <li><a href="it-network-infrastructure.html">Network Infrastructure</a></li>
                                            <li><a href="ip-video-surveillance.html">IP Video Surveillance</a></li>
                                            <li><a href="data-center-infrastructure.html">Data Center Infrastructure</a></li>
                                            <li><a href="services.html" class="footer-menu-more">View All Solutions <i class="ri-arrow-right-line"></i></a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xl-2 col-md-6">
                                    <div class="footer-widget" data-cue="slideInLeft" data-delay="200">
                                        <h3 class="footer-widget-title">Distribution</h3>
                                        <ul class="footer-menu list-unstyled mb-0">
                                            <li><a href="matrix-details.html">Matrix</a></li>
                                            <li><a href="rm-details.html">R&amp;M</a></li>
                                            <li><a href="rittal-details.html">Rittal</a></li>
                                            <li><a href="netka-details.html">NETKA System</a></li>
                                            <li><a href="distribution.html" class="footer-menu-more">View All <i class="ri-arrow-right-line"></i></a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xl-4 col-md-6">
                                    <div class="footer-widget" data-cue="slideInLeft" data-delay="300">
                                        <h3 class="footer-widget-title">Contact Us</h3>
                                        <div class="footer-contact d-flex">
                                            <span class="footer-contact-icon d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"><i class="ri-shopping-bag-3-line"></i></span>
                                            <div>
                                                <span class="footer-contact-label d-block">Sales</span>
                                                <a href="tel:+918041155349" class="contact-num d-block">080 41155349</a>
                                                <a href="mailto:info@octelnetworks.com" class="contact-mail d-inline-block">info@octelnetworks.com</a>
                                            </div>
                                        </div>
                                        <div class="footer-contact d-flex">
                                            <span class="footer-contact-icon d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"><i class="ri-customer-service-2-line"></i></span>
                                            <div>
                                                <span class="footer-contact-label d-block">Service</span>
                                                <a href="tel:+918049574038" class="contact-num d-block">080 49574038</a>
                                                <a href="mailto:helpdesk@octelnetworks.com" class="contact-mail d-inline-block">helpdesk@octelnetworks.com</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="footer-bottom">
                            <div class="row align-items-center g-2">
                                <div class="col-md-7">
                                    <p class="copyright-text text-md-start text-center text-black mb-0"><i class="ri-copyright-line"></i><span class="text_secondary ms-1">2026 Octel Networks.</span> All Rights Reserved.</p>
                                </div>
                                <div class="col-md-5">
                                    <p class="copyright-text text-md-end text-center text-black mb-0">Developed by <a href="https://www.sunsys.in" target="_blank" rel="noopener" class="text_secondary">Manithas Technologies Pvt. Ltd.</a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </footer>
                <!-- Footer End -->

            </div>
        </div>
        <!-- Back to Top -->
        <div id="progress-wrap" class="progress-wrap style-one">
            <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
              <path id="progress-path" d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
            </svg>
        </div>

        <!-- Link of JS files -->
        <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/megamenu.js"></script>
        <script src="assets/js/swiper-bundle.min.js"></script>
        <script src="assets/js/fslightbox.js"></script>
        <script src="assets/js/gsap.min.js"></script>
        <script src="assets/js/scrollTrigger.min.js"></script>
        <script src="assets/js/lenis.min.js"></script>
        <script src="assets/js/scrollToPlugin.js"></script>
        <script src="assets/js/SplitText.min.js"></script>
        <script src="assets/js/customEase.js"></script>
        <script src="assets/js/scrollcue.min.js"></script>
        <script src="assets/js/main.js?v=20261028b"></script>
        <script src="assets/js/chatbot.js"></script>
        <script src="assets/js/cookie-consent.js?v=20261028b"></script>
        <?php if (!empty($item['card_slides'])): ?>
        <script>
            new Swiper('.detail-slider', {
                loop: true,
                speed: 800,
                autoplay: {
                    delay: 3500,
                    disableOnInteraction: false
                },
                navigation: {
                    nextEl: '.detail-slider-next',
                    prevEl: '.detail-slider-prev'
                },
                pagination: {
                    el: '.detail-slider-pagination',
                    clickable: true
                }
            });
        </script>
        <?php endif; ?>
    </body>
</html>
