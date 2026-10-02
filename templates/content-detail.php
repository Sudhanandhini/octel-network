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

$pos = array_search($item['slug'], array_column($allItems, 'slug'));
$prev = $pos !== false && $pos > 0 ? $allItems[$pos - 1] : null;
$next = $pos !== false && $pos < count($allItems) - 1 ? $allItems[$pos + 1] : null;
$prevHref = $prev ? ($fileMap[$prev['slug']] ?? $breadcrumbParentHref) : $breadcrumbParentHref;
$nextHref = $next ? ($fileMap[$next['slug']] ?? $breadcrumbParentHref) : $breadcrumbParentHref;
$prevLabel = $prev ? $prev['nav_name'] : 'All ' . $sidebarTitle;
$nextLabel = $next ? $next['nav_name'] : 'All ' . $sidebarTitle;
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
        <link rel="stylesheet" href="assets/css/header.css">
        <link rel="stylesheet" href="assets/css/style.css">
        <link rel="stylesheet" href="assets/css/footer.css">
        <link rel="stylesheet" href="assets/css/responsive.css">
        <link rel="stylesheet" href="assets/css/dark-theme.css">

        <title><?= e($item['page_title']) ?> – Octel Networks</title>
        <link rel="icon" type="image/png" href="assets/img/new/favicon.png">
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
        </style>
    </head>
    <body>

       <!--  Preloader Start -->
        <div class="preloader-area" id="preloader">
            <div class="spinner">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
        <!--  Preloader End -->

        <!-- Theme Switcher Start -->
        <div class="switch-theme-mode">
            <label id="switch" class="switch">
                <input type="checkbox" onchange="toggleTheme()" id="slider">
                <span class="slider round"></span>
            </label>
        </div>
        <!-- Theme Switcher End -->

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
                                                    <li><a href="case-studies.html">Case Studies</a></li>
                                                    <li><a href="careers.html">Careers</a></li>
                                                    <li><a href="contact.html">Contact Us</a></li>
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
                                                <a href="appointment.html" class="btn style-one d-inline-flex align-items-center">
                                                    <span class="btn-icon-one d-flex flex-column align-items-center justify-content-center rounded-circle transition"><img src="assets/img/new/right-arrow.svg" alt="Icon"></span>
                                                    <span class="btn-text fw-bold d-flex flex-column align-items-center justify-content-center transition">Get A Free Consultation</span>
                                                    <span class="btn-icon-two d-flex flex-column align-items-center justify-content-center rounded-circle transition"><img src="assets/img/new/right-arrow.svg" alt="Icon"></span>
                                                </a>
                                            </div>
                                        </div>
                                        <button class="search-btn bg_gradient border-0 rounded-circle d-flex flex-column align-items-center justify-content-center dropdown-toggle transition" type="button" data-bs-toggle="dropdown" aria-expanded="true">
                                            <i class="ri-search-line"></i>
                                            <i class="ri-close-line"></i>
                                        </button>
                                        <div class="search-dropdown dropdown-menu dropdown-menu-right top-1 border-0" data-bs-popper="static">
                                            <form class="search-popup position-relative" action="#">
                                                <input type="search" class="form-control text-para" placeholder="Search Here">
                                                <button type="submit" class="position-absolute top-0 end-0 h-100 border-0 bg-transparent d-flex flex-column align-items-center justify-content-center"><i class="ri-search-2-line"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="option-item d-lg-block d-none">
                                        <a href="appointment.html" class="btn style-one d-inline-flex align-items-center">
                                            <span class="btn-icon-one d-flex flex-column align-items-center justify-content-center rounded-circle transition"><img src="assets/img/new/right-arrow.svg" alt="Icon"></span>
                                            <span class="btn-text fw-bold d-flex flex-column align-items-center justify-content-center transition">Get A Free Consultation</span>
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
                            <img src="assets/img/uploads/block-bmto1e60f0idhx5-1788591945.jpg" alt="Image" class="w-100 h-100 object-fit-cover">
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
                                        <?php if (!empty($item['hero_image'])): ?>
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
                                        <?php elseif (!empty($item['published']) && !empty($portfolioCards)): ?>
                                        <div class="single-para">
                                            <h5><?= e($item['portfolio_heading']) ?></h5>
                                            <p><?= e($item['portfolio_subtitle']) ?></p>
                                        </div>
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

                                        <?php if (!empty($item['published']) && !empty($whatWeDoItems)): ?>
                                        <div class="single-para">
                                            <h5><?= e($item['whatwedo_heading'] ?? 'What Octel Networks Does') ?></h5>
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
                                            <h3 class="sidebar-widget-title fs-24 fw-semibold text-title mb-20"><?= e($item['cta_heading']) ?></h3>
                                            <p><?= e($item['cta_text']) ?></p>
                                            <form action="#" class="contact-form style-two">
                                                <div class="form-group mb-45">
                                                    <input type="text" class="w-100 bg-transparent fs-xxl-18 text-title outline-0" placeholder="Full Name*" required>
                                                </div>
                                                <div class="form-group mb-45">
                                                    <input type="email" class="w-100 bg-transparent fs-xxl-18 text-title outline-0" placeholder="Email*" required>
                                                </div>
                                                <div class="form-group mb-22">
                                                    <textarea class="w-100 bg-transparent fs-xxl-18 text-title outline-0 resize-0" placeholder="Comment*" required></textarea>
                                                </div>
                                                <button class="btn style-one d-flex align-items-center">
                                                    <span class="btn-icon-one d-flex flex-column align-items-center justify-content-center rounded-circle transition"><img src="assets/img/icons/right-arrow.svg" alt="Icon" class="d-block mx-auto"></span>
                                                    <span class="btn-text fw-bold d-flex flex-column align-items-center justify-content-center transition">Make An Appointment</span>
                                                    <span class="btn-icon-two d-flex flex-column align-items-center justify-content-center rounded-circle transition"><img src="assets/img/icons/right-arrow.svg" alt="Icon" class="d-block mx-auto"></span>
                                                </button>
                                            </form>
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
                        <div class="footer-top pt-115">
                            <div class="row mb-65">
                                <div class="col-xxl-5 col-lg-4 col-md-5">
                                    <div class="footer-widget mb-30" data-cue="slideInLeft">
                                        <a href="index.html" class="logo d-block mb-50">
                                            <img src="assets/img/new/logo-white.png" alt="Image">
                                        </a>
                                        <a href="tel:18083609282" class="contact-num d-block fw-bold text_secondary mb-18">+1 (808) 360-9282</a>
                                        <a href="mailto:info@octelnetworks.com" class="contact-mail fs-xxl-18 fw-bold text-white hover-text-secondary">info@octelnetworks.com</a>
                                    </div>
                                </div>
                                <div class="col-xxl-4 col-lg-4 col-md-7 ps-xxl-4">
                                    <div class="footer-widget mb-30" data-cue="slideInLeft" data-delay="100">
                                        <h3 class="footer-widget-title fs-24 text-black mb-20">Quick Links</h3>
                                        <ul class="footer-menu style-one list-unstyled mb-0">
                                            <li class="d-block fs-xxl-18"><a href="index.html">Home</a></li>
                                            <li class="d-block fs-xxl-18"><a href="about.html">About Us</a></li>
                                            <li class="d-block fs-xxl-18"><a href="services.html">Services</a></li>
                                            <li class="d-block fs-xxl-18"><a href="case-studies.html">Case Studies</a></li>
                                            <li class="d-block fs-xxl-18"><a href="blog-left-sidebar.html">Blog </a></li>
                                            <li class="d-block fs-xxl-18"><a href="contact.html">Contact</a></li>
                                            <li class="d-block fs-xxl-18"><a href="faq.html">FAQs</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xxl-3 col-lg-4 col-md-6">
                                    <div class="footer-widget mb-30" data-cue="slideInLeft" data-delay="200">
                                        <h3 class="footer-widget-title fs-24 text-black mb-20">Stay Connected</h3>
                                        <p class="text-black fs-xxl-18 mb-25">Join our newsletter and stay updated on the latest news</p>
                                        <form action="#" class="newsletter-form style-one position-relative">
                                             <input type="email" class="w-100 fs-xxl-18 text-black border-0 round-5 outline-0" placeholder="Type your email" required>
                                             <button type="submit" class="position-absolute top-0 end-0 h-100 border-0 bg-transparent"><i class="ri-send-plane-fill"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="row ">
                                <div class="col-md-7 pe-xxl-0">
                                    <a href="index.html" class="logo-text text-black reveal-text-right transition" data-cue="slideInUp" data-delay="200">OCTEL <br\>NETWORKS</a>
                                </div>
                                <div class="col-xl-4 offset-xxl-1 col-md-5 ps-xxl-4">
                                    <div class="footer-widget" data-cue="slideInUp" data-delay="200">
                                        <p class="text-black fs-xxl-18 mb-25">Octel Networks is a trusted network infrastructure and IT solutions partner, helping businesses stay connected, secure, and supported.</p>
                                        <ul class="social-profile style-one list-unstyled mb-0">
                                            <li><a href="https://www.facebook.com/" target="_blank" class="d-flex flex-column align-items-center justify-content-center rounded-circle"><i class="ri-facebook-fill"></i></a></li>
                                            <li><a href="https://x.com/?lang=en" target="_blank" class="d-flex flex-column align-items-center justify-content-center rounded-circle"><i class="ri-twitter-x-line"></i></a></li>
                                            <li><a href="https://www.linkedin.com/" target="_blank" class="d-flex flex-column align-items-center justify-content-center rounded-circle"><i class="ri-linkedin-fill"></i></a></li>
                                            <li><a href="https://www.instagram.com/" target="_blank" class="d-flex flex-column align-items-center justify-content-center rounded-circle"><i class="ri-instagram-line"></i></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="footer-bottom">
                            <div class="row align-items-center">
                                <div class="col-md-7 pe-md-0 mb-sm-10">
                                    <p class="copyright-text fs-xxl-10 text-md-start text-center text-black mb-0"><i class="ri-copyright-line"></i><span class="text_secondary ms-1">2026 Octel Networks</span> | Crafted By <a href="https://www.sunsys.in" target="_blank" class=" link-hover-white text-black">Manithas Technologies Pvt. Ltd.</a></p>
                                </div>
                                <div class="col-md-5">
                                    <ul class="footer-bottom-menu list-unstyled text-lg-end text-center mb-0">
                                        <li class="fs-xxl-18"><a href="terms-conditions.html" class="text-black link-hover-primary">Terms And Conditions</a></li>
                                        <li class="fs-xxl-18"><a href="privacy-policy.html" class="text-black link-hover-primary">Privacy Policy</a></li>
                                    </ul>
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
        <script src="assets/js/main.js"></script>
    </body>
</html>
