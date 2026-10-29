/*------------------------------------------------
        GDPR Cookie Consent Popup
--------------------------------------------------
  Shows a consent popup on the first visit and remembers the choice for 180 days
  (localStorage + a first-party cookie the server can read). Nothing optional
  should run until the visitor allows it. To load a tracker only after consent:

      OctelConsent.onAllow('analytics', function () {
          // e.g. inject the Google Analytics script here
      });

  Categories: necessary (always on), analytics, marketing.
  Visitors can change their choice any time via the cookie button (bottom left)
  or any element with the attribute data-cookie-settings.
------------------------------------------------*/
(function () {
    'use strict';

    var STORAGE_KEY = 'octel_cookie_consent';
    var VERSION = 1;          // bump to ask everyone again (e.g. after adding a new tracker)
    var MAX_AGE_DAYS = 180;
    var PRIVACY_URL = 'privacy-policy.html';
    var DISMISS_KEY = 'octel_cookie_banner_closed';
    var listeners = [];

    function readConsent() {
        var raw = null;
        try { raw = localStorage.getItem(STORAGE_KEY); } catch (e) {}
        if (!raw) {
            var match = document.cookie.match(new RegExp('(?:^|; )' + STORAGE_KEY + '=([^;]*)'));
            raw = match ? decodeURIComponent(match[1]) : null;
        }
        if (!raw) return null;
        try {
            var data = JSON.parse(raw);
            var expired = Date.now() - data.time > MAX_AGE_DAYS * 864e5;
            return data.version === VERSION && !expired ? data : null;
        } catch (e) {
            return null;
        }
    }

    function saveConsent(analytics, marketing) {
        var data = {
            version: VERSION,
            time: Date.now(),
            necessary: true,
            analytics: !!analytics,
            marketing: !!marketing
        };
        var raw = JSON.stringify(data);
        try { localStorage.setItem(STORAGE_KEY, raw); } catch (e) {}
        document.cookie = STORAGE_KEY + '=' + encodeURIComponent(raw) +
            '; max-age=' + MAX_AGE_DAYS * 86400 + '; path=/; SameSite=Lax' +
            (location.protocol === 'https:' ? '; Secure' : '');
        notify(data);
        return data;
    }

    function notify(data) {
        listeners.forEach(function (l) {
            if (!l.done && data[l.category]) {
                l.done = true;
                l.fn();
            }
        });
        document.dispatchEvent(new CustomEvent('octel:consent', { detail: data }));
    }

    var css = '' +
        '.oc-cookie{position:fixed;left:24px;bottom:24px;z-index:100000;width:420px;max-width:calc(100% - 32px);' +
        'background:#fff;color:#393737;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.25);padding:28px;' +
        'font-size:15px;line-height:1.6;transform:translateY(20px);opacity:0;transition:transform .4s ease,opacity .4s ease;}' +
        '.oc-cookie.oc-show{transform:none;opacity:1;}' +
        '.oc-cookie .oc-close{position:absolute;top:14px;right:16px;}' +
        '.oc-cookie h4{display:flex;padding-right:24px;align-items:center;gap:10px;font-size:20px;font-weight:600;color:#000;margin:0 0 10px;}' +
        '.oc-cookie h4 i{color:#e30016;font-size:24px;}' +
        '.oc-cookie p{margin:0 0 18px;color:#5b5b5b;}' +
        '.oc-cookie p a{color:#e30016;text-decoration:underline;}' +
        '.oc-btns{display:flex;flex-wrap:wrap;gap:10px;}' +
        '.oc-btn{flex:1 1 auto;border:1px solid #000;border-radius:50px;padding:10px 18px;font-size:14px;font-weight:700;' +
        'cursor:pointer;background:#fff;color:#000;transition:background .3s,color .3s,border-color .3s;}' +
        '.oc-btn:hover{background:#000;color:#fff;}' +
        '.oc-btn.oc-primary{background:#e30016;border-color:#e30016;color:#fff;}' +
        '.oc-btn.oc-primary:hover{background:#000;border-color:#000;}' +
        '.oc-link{background:none;border:0;padding:0;margin-top:14px;font-size:14px;font-weight:600;color:#000;' +
        'text-decoration:underline;cursor:pointer;}' +
        '.oc-overlay{position:fixed;inset:0;z-index:100001;background:rgba(0,0,0,.55);display:flex;align-items:center;' +
        'justify-content:center;padding:16px;opacity:0;transition:opacity .3s;}' +
        '.oc-overlay.oc-show{opacity:1;}' +
        '.oc-modal{background:#fff;color:#393737;border-radius:20px;width:560px;max-width:100%;max-height:calc(100vh - 32px);' +
        'overflow:auto;padding:30px;font-size:15px;line-height:1.6;}' +
        '.oc-modal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}' +
        '.oc-modal-head h4{font-size:22px;font-weight:600;color:#000;margin:0;}' +
        '.oc-close{background:none;border:0;font-size:26px;line-height:1;cursor:pointer;color:#000;}' +
        '.oc-modal > p{color:#5b5b5b;margin:0 0 18px;}' +
        '.oc-modal a{color:#e30016;text-decoration:underline;}' +
        '.oc-modal:focus{outline:none;}' +
        '.oc-cat{border:1px solid #e5e5e5;border-radius:14px;padding:16px 18px;margin-bottom:12px;}' +
        '.oc-cat-head{display:flex;justify-content:space-between;align-items:center;gap:12px;}' +
        '.oc-cat-head strong{color:#000;font-size:16px;}' +
        '.oc-cat p{margin:6px 0 0;font-size:14px;color:#6b6b6b;}' +
        '.oc-always{font-size:13px;font-weight:700;color:#e30016;white-space:nowrap;}' +
        '.oc-switch{position:relative;width:46px;height:26px;flex:none;}' +
        '.oc-switch input{opacity:0;width:0;height:0;position:absolute;}' +
        '.oc-switch span{position:absolute;inset:0;border-radius:26px;background:#c9c9c9;cursor:pointer;transition:background .3s;}' +
        '.oc-switch span:before{content:"";position:absolute;width:20px;height:20px;left:3px;top:3px;border-radius:50%;' +
        'background:#fff;transition:transform .3s;}' +
        '.oc-switch input:checked + span{background:#e30016;}' +
        '.oc-switch input:checked + span:before{transform:translateX(20px);}' +
        '.oc-switch input:focus-visible + span{outline:2px solid #000;outline-offset:2px;}' +
        '.oc-modal .oc-btns{margin-top:18px;}' +
        '.oc-reopen{position:fixed;left:20px;bottom:20px;z-index:9999;width:46px;height:46px;border-radius:50%;border:0;' +
        'background:#000;color:#fff;font-size:22px;display:flex;align-items:center;justify-content:center;cursor:pointer;' +
        'box-shadow:0 6px 20px rgba(0,0,0,.25);transition:background .3s;}' +
        '.oc-reopen:hover{background:#e30016;}' +
        '@media (max-width:575px){.oc-cookie{left:16px;right:16px;bottom:16px;width:auto;max-width:none;padding:22px;}' +
        '.oc-btn{flex:1 1 100%;}.oc-modal{padding:22px;}.oc-reopen{left:16px;bottom:16px;width:42px;height:42px;font-size:20px;}}';

    var banner, overlay, reopenBtn, lastFocus;

    function el(html) {
        var wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        return wrap.firstChild;
    }

    function showBanner() {
        hideReopen();
        if (banner) return;
        banner = el(
            '<div class="oc-cookie" role="dialog" aria-live="polite" aria-labelledby="oc-cookie-title">' +
                '<button type="button" class="oc-close" data-oc="dismiss" aria-label="Close">&times;</button>' +
                '<h4 id="oc-cookie-title"><i class="ri-shield-check-line" aria-hidden="true"></i>We Value Your Privacy</h4>' +
                '<p>We use essential cookies to make this website work. With your consent, we would also like to use ' +
                'analytics and marketing cookies to understand how the site is used and improve our services. ' +
                'You can accept, reject or choose which cookies to allow. Read our <a href="' + PRIVACY_URL + '">Privacy Policy</a>.</p>' +
                '<div class="oc-btns">' +
                    '<button type="button" class="oc-btn" data-oc="reject">Reject All</button>' +
                    '<button type="button" class="oc-btn oc-primary" data-oc="accept">Accept All</button>' +
                '</div>' +
                '<button type="button" class="oc-link" data-oc="customize">Customize Settings</button>' +
            '</div>'
        );
        document.body.appendChild(banner);
        requestAnimationFrame(function () { banner.classList.add('oc-show'); });
    }

    function hideBanner() {
        if (!banner) return;
        var b = banner;
        banner = null;
        b.classList.remove('oc-show');
        setTimeout(function () { b.remove(); }, 400);
    }

    function showModal() {
        if (overlay) return;
        var current = readConsent() || { analytics: false, marketing: false };
        lastFocus = document.activeElement;
        overlay = el(
            '<div class="oc-overlay">' +
                '<div class="oc-modal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="oc-modal-title">' +
                    '<div class="oc-modal-head">' +
                        '<h4 id="oc-modal-title">Cookie Preferences</h4>' +
                        '<button type="button" class="oc-close" data-oc="close" aria-label="Close">&times;</button>' +
                    '</div>' +
                    '<p>Choose which cookies you allow. You can change your choice at any time using the cookie button ' +
                    'at the bottom left of the page. See our <a href="' + PRIVACY_URL + '">Privacy Policy</a> for details.</p>' +
                    '<div class="oc-cat">' +
                        '<div class="oc-cat-head"><strong>Strictly Necessary</strong><span class="oc-always">Always Active</span></div>' +
                        '<p>Required for the website to work, such as remembering your cookie choice and securing forms. These cannot be switched off.</p>' +
                    '</div>' +
                    '<div class="oc-cat">' +
                        '<div class="oc-cat-head"><strong>Analytics</strong>' +
                            '<label class="oc-switch"><input type="checkbox" id="oc-analytics"' + (current.analytics ? ' checked' : '') +
                            ' aria-label="Allow analytics cookies"><span></span></label></div>' +
                        '<p>Help us understand how visitors use the website (for example, pages visited) so we can improve it. Data is aggregated.</p>' +
                    '</div>' +
                    '<div class="oc-cat">' +
                        '<div class="oc-cat-head"><strong>Marketing</strong>' +
                            '<label class="oc-switch"><input type="checkbox" id="oc-marketing"' + (current.marketing ? ' checked' : '') +
                            ' aria-label="Allow marketing cookies"><span></span></label></div>' +
                        '<p>Used to show relevant content and measure campaigns across other websites and social media.</p>' +
                    '</div>' +
                    '<div class="oc-btns">' +
                        '<button type="button" class="oc-btn" data-oc="reject">Reject All</button>' +
                        '<button type="button" class="oc-btn" data-oc="save">Save Preferences</button>' +
                        '<button type="button" class="oc-btn oc-primary" data-oc="accept">Accept All</button>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
        document.body.appendChild(overlay);
        requestAnimationFrame(function () { overlay.classList.add('oc-show'); });
        overlay.querySelector('.oc-modal').focus();
    }

    function hideModal() {
        if (!overlay) return;
        var o = overlay;
        overlay = null;
        o.classList.remove('oc-show');
        setTimeout(function () { o.remove(); }, 300);
        if (lastFocus && lastFocus.focus) lastFocus.focus();
    }

    function showReopen() {
        if (reopenBtn) return;
        reopenBtn = el('<button type="button" class="oc-reopen" data-oc="customize" aria-label="Cookie settings" title="Cookie settings">' +
            '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
            'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
            '<path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/>' +
            '<path d="M8.5 8.5v.01M16 15.5v.01M12 12v.01M11 17v.01M7 14v.01"/></svg></button>');
        document.body.appendChild(reopenBtn);
    }

    function hideReopen() {
        if (!reopenBtn) return;
        reopenBtn.remove();
        reopenBtn = null;
    }

    // Closing the banner without choosing keeps optional cookies off and hides
    // it for the rest of this browser session; it asks again next visit.
    function dismissBanner() {
        try { sessionStorage.setItem(DISMISS_KEY, '1'); } catch (e) {}
        hideBanner();
        showReopen();
    }

    function bannerDismissed() {
        try { return sessionStorage.getItem(DISMISS_KEY) === '1'; } catch (e) { return false; }
    }

    function finish(analytics, marketing) {
        saveConsent(analytics, marketing);
        hideModal();
        hideBanner();
        showReopen();
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-cookie-settings]')) {
            e.preventDefault();
            showModal();
            return;
        }
        if (overlay && e.target === overlay) {
            hideModal();
            return;
        }
        var btn = e.target.closest('[data-oc]');
        if (!btn) return;
        switch (btn.getAttribute('data-oc')) {
            case 'accept': finish(true, true); break;
            case 'reject': finish(false, false); break;
            case 'customize': showModal(); break;
            case 'close': hideModal(); break;
            case 'dismiss': dismissBanner(); break;
            case 'save':
                finish(overlay.querySelector('#oc-analytics').checked, overlay.querySelector('#oc-marketing').checked);
                break;
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay) hideModal();
    });

    window.OctelConsent = {
        get: readConsent,
        allows: function (category) {
            var c = readConsent();
            return !!(c && c[category]);
        },
        onAllow: function (category, fn) {
            var l = { category: category, fn: fn, done: false };
            listeners.push(l);
            var c = readConsent();
            if (c && c[category]) {
                l.done = true;
                fn();
            }
        },
        open: showModal
    };

    function init() {
        var style = document.createElement('style');
        style.textContent = css;
        document.head.appendChild(style);
        if (readConsent() || bannerDismissed()) {
            showReopen();
        } else {
            setTimeout(showBanner, 800);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
