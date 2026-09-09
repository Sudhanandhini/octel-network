// Shared header/footer/breadcrumb renderers used by scripts/build.js
// Single source of truth for the chrome that appears on every page.

function navLink(active) {
  return active ? ' class="active"' : '';
}

function renderTopBar() {
  return `
  <div class="top-bar">
    <div class="container style-two d-flex flex-wrap align-items-center justify-content-between">
      <ul class="contact-info">
        <li><i class="ri-phone-fill"></i> <a href="tel:18083609282">+1 (808) 360-9282</a></li>
        <li><i class="ri-mail-fill"></i> <a href="mailto:hello@exrox.com">hello@exrox.com</a></li>
        <li class="d-none d-md-flex"><i class="ri-map-pin-fill"></i> Chicago, IL, USA</li>
      </ul>
      <ul class="top-social">
        <li><a href="https://www.facebook.com/" target="_blank" rel="noopener"><i class="ri-facebook-fill"></i></a></li>
        <li><a href="https://x.com/" target="_blank" rel="noopener"><i class="ri-twitter-x-line"></i></a></li>
        <li><a href="https://www.linkedin.com/" target="_blank" rel="noopener"><i class="ri-linkedin-fill"></i></a></li>
        <li><a href="https://www.instagram.com/" target="_blank" rel="noopener"><i class="ri-instagram-line"></i></a></li>
      </ul>
    </div>
  </div>`;
}

/**
 * @param {Object} o
 * @param {'transparent'|'inner'} o.variant
 * @param {string} o.active one of: home, about, services, industries, case, team, pricing, faq, blog, contact
 */
function renderHeader(o) {
  const a = o.active;
  const navClass = o.variant === 'inner' ? 'inner-page' : 'style-one';
  return `
  <header class="navbar-area ${navClass}" id="navbar">
    ${o.variant === 'inner' ? renderTopBar() : ''}
    <div class="container style-two">
      <div class="navbar-wrapper d-flex justify-content-between align-items-center">
        <a href="index.html" class="navbar-brand">
          <img src="assets/img/new/logo-white.png" alt="Exrox logo" />
        </a>

        <div class="menu-area mx-auto">
          <nav class="menu">
            <div class="menu-mobile-header">
              <button type="button" class="menu-mobile-arrow bg-transparent border-0"><i class="ri-arrow-left-s-line"></i></button>
              <div class="menu-mobile-title">Menu</div>
              <button type="button" class="menu-mobile-close bg-transparent border-0"><i class="ri-close-line"></i></button>
            </div>
            <ul class="menu-section">
              <li class="menu-item-has-children">
                <a href="javascript:void(0)"${navLink(a === 'home')}>Home <i class="ri-arrow-down-s-line"></i></a>
                <ul class="menu-subs">
                  <li><a href="index.html"${navLink(a === 'home')}>Home — Accounting &amp; Consulting</a></li>
                </ul>
              </li>
              <li><a href="about.html"${navLink(a === 'about')}>About</a></li>
              <li class="menu-item-has-children">
                <a href="javascript:void(0)"${navLink(a === 'services')}>Services <i class="ri-arrow-down-s-line"></i></a>
                <ul class="menu-subs">
                  <li><a href="services.html">Our Services</a></li>
                  <li><a href="service-details.html">Service Details</a></li>
                </ul>
              </li>
              <li><a href="industries.html"${navLink(a === 'industries')}>Industries</a></li>
              <li class="menu-item-has-children">
                <a href="javascript:void(0)"${navLink(a === 'case')}>Case Studies <i class="ri-arrow-down-s-line"></i></a>
                <ul class="menu-subs">
                  <li><a href="case-studies.html">Case Studies</a></li>
                  <li><a href="case-study-details.html">Case Study Details</a></li>
                </ul>
              </li>
              <li><a href="team.html"${navLink(a === 'team')}>Team</a></li>
              <li><a href="pricing.html"${navLink(a === 'pricing')}>Pricing</a></li>
              <li><a href="faq.html"${navLink(a === 'faq')}>FAQ</a></li>
              <li class="menu-item-has-children">
                <a href="javascript:void(0)"${navLink(a === 'blog')}>Blog <i class="ri-arrow-down-s-line"></i></a>
                <ul class="menu-subs">
                  <li><a href="blog.html">Blog</a></li>
                  <li><a href="blog-details.html">Blog Details</a></li>
                </ul>
              </li>
              <li><a href="contact.html"${navLink(a === 'contact')}>Contact</a></li>
            </ul>
          </nav>
        </div>

        <div class="other-options d-flex align-items-center">
          <div class="option-item position-relative">
            <button class="search-btn border-0" type="button" aria-label="Toggle search">
              <i class="ri-search-line"></i><i class="ri-close-line"></i>
            </button>
            <div class="search-dropdown">
              <form class="search-popup position-relative" action="#" onsubmit="return false;">
                <input type="search" class="form-control" placeholder="Search here" />
                <button type="submit" aria-label="Search"><i class="ri-search-2-line"></i></button>
              </form>
            </div>
          </div>
          <div class="option-item d-none d-lg-block">
            <a href="contact.html" class="btn style-one">
              <span class="btn-icon-one"><i class="ri-arrow-right-up-line"></i></span>
              <span class="btn-text">Get A Free Consultation</span>
              <span class="btn-icon-two"><i class="ri-arrow-right-up-line"></i></span>
            </a>
          </div>
          <div class="option-item d-lg-none">
            <button type="button" class="menu-mobile-trigger" aria-label="Toggle menu">
              <span></span><span></span><span></span>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="mobile-menu-overlay"></div>
  </header>`;
}

