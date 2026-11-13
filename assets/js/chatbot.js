/*-------------------------------
    OCTA — Octel Technical Assistant
    "Technical Answers, Round the Clock"
    Self-contained: injects its own styles and markup.
    Answers come only from content already published on the site.
    Leads are sent to chatbot-lead.php (saved under Admin > Contacts and emailed to Sales/Support).
    Keep every bot message to two or three lines.
-------------------------------*/
(function () {
    'use strict';

    if (document.getElementById('octel-chatbot')) return;

    var EMAIL = 'info@octelnetworks.com';
    var NOC_PHONE = '080 49574038';
    var NOC_TEL = '+918049574038';
    var LEAD_URL = new URL('chatbot-lead.php', document.baseURI).href;

    /* ---------- Knowledge base ---------- */

    var SOLUTIONS = [
        { name: 'Network Infrastructure & Integration', url: 'it-network-infrastructure.html',
          keys: ['network infrastructure', 'networking', 'lan', 'wan', 'sd-wan', 'sdwan', 'wifi', 'wi-fi', 'wireless', 'switching', 'router', 'routing'],
          bullets: ['LAN, WAN & SD-WAN', 'Next-Gen Firewalls', 'Enterprise Wi-Fi'] },
        { name: 'IT Services & Managed Infrastructure', url: 'managed-it-services.html',
          keys: ['managed', 'managed services', 'it services', 'it consulting', 'consulting', 'project management', 'sla', 'facility management', 'preventive maintenance'],
          bullets: ['IT Consulting & Architecture', 'Project Management', 'SLA-Based Support'] },
        { name: 'Passive Network Infrastructure', url: 'passive-network-cabling.html',
          keys: ['passive', 'cabling', 'cable', 'cables', 'fiber', 'fibre', 'cat6', 'cat6a', 'patch panel', 'patch panels', 'structured cabling'],
          bullets: ['Cat6 / Cat6A Copper Cabling', 'Fiber Optic Backbone', 'Racks & Patch Panels'] },
        { name: 'IP Video Surveillance', url: 'ip-video-surveillance.html',
          keys: ['cctv', 'camera', 'cameras', 'surveillance', 'video', 'anpr', 'ptz', 'thermal', 'face recognition', 'vms'],
          bullets: ['IP Cameras (Dome, PTZ, Thermal)', 'Recording & Storage', 'AI Video Analytics'] },
        { name: 'IP Telephony & Unified Communications', url: 'ip-telephony.html',
          keys: ['telephony', 'voip', 'pbx', 'ip-pbx', 'ip phone', 'ip phones', 'phone system', 'unified communications', 'uc', 'sip', 'ivr', 'microsoft teams', 'softphone'],
          bullets: ['IP Phones & Softphones', 'IP-PBX', 'Microsoft Teams Integration'] },
        { name: 'Mall Infrastructure Management', url: 'mall-infrastructure-management.html',
          keys: ['mall', 'malls', 'shopping', 'tenant', 'tenants', 'guest wifi', 'guest wi-fi', 'retail'],
          bullets: ['Network Redundancy', 'Mall-Wide & Guest Wi-Fi', 'Tenant Connectivity'] },
        { name: 'Network Management & Monitoring', url: 'network-management-software.html',
          keys: ['monitoring', 'network management', 'nms', 'topology', 'discovery', 'siem', 'soar', 'capacity planning', 'dashboards'],
          bullets: ['Discovery & Topology Mapping', 'Real-Time Monitoring', 'Alerts & Reporting'] },
        { name: 'Cybersecurity', url: 'cyber-security.html',
          keys: ['cyber', 'cybersecurity', 'cyber security', 'security', 'xdr', 'zero trust', 'ztna', 'phishing', 'ransomware', 'dlp', 'identity', 'security testing', 'vapt'],
          bullets: ['EDR/XDR & Zero Trust', 'Email, Data & Identity Protection', 'Security Assessments'] },
        { name: 'Endpoint to UTM & Network Security', url: 'endpoint-network-security.html',
          keys: ['endpoint', 'utm', 'edr', 'antivirus', 'anti-virus', 'air-gap', 'air-gapped', 'air gap', 'soc', 'mdr', 'trend ai', 'vpn', 'firewall'],
          bullets: ['Endpoint Protection with Trend AI', 'Firewall, UTM & VPN', 'SOC, NOC & MDR'] },
        { name: 'Cloud Services & Migration', url: 'cloud-solutions-support.html',
          keys: ['cloud', 'aws', 'azure', 'gcp', 'google cloud', 'migration', 'devops', 'kubernetes', 'containers'],
          bullets: ['Cloud Architecture & Migration', 'DevOps & Automation', 'Managed AWS, Azure & GCP'] },
        { name: 'Data Center Infrastructure & Management', url: 'data-center-infrastructure.html',
          keys: ['data center', 'data centre', 'datacenter', 'datacentre', 'dcim', 'racks', 'rack', 'colocation', 'interconnect'],
          bullets: ['Integrated Data Center Builds', 'High-Density Fiber & Copper', 'DCIM & Energy Management'] },
        { name: 'Time, Attendance & Access Control', url: 'time-attendance-access-control.html',
          keys: ['attendance', 'biometric', 'access control', 'visitor', 'visitors', 'fingerprint', 'iris', 'hid', 'payroll', 'door'],
          bullets: ['Biometric Attendance', 'Access Control', 'Visitor Management'] },
        { name: 'IT Asset Management & AMC', url: 'amc-asset-recovery-ewaste.html',
          keys: ['asset', 'assets', 'amc', 'e-waste', 'ewaste', 'refresh', 'sanitization', 'sanitisation', 'recycling', 'disposal', 'itam'],
          bullets: ['AMC & Multi-Vendor Support', 'Technology Refresh', 'Secure E-Waste Disposal'] },
        { name: 'Enterprise IT Infrastructure', url: 'enterprise-it.html',
          keys: ['enterprise it', 'laptop', 'laptops', 'desktop', 'desktops', 'server', 'servers', 'storage', 'workstation', 'workstations', 'dell', 'hp', 'lenovo', 'compute'],
          bullets: ['Laptops & Desktops', 'Servers & Storage', 'Lifecycle Support'] },
        { name: 'Optics, Photonics & RF Systems', url: 'optics-photonics-rf-systems.html',
          keys: ['optics', 'optical', 'photonics', 'rf', 'radio frequency', 'radar', 'drone', 'drones', 'gimbal', 'gimbals'],
          bullets: ['Optics & Photonics', 'RF Systems', 'Drones & Radar'] }
    ];

    function solutionByUrl(url) {
        for (var i = 0; i < SOLUTIONS.length; i++) if (SOLUTIONS[i].url === url) return SOLUTIONS[i];
        return null;
    }

    // Solution areas offered as buttons under "Our Solutions".
    var AREAS = {
        'networking': 'it-network-infrastructure.html',
        'security': 'cyber-security.html',
        'cloud': 'cloud-solutions-support.html',
        'unified communications': 'ip-telephony.html',
        'managed services': 'managed-it-services.html',
        'surveillance': 'ip-video-surveillance.html'
    };

    var DISTRIBUTORS = [
        { name: 'MATRIX', url: 'matrix-details.html', keys: ['matrix'] },
        { name: 'R&M', url: 'rm-details.html', keys: ['r&m', 'r & m', 'reichle', 'massari'] },
        { name: 'Rittal', url: 'rittal-details.html', keys: ['rittal'] },
        { name: 'NETKA System', url: 'netka-details.html', keys: ['netka'] }
    ];

    var CONTACT_LABEL = 'Contact Sales or Support';
    var MAIN_MENU = ['Our Solutions', 'Distribution', 'Our Partners', CONTACT_LABEL];
    var EXPERT_LABEL = 'Talk to an expert';

    function link(url, text) {
        return '<a href="' + url + '">' + text + '</a>';
    }
    function esc(text) {
        return String(text).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    var INTENTS = [
        { id: 'greeting', keys: ['hi', 'hello', 'hey', 'hii', 'good morning', 'good afternoon', 'good evening', 'menu', 'main menu', 'start', 'help'],
          answer: function () {
              return { html: 'Hello again! Pick a topic below or type your question.', replies: MAIN_MENU };
          } },
        { id: 'solutions', keys: ['solutions', 'solution', 'services', 'service', 'what do you do', 'what you do', 'offer', 'offerings', 'our solutions'],
          answer: function () {
              return { html: 'We design, deploy and manage the full infrastructure stack.<br>Which area are you interested in?',
                       replies: ['Networking', 'Security', 'Cloud', 'Unified Communications', 'Managed Services', 'Surveillance', 'All Solutions'] };
          } },
        { id: 'allsolutions', keys: ['all solutions'],
          answer: function () {
              return { html: 'You can browse all 15 of our solutions on the ' + link('services.html', 'Solutions page') + '.',
                       replies: [EXPERT_LABEL, 'Main menu'] };
          } },
        { id: 'distribution', keys: ['distribution', 'distributor', 'distributors', 'distribute', 'brands', 'manufacturers', 'oem'],
          answer: function () {
              return { html: 'We are an authorised distributor for MATRIX, R&amp;M, Rittal and NETKA System, with full manufacturer warranty.<br>' +
                             'See the ' + link('distribution.html', 'Distribution page') + ' or pick a brand.',
                       replies: ['MATRIX', 'R&M', 'Rittal', 'NETKA System', EXPERT_LABEL] };
          } },
        { id: 'partners', keys: ['partners', 'partner', 'our partners', 'peripheral', 'backup', 'software'],
          answer: function () {
              return { html: 'We work with leading OEMs across computing, networking, security, storage, backup, cloud and software.<br>' +
                             'Explore them on the ' + link('partners.html', 'Our Partners page') + '.',
                       replies: ['Our Solutions', EXPERT_LABEL, 'Main menu'] };
          } },
        { id: 'about', keys: ['about', 'about octel', 'who are you', 'company', 'history', 'founded', 'founder', 'since', 'experience', 'team size', 'clients'],
          answer: function () {
              return { html: 'Founded in Bengaluru in 1999, Octel Networks is an end-to-end network and IT infrastructure partner for 15,000+ clients.<br>' +
                             'Read more ' + link('about.html', 'about us') + '.',
                       replies: ['Leadership', 'Our Solutions', CONTACT_LABEL] };
          } },
        { id: 'why', keys: ['why octel', 'why choose', 'why should', 'different', 'sets you apart', 'advantage'],
          answer: function () {
              return { html: 'One point of accountability, 24x7 support and 25+ years of delivery for 15,000+ clients.<br>' +
                             'More on the ' + link('why-octel.html', 'Why Octel page') + '.',
                       replies: ['Our Solutions', CONTACT_LABEL] };
          } },
        { id: 'leadership', keys: ['leadership', 'leaders', 'ceo', 'coo', 'management', 'director'],
          answer: function () {
              return { html: '<b>VK Raju</b>, Founder, Managing Director &amp; CEO<br><b>Rajeev Pillai</b>, Chief Operating Officer<br>' +
                             'Meet the team on the ' + link('our-leaders.html', 'Our Leaders page') + '.',
                       replies: ['About Octel', CONTACT_LABEL] };
          } },
        { id: 'support', keys: ['support', 'issue', 'problem', 'not working', 'down', 'outage', 'urgent', 'noc', 'complaint', 'ticket', 'breakdown'],
          answer: function () {
              return { html: 'For urgent 24x7 support, please call our NOC on ' + link('tel:' + NOC_TEL, NOC_PHONE) + '.<br>' +
                             'Or I can pass a support request to our team.',
                       replies: ['Log a support request', 'Main menu'] };
          } },
        { id: 'locations', keys: ['office', 'offices', 'location', 'locations', 'address', 'where', 'branch', 'branches', 'visit'],
          answer: function () {
              return { html: 'We have offices in Bangalore (head office), Chennai and Hyderabad.<br>' +
                             'Addresses are on the ' + link('contact.html', 'Contact Us page') + '.',
                       replies: [CONTACT_LABEL, 'Main menu'] };
          } },
        { id: 'careers', keys: ['career', 'careers', 'job', 'jobs', 'hiring', 'vacancy', 'vacancies', 'work with you', 'join', 'internship'],
          answer: function () {
              return { html: 'Interested in joining Octel Networks?<br>See current openings on our ' + link('careers.html', 'Careers page') + '.',
                       replies: ['About Octel', 'Main menu'] };
          } },
        { id: 'cases', keys: ['case study', 'case studies', 'projects', 'portfolio', 'references', 'customers'],
          answer: function () {
              return { html: 'See examples of our work on the ' + link('case-studies.html', 'Case Studies page') + '.',
                       replies: ['Our Solutions', EXPERT_LABEL] };
          } },
        { id: 'thanks', keys: ['thanks', 'thank you', 'thank', 'ok', 'okay', 'great', 'bye', 'goodbye'],
          answer: function () {
              return { html: 'You are welcome! Is there anything else I can help you with?', replies: MAIN_MENU };
          } }
    ];

    var QUICK = {
        'our solutions': 'solutions', 'all solutions': 'allsolutions', 'distribution': 'distribution', 'our partners': 'partners',
        'about octel': 'about', 'leadership': 'leadership', 'main menu': 'greeting'
    };

    /* ---------- Lead capture (asked one at a time, only after a path is chosen) ---------- */

    var CITIES = ['Bangalore', 'Chennai', 'Hyderabad', 'Mumbai', 'Delhi', 'Other'];
    var NEEDS = ['Networking', 'Security', 'Wireless', 'UC', 'AMC', 'Other'];
    var lead = null;   // { step: 'desk'|'need'|..., data: {...}, otherCity: bool }

    var STEP_ORDER = ['desk', 'need', 'company', 'name', 'size', 'city', 'reach'];

    function stepPrompt(step) {
        var d = lead.data;
        switch (step) {
            case 'desk': return { html: 'Happy to connect you with our team.<br>Is this a new requirement (Sales) or help with an existing setup (Support)?', replies: ['Sales', 'Support', 'Cancel'] };
            case 'need': return { html: 'What do you need help with?', replies: NEEDS.concat(['Cancel']) };
            case 'company': return { html: 'Thanks. What is your company name?', replies: ['Cancel'] };
            case 'name': return { html: 'And your name, please?', replies: ['Cancel'] };
            case 'size': return { html: 'Thanks, ' + esc(d.name.split(' ')[0]) + '. How many sites and users?<br>For example: 1 office, 50 users.', replies: ['Cancel'] };
            case 'city': return lead.otherCity
                ? { html: 'Please type your city.', replies: ['Cancel'] }
                : { html: 'Which city are you based in?', replies: CITIES.concat(['Cancel']) };
            case 'reach': return { html: 'What is the best email or mobile number to reach you?', replies: ['Cancel'] };
        }
    }

    function nextStep() {
        var i = STEP_ORDER.indexOf(lead.step) + 1;
        while (i < STEP_ORDER.length && lead.data[STEP_ORDER[i]]) i++;
        return STEP_ORDER[i] || null;
    }

    function startLead(prefill) {
        lead = { step: null, data: prefill || {}, otherCity: false };
        lead.step = STEP_ORDER[0];
        if (lead.data[lead.step]) lead.step = nextStep();
        return stepPrompt(lead.step);
    }

    function leadAnswer(text) {
        var value = text.trim();
        var step = lead.step;

        if (step === 'desk') {
            var v = value.toLowerCase();
            if (v.indexOf('support') !== -1) value = 'Support';
            else if (v.indexOf('sales') !== -1 || v.indexOf('new') !== -1) value = 'Sales';
            else return { html: 'Please choose Sales or Support.', replies: ['Sales', 'Support', 'Cancel'] };
        }
        if (step === 'city' && !lead.otherCity && value.toLowerCase() === 'other') {
            lead.otherCity = true;
            return stepPrompt('city');
        }
        var error = validate(step, value);
        if (error) {
            return { html: '<span class="ocb-error"><i class="ri-error-warning-line" aria-hidden="true"></i> ' + error + '</span>', replies: ['Cancel'] };
        }
        if (step === 'reach') value = cleanReach(value);
        if (value.length > 150) value = value.slice(0, 150);
        lead.data[step] = value;

        var next = nextStep();
        if (next) {
            lead.step = next;
            return stepPrompt(next);
        }
        submitLead();
        return null;
    }

    /* ---------- Answer validation (returns an error message, or '' when valid) ---------- */

    var EMAIL_RE = /^[A-Za-z0-9._%+\-]+@[A-Za-z0-9\-]+(\.[A-Za-z0-9\-]+)*\.[A-Za-z]{2,}$/;

    function phoneDigits(value) {
        return value.replace(/[\s\-()]/g, '');
    }
    function isValidPhone(value) {
        if (!/^\+?[0-9\s\-()]+$/.test(value)) return false;      // only digits, spaces, hyphens, brackets, leading +
        var p = phoneDigits(value);
        if (/^(\+91|91|0)?[6-9][0-9]{9}$/.test(p)) return true;   // Indian mobile: 98765 43210, +91 98765 43210
        if (/^0[1-9][0-9]{9}$/.test(p)) return true;              // Indian landline with STD code: 080 41155349
        return /^\+[1-9][0-9]{7,14}$/.test(p);                    // International, with country code
    }
    function cleanReach(value) {
        return EMAIL_RE.test(value) ? value.toLowerCase() : value.replace(/\s+/g, ' ');
    }

    function validate(step, value) {
        switch (step) {
            case 'need':
                return value.length < 2 ? 'Please choose an option or tell me briefly what you need.' : '';
            case 'company':
                if (value.length < 2 || !/[A-Za-z]/.test(value)) return 'Please enter a valid company name.';
                if (value.length > 100) return 'That company name is too long. Please shorten it.';
                if (EMAIL_RE.test(value) || /^[\d\s+\-()]+$/.test(value)) return 'Please enter your company name here, not an email or phone number.';
                return '';
            case 'name':
                if (!/^[A-Za-z][A-Za-z .'\-]*$/.test(value) || value.replace(/[^A-Za-z]/g, '').length < 2) {
                    return 'Please enter a valid name, using letters only.<br>For example: Priya Sharma.';
                }
                return value.length > 60 ? 'That name is too long. Please shorten it.' : '';
            case 'size':
                return /\d/.test(value) ? '' : 'Please include a number of sites or users.<br>For example: 1 office, 50 users.';
            case 'city':
                return /^[A-Za-z][A-Za-z .\-]{1,49}$/.test(value) ? '' : 'Please enter a valid city name, using letters only.';
            case 'reach':
                if (value.indexOf('@') !== -1) {
                    return EMAIL_RE.test(value) ? '' : 'That email address does not look right.<br>For example: name@company.com.';
                }
                return isValidPhone(value) ? '' : 'That mobile number does not look right.<br>Please enter 10 digits, e.g. 98765 43210, or include the country code, e.g. +91 98765 43210.';
        }
        return '';
    }

    function submitLead() {
        var payload = {
            desk: lead.data.desk || 'Sales', need: lead.data.need || '', company: lead.data.company || '',
            name: lead.data.name || '', size: lead.data.size || '', city: lead.data.city || '',
            reach: lead.data.reach || '', page: location.pathname, website: ''
        };
        lead = null;
        var typing = showTyping();

        fetch(LEAD_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function (r) {
            return r.json().catch(function () { return { ok: false }; });
        }).then(function (res) {
            if (res && res.ok) {
                fillBot(typing, { html: 'Thank you. I have shared your details with our team, who will contact you within one working day.<br>' +
                                         'For urgent 24x7 support, please call our NOC on ' + link('tel:' + NOC_TEL, NOC_PHONE) + '.',
                                  replies: ['Main menu'] });
            } else {
                fillBot(typing, failMessage(res && res.error));
            }
        }).catch(function () {
            fillBot(typing, failMessage());
        });
    }

    function failMessage(error) {
        return { html: (error ? esc(error) + '<br>' : 'Sorry, I could not send your details just now.<br>') +
                       'Please email ' + link('mailto:' + EMAIL, EMAIL) + ' or call ' + link('tel:' + NOC_TEL, NOC_PHONE) + '.',
                 replies: [CONTACT_LABEL, 'Main menu'] };
    }

    /* ---------- Confidentiality ---------- */

    var SENSITIVE = /\b(password|passwd|pwd|passcode|pass code|otp|credentials?|login details|username and password|secret key|api key|private key|ssh key|pre-?shared key|psk|snmp community)\b|\b\d{1,3}(\.\d{1,3}){3}\b/i;

    function sensitiveWarning() {
        return 'For your security, please do not share passwords, IP addresses or other sensitive network details in chat.<br>' +
               'Our engineers will collect what they need securely.';
    }

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
    var lastTopic = '';
    function solutionAnswer(s) {
        lastTopic = s.name;
        return { html: '<b>' + s.name + '</b><br>' + s.bullets.join(' · ') + '<br>' + link(s.url, 'Learn more'),
                 replies: [EXPERT_LABEL, 'Our Solutions', 'Main menu'] };
    }
    function distributorAnswer(d) {
        lastTopic = d.name + ' products';
        return { html: 'We are an authorised distribution partner for <b>' + d.name + '</b>.<br>' + link(d.url, 'View ' + d.name + ' details'),
                 replies: [EXPERT_LABEL, 'Distribution', 'Main menu'] };
    }
    function findIntent(id) {
        for (var i = 0; i < INTENTS.length; i++) if (INTENTS[i].id === id) return INTENTS[i];
        return INTENTS[0];
    }

    function respond(input) {
        var raw = input.trim().toLowerCase();

        // While collecting details, every message is an answer (unless the visitor cancels).
        if (lead) {
            if (raw === 'cancel' || raw === 'main menu' || raw === 'menu') {
                lead = null;
                return { html: 'No problem. What else can I help you with?', replies: MAIN_MENU };
            }
            if (SENSITIVE.test(input)) {
                var again = stepPrompt(lead.step);
                return { html: sensitiveWarning() + '<br>' + again.html, replies: again.replies };
            }
            return leadAnswer(input);
        }

        if (SENSITIVE.test(input)) return { html: sensitiveWarning(), replies: MAIN_MENU };

        if (raw === CONTACT_LABEL.toLowerCase() || raw === 'yes, connect me') return startLead({});
        if (raw === EXPERT_LABEL.toLowerCase()) return startLead({ desk: 'Sales', need: lastTopic });
        if (raw === 'log a support request') return startLead({ desk: 'Support' });
        if (AREAS[raw]) return solutionAnswer(solutionByUrl(AREAS[raw]));
        if (QUICK[raw]) return findIntent(QUICK[raw]).answer();

        var text = normalize(input);

        // Sales-type requests go straight to the contact flow.
        if (hits(text, ['contact', 'contact us', 'quote', 'quotation', 'pricing', 'price', 'cost', 'enquiry', 'inquiry', 'call me', 'callback', 'call back', 'demo', 'consultation', 'sales', 'talk to'])) {
            return startLead({});
        }

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
            html: 'I may not have the right answer for that.<br>Would you like me to connect you with our team?',
            replies: ['Yes, connect me', 'Main menu']
        };
    }

    /* ---------- Styles ---------- */

    var css = '' +
        '#octel-chatbot{--ocb-red:#e30016;--ocb-dark:#00252C;--ocb-bg:#fff;--ocb-soft:#f4f5f5;--ocb-text:#393737;--ocb-muted:#6b6b6b;--ocb-border:#e6e6e6;font-family:inherit;}' +
        '.theme-dark #octel-chatbot{--ocb-bg:#0f1a1c;--ocb-soft:#1b2a2d;--ocb-text:#f1f1f1;--ocb-muted:#a9b3b5;--ocb-border:#26383b;}' +
        '#octel-chatbot .ocb-launcher{position:fixed;right:30px;bottom:90px;z-index:1000;width:58px;height:58px;border-radius:50%;border:0;background:var(--ocb-red);color:#fff;font-size:26px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(227,0,22,.35);cursor:pointer;transition:transform .2s ease;}' +
        '#octel-chatbot .ocb-launcher:hover{transform:scale(1.06);}' +
        '#octel-chatbot .ocb-launcher:focus-visible,#octel-chatbot button:focus-visible,#octel-chatbot input:focus-visible{outline:2px solid var(--ocb-dark);outline-offset:2px;}' +
        '#octel-chatbot .ocb-panel{position:fixed;right:30px;bottom:160px;z-index:1001;width:370px;max-width:calc(100vw - 32px);height:540px;max-height:calc(100vh - 190px);background:var(--ocb-bg);color:var(--ocb-text);border-radius:20px;box-shadow:0 20px 50px rgba(0,0,0,.2);display:flex;flex-direction:column;overflow:hidden;opacity:0;visibility:hidden;transform:translateY(16px);transition:opacity .2s ease,transform .2s ease,visibility .2s;}' +
        '#octel-chatbot.ocb-open .ocb-panel{opacity:1;visibility:visible;transform:none;}' +
        '#octel-chatbot .ocb-head{background:var(--ocb-dark);color:#fff;padding:16px 18px;display:flex;align-items:center;gap:12px;}' +
        '#octel-chatbot .ocb-avatar{width:42px;height:42px;border-radius:50%;background:var(--ocb-red);display:flex;align-items:center;justify-content:center;font-size:20px;flex:none;position:relative;}' +
        '#octel-chatbot .ocb-avatar::after{content:"";position:absolute;right:0;bottom:1px;width:11px;height:11px;border-radius:50%;background:#2ecc71;border:2px solid var(--ocb-dark);}' +
        '#octel-chatbot .ocb-title{font-weight:700;font-size:16px;line-height:1.2;margin:0;color:#fff;}' +
        '#octel-chatbot .ocb-title small{font-weight:400;font-size:12px;opacity:.75;margin-left:4px;}' +
        '#octel-chatbot .ocb-sub{font-size:12.5px;opacity:.8;margin:3px 0 0;}' +
        '#octel-chatbot .ocb-close{margin-left:auto;background:transparent;border:0;color:#fff;font-size:22px;cursor:pointer;line-height:1;padding:4px;}' +
        '#octel-chatbot .ocb-body{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;background:var(--ocb-soft);overscroll-behavior:contain;}' +
        '#octel-chatbot .ocb-msg{max-width:88%;padding:10px 14px;border-radius:16px;font-size:14px;line-height:1.55;word-wrap:break-word;}' +
        '#octel-chatbot .ocb-bot{align-self:flex-start;background:var(--ocb-bg);border:1px solid var(--ocb-border);border-bottom-left-radius:4px;}' +
        '#octel-chatbot .ocb-user{align-self:flex-end;background:var(--ocb-red);color:#fff;border-bottom-right-radius:4px;}' +
        '#octel-chatbot .ocb-error{color:var(--ocb-red);}' +
        '#octel-chatbot .ocb-error i{vertical-align:-2px;}' +
        '#octel-chatbot .ocb-msg a{color:var(--ocb-red);font-weight:600;text-decoration:underline;}' +
        '#octel-chatbot .ocb-replies{display:flex;flex-wrap:wrap;gap:6px;align-self:flex-start;}' +
        '#octel-chatbot .ocb-chip{background:transparent;border:1px solid var(--ocb-red);color:var(--ocb-red);border-radius:999px;padding:6px 12px;font-size:13px;cursor:pointer;transition:background .15s,color .15s;}' +
        '#octel-chatbot .ocb-chip:hover{background:var(--ocb-red);color:#fff;}' +
        '#octel-chatbot .ocb-chip.ocb-chip-muted{border-color:var(--ocb-border);color:var(--ocb-muted);}' +
        '#octel-chatbot .ocb-chip.ocb-chip-muted:hover{background:var(--ocb-border);color:var(--ocb-text);}' +
        '#octel-chatbot .ocb-typing{display:inline-flex;gap:4px;}' +
        '#octel-chatbot .ocb-typing span{width:6px;height:6px;border-radius:50%;background:var(--ocb-muted);animation:ocb-blink 1s infinite;}' +
        '#octel-chatbot .ocb-typing span:nth-child(2){animation-delay:.15s}#octel-chatbot .ocb-typing span:nth-child(3){animation-delay:.3s}' +
        '@keyframes ocb-blink{0%,80%,100%{opacity:.3}40%{opacity:1}}' +
        '#octel-chatbot .ocb-form{display:flex;gap:8px;padding:12px;border-top:1px solid var(--ocb-border);background:var(--ocb-bg);}' +
        '#octel-chatbot .ocb-input{flex:1;min-width:0;border:1px solid var(--ocb-border);background:var(--ocb-bg);color:var(--ocb-text);border-radius:999px;padding:10px 16px;font-size:14px;outline:0;}' +
        '#octel-chatbot .ocb-send{width:42px;height:42px;flex:none;border-radius:50%;border:0;background:var(--ocb-red);color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;}' +
        '#octel-chatbot .ocb-note{font-size:11.5px;color:var(--ocb-muted);text-align:center;padding:0 12px 10px;background:var(--ocb-bg);margin:0;}' +
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
        '<button type="button" class="ocb-launcher" aria-label="Chat with OCTA" aria-expanded="false" aria-controls="ocb-panel"><i class="ri-chat-3-line" aria-hidden="true"></i></button>' +
        '<div class="ocb-panel" id="ocb-panel" role="dialog" aria-label="OCTA, Octel Technical Assistant" aria-hidden="true">' +
            '<div class="ocb-head">' +
                '<span class="ocb-avatar" aria-hidden="true"><i class="ri-robot-2-line"></i></span>' +
                '<div><p class="ocb-title">OCTA <small>Octel Technical Assistant</small></p><p class="ocb-sub">Technical Answers, Round the Clock</p></div>' +
                '<button type="button" class="ocb-close" aria-label="Close chat"><i class="ri-close-line" aria-hidden="true"></i></button>' +
            '</div>' +
            '<div class="ocb-body" aria-live="polite"></div>' +
            '<form class="ocb-form" autocomplete="off">' +
                '<label for="ocb-input" class="visually-hidden" style="position:absolute;left:-9999px;">Type your message</label>' +
                '<input id="ocb-input" class="ocb-input" type="text" placeholder="Type your message..." maxlength="300">' +
                '<button type="submit" class="ocb-send" aria-label="Send"><i class="ri-send-plane-fill" aria-hidden="true"></i></button>' +
            '</form>' +
            '<p class="ocb-note">Please do not share passwords or sensitive network details in chat.</p>' +
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
    function showTyping() {
        var typing = document.createElement('div');
        typing.className = 'ocb-msg ocb-bot';
        typing.innerHTML = '<span class="ocb-typing" aria-label="Typing"><span></span><span></span><span></span></span>';
        body.appendChild(typing);
        scrollDown();
        return typing;
    }
    function fillBot(el, reply) {
        el.innerHTML = reply.html;
        if (reply.replies && reply.replies.length) {
            var wrap = document.createElement('div');
            wrap.className = 'ocb-replies';
            reply.replies.forEach(function (label) {
                var chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'ocb-chip' + (label === 'Cancel' || label === 'Main menu' ? ' ocb-chip-muted' : '');
                chip.textContent = label;
                chip.addEventListener('click', function () { send(label); });
                wrap.appendChild(chip);
            });
            body.appendChild(wrap);
        }
        scrollDown();
    }
    function addBot(reply) {
        var typing = showTyping();
        setTimeout(function () { fillBot(typing, reply); }, 450);
    }
    function send(text) {
        text = (text || '').trim();
        if (!text) return;
        clearReplies();
        addUser(text);
        var reply = respond(text);
        if (reply) addBot(reply);   // null while a lead is being submitted
    }

    function openChat() {
        root.classList.add('ocb-open');
        launcher.setAttribute('aria-expanded', 'true');
        launcher.setAttribute('aria-label', 'Close chat');
        launcher.innerHTML = '<i class="ri-close-line" aria-hidden="true"></i>';
        panel.setAttribute('aria-hidden', 'false');
        if (!started) {
            started = true;
            addBot({ html: 'Hello, I\'m <b>OCTA</b> — Octel Technical Assistant.<br>' +
                           'I can help with Networking, Security, Cloud, Unified Communications and Managed Services, among other solutions.<br>' +
                           'How may I assist you today?',
                     replies: MAIN_MENU });
        }
        setTimeout(function () { input.focus(); }, 200);
    }
    function closeChat() {
        root.classList.remove('ocb-open');
        launcher.setAttribute('aria-expanded', 'false');
        launcher.setAttribute('aria-label', 'Chat with OCTA');
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
