/*-------------------------------
    Octel Networks — FAQ Chatbot
    Self-contained: injects its own styles and markup.
    Answers come only from content already published on the site.
-------------------------------*/
(function () {
    'use strict';

    if (document.getElementById('octel-chatbot')) return;

    var EMAIL = 'info@octelnetworks.com';

    /* ---------- Knowledge base ---------- */

    var SOLUTIONS = [
        { name: 'Network Infrastructure & Integration', url: 'it-network-infrastructure.html',
          keys: ['network infrastructure', 'lan', 'wan', 'sd-wan', 'sdwan', 'wifi', 'wi-fi', 'wireless', 'switching', 'router', 'routing', 'next-gen firewall'],
          bullets: ['LAN, WAN & SD-WAN Architecture', 'Next-Gen Firewall & Network Security', 'Enterprise Wi-Fi Design & Optimization', 'Centralized Monitoring & Redundancy'] },
        { name: 'IT Services & Managed Infrastructure', url: 'managed-it-services.html',
          keys: ['managed', 'it services', 'it consulting', 'consulting', 'project management', 'sla', 'facility management', 'preventive maintenance', 'vendor coordination'],
          bullets: ['IT Consulting & Solution Architecture', 'Project Management & Vendor Coordination', 'Facility Management & Preventive Maintenance', 'SLA-Based Support & Incident Management'] },
        { name: 'Passive Network Infrastructure', url: 'passive-network-cabling.html',
          keys: ['passive', 'cabling', 'cable', 'cables', 'fiber', 'fibre', 'cat6', 'cat6a', 'patch panel', 'patch panels', 'structured cabling'],
          bullets: ['Structured Copper Cabling (Cat6 / Cat6A)', 'Fiber Optic Backbone & Termination', 'Racks, Patch Panels & Cable Management', 'Testing, Certification & Documentation'] },
        { name: 'IP Video Surveillance', url: 'ip-video-surveillance.html',
          keys: ['cctv', 'camera', 'cameras', 'surveillance', 'video', 'anpr', 'ptz', 'thermal', 'face recognition', 'vms'],
          bullets: ['IP Camera Systems (Fixed, Dome, PTZ, Thermal)', 'Recording, Storage & Edge Processing', 'Video Management & AI Analytics', 'Multi-Site Integration with Access Control'] },
        { name: 'IP Telephony & Unified Communications', url: 'ip-telephony.html',
          keys: ['telephony', 'voip', 'pbx', 'ip-pbx', 'ip phone', 'ip phones', 'phone system', 'unified communications', 'sip', 'ivr', 'microsoft teams', 'softphone'],
          bullets: ['IP Phones & Softphone Endpoints', 'IP-PBX & Centralized Call Management', 'Unified Communications (Voice, Messaging, Presence)', 'CRM & Microsoft Teams Integration'] },
        { name: 'Mall Infrastructure Management', url: 'mall-infrastructure-management.html',
          keys: ['mall', 'malls', 'shopping', 'tenant', 'tenants', 'guest wifi', 'guest wi-fi', 'retail'],
          bullets: ['Internet & Network Redundancy', 'Mall-Wide & Guest Wi-Fi', 'Tenant Connectivity & Segmentation', 'IP Telephony & Multi-Location Connectivity'] },
        { name: 'Network Management & Monitoring', url: 'network-management-software.html',
          keys: ['monitoring', 'network management', 'nms', 'topology', 'discovery', 'siem', 'soar', 'capacity planning', 'dashboards'],
          bullets: ['Discovery & Topology Mapping', 'Real-Time & Performance Monitoring', 'Fault, Alerts & Traffic Visibility', 'Reporting & Capacity Planning'] },
        { name: 'Cybersecurity', url: 'cyber-security.html',
          keys: ['cyber', 'cybersecurity', 'cyber security', 'security', 'xdr', 'zero trust', 'ztna', 'phishing', 'ransomware', 'dlp', 'identity', 'security testing', 'vapt'],
          bullets: ['Layered Defense Across Endpoint, Network & Cloud', 'EDR/XDR & Zero Trust Access', 'Email, Data & Identity Protection', 'Security Assessment & Testing'] },
        { name: 'Security: Endpoint to UTM & Network Security', url: 'endpoint-network-security.html',
          keys: ['endpoint', 'utm', 'edr', 'antivirus', 'anti-virus', 'air-gap', 'air-gapped', 'air gap', 'soc', 'noc', 'mdr', 'trend ai', 'vpn', 'firewall'],
          bullets: ['Endpoint Protection, EDR & XDR with Trend AI', 'Next-Gen Firewall, UTM & Secure VPN', 'SOC, NOC & Managed Detection and Response', 'Security Migration & Implementation, including Air-Gapped Environments'] },
        { name: 'Cloud Services & Migration', url: 'cloud-solutions-support.html',
          keys: ['cloud', 'aws', 'azure', 'gcp', 'google cloud', 'migration', 'devops', 'kubernetes', 'containers'],
          bullets: ['Cloud Consulting, Architecture & Migration', 'DevOps & Process Automation', 'Cloud Security & Cost Optimization', 'Managed Cloud Services on AWS, Azure & GCP'] },
        { name: 'Data Center Infrastructure & Management', url: 'data-center-infrastructure.html',
          keys: ['data center', 'data centre', 'datacenter', 'datacentre', 'dcim', 'racks', 'rack', 'colocation', 'esg', 'sustainability', 'interconnect'],
          bullets: ['Integrated Data Center Infrastructure', 'High-Density Fiber & Copper Networking', 'Data Center Interconnect, Cloud Connectivity & Edge Infrastructure', 'Intelligent Infrastructure Management (DCIM) for Energy & Sustainability'] },
        { name: 'Time, Attendance & Access Control', url: 'time-attendance-access-control.html',
          keys: ['attendance', 'biometric', 'access control', 'visitor', 'visitors', 'fingerprint', 'iris', 'hid', 'payroll', 'door'],
          bullets: ['Biometric Attendance: Face, Fingerprint, HID & Iris', 'Time & Attendance Management', 'Access Control & Visitor Management', 'Multi-Location Management with HR/Payroll Integration'] },
        { name: 'IT Asset Management & Refresh', url: 'amc-asset-recovery-ewaste.html',
          keys: ['asset', 'assets', 'amc', 'e-waste', 'ewaste', 'refresh', 'sanitization', 'sanitisation', 'recycling', 'disposal', 'itam'],
          bullets: ['Asset Discovery & Lifecycle Visibility', 'AMC & Multi-Vendor IT Support', 'Technology Refresh & Modernization', 'Secure Data Sanitization & E-Waste Management'] },
        { name: 'Enterprise IT Infrastructure', url: 'enterprise-it.html',
          keys: ['enterprise it', 'laptop', 'laptops', 'desktop', 'desktops', 'server', 'servers', 'storage', 'workstation', 'workstations', 'end-user', 'dell', 'hp', 'lenovo', 'compute'],
          bullets: ['End-User Computing & Collaboration Technology', 'Enterprise Networking, Wi-Fi & SD-WAN', 'Servers, Compute & Enterprise Storage', 'Data Center Infrastructure & Lifecycle Support'] },
        { name: 'Optics, Photonics & RF Systems', url: 'optics-photonics-rf-systems.html',
          keys: ['optics', 'optical', 'photonics', 'rf', 'radio frequency', 'radar', 'drone', 'drones', 'gimbal', 'gimbals', 'line-of-sight'],
          bullets: ['Optics & Photonics', 'RF Photonics & RF Systems', 'Precision Gimbals for Fine Line-of-Sight Alignment', 'Drones & Radar Systems'] }
    ];

    var DISTRIBUTORS = [
        { name: 'Rittal', url: 'rittal-details.html', keys: ['rittal'] },
        { name: 'R&M (Reichle & De-Massari AG)', url: 'rm-details.html', keys: ['r&m', 'r & m', 'reichle', 'massari'] },
        { name: 'NETKA System', url: 'netka-details.html', keys: ['netka'] },
        { name: 'MATRIX', url: 'matrix-details.html', keys: ['matrix'] }
    ];

    var MAIN_MENU = ['Our Solutions', 'Distribution', 'Our Partners', 'About Octel', 'Contact Us'];

    function link(url, text) {
        return '<a href="' + url + '">' + text + '</a>';
    }
    function list(items) {
        return '<ul>' + items.map(function (i) { return '<li>' + i + '</li>'; }).join('') + '</ul>';
    }

    var INTENTS = [
        { id: 'greeting', keys: ['hi', 'hello', 'hey', 'hii', 'good morning', 'good afternoon', 'good evening', 'menu', 'start', 'help'],
          answer: function () {
              return { html: 'Hello! How can I help you today? Pick a topic below or type your question.', replies: MAIN_MENU };
          } },
        { id: 'solutions', keys: ['solutions', 'solution', 'services', 'service', 'what do you do', 'what you do', 'offer', 'offerings', 'our solutions'],
          answer: function () {
              return { html: 'Octel Networks offers these solutions:' + list(SOLUTIONS.map(function (s) { return link(s.url, s.name); })) +
                       'See them all on the ' + link('services.html', 'Solutions page') + '.', replies: ['Distribution', 'Contact Us'] };
          } },
        { id: 'distribution', keys: ['distribution', 'distributor', 'distributors', 'distribute', 'brands', 'manufacturers', 'oem'],
          answer: function () {
              return { html: 'We have direct partnerships with leading manufacturers:' + list(DISTRIBUTORS.map(function (d) { return link(d.url, d.name); })) +
                       'Every product comes with full manufacturer warranty and support. Learn more on the ' + link('distribution.html', 'Distribution page') + '.',
                       replies: ['Our Partners', 'Contact Us'] };
          } },
        { id: 'partners', keys: ['partners', 'partner', 'our partners', 'peripheral', 'backup', 'software'],
          answer: function () {
              return { html: 'Our partner ecosystem covers:' + list(['Desktops, Laptops, Servers & Workstations', 'Networking & Security', 'Peripheral Products',
                       'Software Solutions', 'Backup Solutions', 'Storage Solutions', 'Cloud Partners', 'Security Software', 'Passive Components']) +
                       'Explore them on the ' + link('partners.html', 'Our Partners page') + '.', replies: ['Our Solutions', 'Contact Us'] };
          } },
        { id: 'about', keys: ['about', 'about octel', 'who are you', 'company', 'history', 'founded', 'founder', 'since', 'experience', 'team size', 'clients'],
          answer: function () {
              return { html: 'Founded in 1999 by Vijay Kumar Raju, Octel Networks began as a specialized network infrastructure provider in Bengaluru and evolved into a full-service network and IT infrastructure partner.' +
                       list(['15,000+ clients across enterprise, retail and mall environments', 'A growing team of 50+ professionals', 'A single point of contact and 24x7 assistance']) +
                       'Read more ' + link('about.html', 'about us') + ' or see ' + link('why-octel.html', 'why clients choose Octel') + '.',
                       replies: ['Leadership', 'Our Solutions', 'Contact Us'] };
          } },
        { id: 'why', keys: ['why octel', 'why choose', 'why should', 'different', 'sets you apart', 'advantage'],
          answer: function () {
              return { html: 'What sets us apart:' + list(['<b>Authorized Distribution</b> with direct manufacturer partnerships and genuine products',
                       '<b>Engineering-Led Deployments</b>, scoped and supported by engineers, right-sized to your actual load',
                       '<b>Long-Term Support</b> that stays engaged after go-live and scales as you grow']) +
                       'More on the ' + link('why-octel.html', 'Why Octel page') + '.', replies: ['About Octel', 'Contact Us'] };
          } },
        { id: 'leadership', keys: ['leadership', 'leaders', 'ceo', 'management', 'director'],
          answer: function () {
              return { html: list(['<b>Vijaya Kumar Raju</b>, Founder & CEO', '<b>Rajeev Pillai</b>, Business Head']) +
                       'Meet the team on the ' + link('our-leaders.html', 'Our Leaders page') + '.', replies: ['About Octel', 'Contact Us'] };
          } },
        { id: 'contact', keys: ['contact', 'contact us', 'email', 'mail', 'reach', 'talk', 'quote', 'quotation', 'pricing', 'price', 'cost', 'enquiry', 'inquiry', 'call', 'phone number', 'demo', 'consultation', 'sales'],
          answer: function () {
              return { html: 'We would be glad to help. Email us at ' + link('mailto:' + EMAIL, EMAIL) + ' or send your requirement through the ' +
                       link('contact.html', 'Contact Us page') + ' and our team will get back to you.', replies: ['Office Locations', 'Our Solutions'] };
          } },
        { id: 'locations', keys: ['office', 'offices', 'location', 'locations', 'address', 'where', 'bangalore', 'bengaluru', 'chennai', 'hyderabad', 'branch', 'branches', 'visit'],
          answer: function () {
              return { html: list(['<b>Head Office, Bangalore:</b> 887 11th Main 4th Cross HAL 2nd Stage, Bangalore, Karnataka, India - 560008',
                       '<b>Chennai:</b> Puliyur 2nd Lane, 2nd Main Kodambakkam, Chennai, Tamil Nadu, India - 600024',
                       '<b>Hyderabad:</b> 1-10-39, 4th Floor, Gumidelli Towers, Prakash Nagar, Begumpet, Hyderabad, Telangana, India - 500016']) +
                       'See the ' + link('contact.html', 'Contact Us page') + ' for details.', replies: ['Contact Us'] };
          } },
        { id: 'careers', keys: ['career', 'careers', 'job', 'jobs', 'hiring', 'vacancy', 'vacancies', 'work with you', 'join', 'internship'],
          answer: function () {
              return { html: 'Interested in joining Octel Networks? See current openings on our ' + link('careers.html', 'Careers page') + '.', replies: ['About Octel', 'Contact Us'] };
          } },
        { id: 'cases', keys: ['case study', 'case studies', 'projects', 'portfolio', 'references', 'customers'],
          answer: function () {
              return { html: 'See examples of our work on the ' + link('case-studies.html', 'Case Studies page') + '.', replies: ['Our Solutions', 'Contact Us'] };
          } },
        { id: 'events', keys: ['event', 'events', 'launch'],
          answer: function () {
              return { html: list(['<b>NETKA Launch, India:</b> Octel introduced NETKA to the Indian market as its exclusive distributor, with launches in Chennai and Bengaluru in 2026.',
                       '<b>MATRIX Event, Bengaluru:</b> held on July 31, 2026, bringing together industry professionals and clients.']) +
                       'More on the ' + link('about.html', 'About Us page') + '.', replies: ['Distribution', 'Contact Us'] };
          } },
        { id: 'thanks', keys: ['thanks', 'thank you', 'thank', 'ok', 'okay', 'great', 'bye', 'goodbye'],
          answer: function () {
              return { html: 'You are welcome! Is there anything else I can help you with?', replies: MAIN_MENU };
          } }
    ];

    // Quick-reply labels map straight to an intent.
    var QUICK = {
        'our solutions': 'solutions', 'distribution': 'distribution', 'our partners': 'partners',
        'about octel': 'about', 'contact us': 'contact', 'office locations': 'locations', 'leadership': 'leadership'
    };

    /* ---------- Matching ---------- */

    function normalize(text) {
        return ' ' + text.toLowerCase().replace(/[^a-z0-9&\-\s]/g, ' ').replace(/\s+/g, ' ').trim() + ' ';
    }
    function hits(text, keys) {
        var score = 0;
        keys.forEach(function (k) {
            if (text.indexOf(' ' + k + ' ') !== -1) score += k.split(' ').length;
        });
        return score;
    }
    function solutionAnswer(s) {
        return { html: '<b>' + s.name + '</b>' + list(s.bullets) + link(s.url, 'Discover more about ' + s.name) + '.', replies: ['Our Solutions', 'Contact Us'] };
    }
    function distributorAnswer(d) {
        return { html: 'Octel Networks is an authorized distribution partner for <b>' + d.name + '</b>. ' +
                 link(d.url, 'View ' + d.name + ' details') + ' or see all brands on the ' + link('distribution.html', 'Distribution page') + '.',
                 replies: ['Distribution', 'Contact Us'] };
    }

    function respond(input) {
        var raw = input.trim().toLowerCase();
        if (QUICK[raw]) return findIntent(QUICK[raw]).answer();

        var text = normalize(input);
        var best = null, bestScore = 0;

        DISTRIBUTORS.forEach(function (d) {
            var sc = hits(text, d.keys) * 3;
            if (sc > bestScore) { bestScore = sc; best = distributorAnswer(d); }
        });
        SOLUTIONS.forEach(function (s) {
            var sc = hits(text, s.keys) * 2;
            if (sc > bestScore) { bestScore = sc; best = solutionAnswer(s); }
        });
        INTENTS.forEach(function (it) {
            var sc = hits(text, it.keys);
            if (sc > bestScore) { bestScore = sc; best = it.answer(); }
        });

        return best || {
            html: 'Sorry, I could not find an answer to that. Please email us at ' + link('mailto:' + EMAIL, EMAIL) +
                  ' or use the ' + link('contact.html', 'Contact Us page') + ', and our team will help you.',
            replies: MAIN_MENU
        };
    }
    function findIntent(id) {
        for (var i = 0; i < INTENTS.length; i++) if (INTENTS[i].id === id) return INTENTS[i];
        return INTENTS[0];
    }

    /* ---------- Styles ---------- */

    var css = '' +
        '#octel-chatbot{--ocb-red:#e30016;--ocb-dark:#00252C;--ocb-bg:#fff;--ocb-soft:#f4f5f5;--ocb-text:#393737;--ocb-muted:#6b6b6b;--ocb-border:#e6e6e6;font-family:inherit;}' +
        '.theme-dark #octel-chatbot{--ocb-bg:#0f1a1c;--ocb-soft:#1b2a2d;--ocb-text:#f1f1f1;--ocb-muted:#a9b3b5;--ocb-border:#26383b;}' +
        '#octel-chatbot .ocb-launcher{position:fixed;right:30px;bottom:90px;z-index:1000;width:58px;height:58px;border-radius:50%;border:0;background:var(--ocb-red);color:#fff;font-size:26px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(227,0,22,.35);cursor:pointer;transition:transform .2s ease;}' +
        '#octel-chatbot .ocb-launcher:hover{transform:scale(1.06);}' +
        '#octel-chatbot .ocb-launcher:focus-visible,#octel-chatbot button:focus-visible,#octel-chatbot input:focus-visible{outline:2px solid var(--ocb-dark);outline-offset:2px;}' +
        '#octel-chatbot .ocb-panel{position:fixed;right:30px;bottom:160px;z-index:1001;width:370px;max-width:calc(100vw - 32px);height:520px;max-height:calc(100vh - 190px);background:var(--ocb-bg);color:var(--ocb-text);border-radius:20px;box-shadow:0 20px 50px rgba(0,0,0,.2);display:flex;flex-direction:column;overflow:hidden;opacity:0;visibility:hidden;transform:translateY(16px);transition:opacity .2s ease,transform .2s ease,visibility .2s;}' +
        '#octel-chatbot.ocb-open .ocb-panel{opacity:1;visibility:visible;transform:none;}' +
        '#octel-chatbot .ocb-head{background:var(--ocb-dark);color:#fff;padding:16px 18px;display:flex;align-items:center;gap:12px;}' +
        '#octel-chatbot .ocb-avatar{width:40px;height:40px;border-radius:50%;background:var(--ocb-red);display:flex;align-items:center;justify-content:center;font-size:20px;flex:none;}' +
        '#octel-chatbot .ocb-title{font-weight:700;font-size:16px;line-height:1.2;margin:0;color:#fff;}' +
        '#octel-chatbot .ocb-sub{font-size:13px;opacity:.8;margin:2px 0 0;}' +
        '#octel-chatbot .ocb-close{margin-left:auto;background:transparent;border:0;color:#fff;font-size:22px;cursor:pointer;line-height:1;padding:4px;}' +
        '#octel-chatbot .ocb-body{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;background:var(--ocb-soft);overscroll-behavior:contain;}' +
        '#octel-chatbot .ocb-msg{max-width:88%;padding:10px 14px;border-radius:16px;font-size:14px;line-height:1.55;word-wrap:break-word;}' +
        '#octel-chatbot .ocb-bot{align-self:flex-start;background:var(--ocb-bg);border:1px solid var(--ocb-border);border-bottom-left-radius:4px;}' +
        '#octel-chatbot .ocb-user{align-self:flex-end;background:var(--ocb-red);color:#fff;border-bottom-right-radius:4px;}' +
        '#octel-chatbot .ocb-msg ul{margin:6px 0;padding-left:18px;list-style:disc;}' +
        '#octel-chatbot .ocb-msg li{margin:2px 0;list-style:disc;}' +
        '#octel-chatbot .ocb-msg a{color:var(--ocb-red);font-weight:600;text-decoration:underline;}' +
        '#octel-chatbot .ocb-replies{display:flex;flex-wrap:wrap;gap:6px;align-self:flex-start;}' +
        '#octel-chatbot .ocb-chip{background:transparent;border:1px solid var(--ocb-red);color:var(--ocb-red);border-radius:999px;padding:6px 12px;font-size:13px;cursor:pointer;transition:background .15s,color .15s;}' +
        '#octel-chatbot .ocb-chip:hover{background:var(--ocb-red);color:#fff;}' +
        '#octel-chatbot .ocb-typing{display:inline-flex;gap:4px;}' +
        '#octel-chatbot .ocb-typing span{width:6px;height:6px;border-radius:50%;background:var(--ocb-muted);animation:ocb-blink 1s infinite;}' +
        '#octel-chatbot .ocb-typing span:nth-child(2){animation-delay:.15s}#octel-chatbot .ocb-typing span:nth-child(3){animation-delay:.3s}' +
        '@keyframes ocb-blink{0%,80%,100%{opacity:.3}40%{opacity:1}}' +
        '#octel-chatbot .ocb-form{display:flex;gap:8px;padding:12px;border-top:1px solid var(--ocb-border);background:var(--ocb-bg);}' +
        '#octel-chatbot .ocb-input{flex:1;min-width:0;border:1px solid var(--ocb-border);background:var(--ocb-bg);color:var(--ocb-text);border-radius:999px;padding:10px 16px;font-size:14px;outline:0;}' +
        '#octel-chatbot .ocb-send{width:42px;height:42px;flex:none;border-radius:50%;border:0;background:var(--ocb-red);color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;}' +
        '@media (max-width:575px){#octel-chatbot .ocb-launcher{right:16px;bottom:80px;width:52px;height:52px;font-size:24px;}' +
        '#octel-chatbot .ocb-panel{right:16px;left:16px;width:auto;max-width:none;bottom:144px;height:auto;top:80px;max-height:none;}}' +
        '@media (prefers-reduced-motion:reduce){#octel-chatbot *{transition:none!important;animation:none!important;}}';

    var style = document.createElement('style');
    style.id = 'octel-chatbot-styles';
    style.textContent = css;
    document.head.appendChild(style);

    /* ---------- Markup ---------- */

    var root = document.createElement('div');
    root.id = 'octel-chatbot';
    root.innerHTML =
        '<button type="button" class="ocb-launcher" aria-label="Open chat" aria-expanded="false" aria-controls="ocb-panel"><i class="ri-chat-3-line" aria-hidden="true"></i></button>' +
        '<div class="ocb-panel" id="ocb-panel" role="dialog" aria-label="Octel Networks chat" aria-hidden="true">' +
            '<div class="ocb-head">' +
                '<span class="ocb-avatar" aria-hidden="true"><i class="ri-customer-service-2-line"></i></span>' +
                '<div><p class="ocb-title">Octel Networks</p><p class="ocb-sub">Ask about our solutions &amp; services</p></div>' +
                '<button type="button" class="ocb-close" aria-label="Close chat"><i class="ri-close-line" aria-hidden="true"></i></button>' +
            '</div>' +
            '<div class="ocb-body" aria-live="polite"></div>' +
            '<form class="ocb-form" autocomplete="off">' +
                '<label for="ocb-input" class="visually-hidden" style="position:absolute;left:-9999px;">Type your message</label>' +
                '<input id="ocb-input" class="ocb-input" type="text" placeholder="Type your question..." maxlength="300">' +
                '<button type="submit" class="ocb-send" aria-label="Send"><i class="ri-send-plane-fill" aria-hidden="true"></i></button>' +
            '</form>' +
        '</div>';
    document.body.appendChild(root);

    var launcher = root.querySelector('.ocb-launcher');
    var panel = root.querySelector('.ocb-panel');
    var body = root.querySelector('.ocb-body');
    var form = root.querySelector('.ocb-form');
    var input = root.querySelector('.ocb-input');
    var closeBtn = root.querySelector('.ocb-close');
    var started = false;

    // Keep the page's smooth-scroll (Lenis) from hijacking wheel events inside the chat.
    panel.setAttribute('data-lenis-prevent', '');

    function scrollDown() {
        body.scrollTop = body.scrollHeight;
    }
    function addUser(text) {
        var el = document.createElement('div');
        el.className = 'ocb-msg ocb-user';
        el.textContent = text;
        body.appendChild(el);
        scrollDown();
    }
    function clearReplies() {
        body.querySelectorAll('.ocb-replies').forEach(function (r) { r.remove(); });
    }
    function addBot(reply) {
        var typing = document.createElement('div');
        typing.className = 'ocb-msg ocb-bot';
        typing.innerHTML = '<span class="ocb-typing" aria-label="Typing"><span></span><span></span><span></span></span>';
        body.appendChild(typing);
        scrollDown();

        setTimeout(function () {
            typing.innerHTML = reply.html;
            if (reply.replies && reply.replies.length) {
                var wrap = document.createElement('div');
                wrap.className = 'ocb-replies';
                reply.replies.forEach(function (label) {
                    var chip = document.createElement('button');
                    chip.type = 'button';
                    chip.className = 'ocb-chip';
                    chip.textContent = label;
                    chip.addEventListener('click', function () { send(label); });
                    wrap.appendChild(chip);
                });
                body.appendChild(wrap);
            }
            scrollDown();
        }, 450);
    }
    function send(text) {
        text = (text || '').trim();
        if (!text) return;
        clearReplies();
        addUser(text);
        addBot(respond(text));
    }

    function openChat() {
        root.classList.add('ocb-open');
        launcher.setAttribute('aria-expanded', 'true');
        launcher.setAttribute('aria-label', 'Close chat');
        launcher.innerHTML = '<i class="ri-close-line" aria-hidden="true"></i>';
        panel.setAttribute('aria-hidden', 'false');
        if (!started) {
            started = true;
            addBot({ html: 'Hi! Welcome to <b>Octel Networks</b>. How can I help you today?', replies: MAIN_MENU });
        }
        setTimeout(function () { input.focus(); }, 200);
    }
    function closeChat() {
        root.classList.remove('ocb-open');
        launcher.setAttribute('aria-expanded', 'false');
        launcher.setAttribute('aria-label', 'Open chat');
        launcher.innerHTML = '<i class="ri-chat-3-line" aria-hidden="true"></i>';
        panel.setAttribute('aria-hidden', 'true');
        launcher.focus();
    }

    launcher.addEventListener('click', function () {
        root.classList.contains('ocb-open') ? closeChat() : openChat();
    });
    closeBtn.addEventListener('click', closeChat);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && root.classList.contains('ocb-open')) closeChat();
    });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = input.value;
        input.value = '';
        send(text);
    });
})();