function renderBreadcrumb(o) {
  return `
  <section class="breadcrumb-area">
    <div class="br-bg"><img src="assets/img/breadcrumb/${o.img || 'br-1.svg'}" alt="" /></div>
    <div class="container style-one">
      <div class="row">
        <div class="col-xxl-10 col-lg-8">
          <h2 class="section-title style-three fw-bold mb-20">${o.title}</h2>
          <ul class="br-menu">
            <li><a href="index.html">Home</a></li>
            <li>${o.title}</li>
          </ul>
        </div>
      </div>
    </div>
  </section>`;
}

function renderFooter() {
  return `
  <footer class="footer-area">
    <div class="container style-one">
      <div class="footer-top">
        <div class="row mb-5 g-4">
          <div class="col-xxl-5 col-lg-4 col-md-5">
            <div class="footer-widget">
              <a href="index.html" class="logo d-block mb-4"><img src="assets/img/new/logo-white.png" alt="Exrox logo" /></a>
              <a href="tel:18083609282" class="contact-num">+1 (808) 360-9282</a>
              <a href="mailto:hello@exrox.com" class="contact-mail">hello@exrox.com</a>
            </div>
          </div>
          <div class="col-xxl-4 col-lg-4 col-md-7">
            <div class="footer-widget">
              <h3 class="footer-widget-title">Quick Links</h3>
              <ul class="footer-menu">
                <li><a href="index.html">Home</a></li>
                <li><a href="about.html">About Us</a></li>
                <li><a href="services.html">Services</a></li>
                <li><a href="case-studies.html">Case Studies</a></li>
                <li><a href="blog.html">Blog</a></li>
                <li><a href="contact.html">Contact</a></li>
                <li><a href="faq.html">FAQs</a></li>
              </ul>
            </div>
          </div>
          <div class="col-xxl-3 col-lg-4 col-md-6">
            <div class="footer-widget">
              <h3 class="footer-widget-title">Stay Connected</h3>
              <p>Join our newsletter and stay updated on the latest news</p>
              <form class="newsletter-form position-relative" onsubmit="return false;">
                <input type="email" placeholder="Type your email" required />
                <button type="submit" aria-label="Subscribe"><i class="ri-send-plane-fill"></i></button>
              </form>
            </div>
          </div>
        </div>

        <div class="row align-items-end footer-bottom-row">
          <div class="col-md-7">
            <a href="index.html" class="logo-text">EXROX</a>
          </div>
          <div class="col-xl-4 offset-xxl-1 col-md-5">
            <div class="footer-widget">
              <p>Exrox is a trusted accounting and management consulting firm helping businesses grow with clarity and control.</p>
              <ul class="social-profile style-one">
                <li><a href="https://www.facebook.com/" target="_blank" rel="noopener"><i class="ri-facebook-fill"></i></a></li>
                <li><a href="https://x.com/" target="_blank" rel="noopener"><i class="ri-twitter-x-line"></i></a></li>
                <li><a href="https://www.linkedin.com/" target="_blank" rel="noopener"><i class="ri-linkedin-fill"></i></a></li>
                <li><a href="https://www.instagram.com/" target="_blank" rel="noopener"><i class="ri-instagram-line"></i></a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <div class="row align-items-center">
          <div class="col-md-7">
            <p class="copyright-text"><i class="ri-copyright-line"></i> <span class="text_secondary">Exrox</span> — a design system reproduction, built with Bootstrap 5, Swiper &amp; GSAP.</p>
          </div>
          <div class="col-md-5">
            <ul class="footer-bottom-menu">
              <li><a href="terms.html">Terms &amp; Conditions</a></li>
              <li><a href="privacy.html">Privacy Policy</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <div id="progress-wrap" class="progress-wrap">
    <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
      <path id="progress-path" d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
    </svg>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/scrollcue@2.0.0/scrollCue.min.js"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>`;
}

module.exports = { renderHeader, renderFooter, renderBreadcrumb };
