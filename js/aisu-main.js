/* =============================================
   AISU - All India Students Union
   Main JavaScript (Premium Enhanced)
   ============================================= */

document.addEventListener('DOMContentLoaded', function () {

    /* ─── PREMIUM MAGIC RING CURSOR (native cursor + CSS div overlay) ─── */
    const isTouchDevice = () => window.matchMedia('(pointer: coarse)').matches;
    if (!isTouchDevice()) {

        // Inject cursor styles
        const cursorStyle = document.createElement('style');
        cursorStyle.textContent = `
            #aisu-dot {
                position: fixed;
                top: 0; left: 0;
                width: 8px; height: 8px;
                background: #FF6F0F;
                border-radius: 50%;
                pointer-events: none;
                z-index: 2147483647;
                transform: translate(-50%, -50%);
                will-change: transform;
                box-shadow: 0 0 10px 2px rgba(255,111,15,0.65);
                transition: width 0.15s, height 0.15s;
            }
            #aisu-ring {
                position: fixed;
                top: 0; left: 0;
                width: 44px; height: 44px;
                border: 2px solid rgba(255,111,15,0.55);
                border-radius: 50%;
                pointer-events: none;
                z-index: 2147483646;
                transform: translate(-50%, -50%);
                will-change: transform;
                transition:
                    left 0.14s cubic-bezier(.17,.67,.35,1.2),
                    top  0.14s cubic-bezier(.17,.67,.35,1.2),
                    width 0.2s, height 0.2s,
                    border-color 0.2s, border-width 0.15s;
            }
            #aisu-ring.hovering {
                width: 64px; height: 64px;
                border-color: rgba(255,111,15,0.85);
                border-width: 2.5px;
                box-shadow: 0 0 14px rgba(255,111,15,0.3);
            }
            /* Pearl particles that shoot outward on click */
            .aisu-pearl {
                position: fixed;
                border-radius: 50%;
                pointer-events: none;
                z-index: 2147483645;
                background: #FF6F0F;
                box-shadow: 0 0 6px 1px rgba(255,111,15,0.7);
                animation: aisu-pearl-anim var(--dur, 0.5s) ease-out forwards;
            }
            @keyframes aisu-pearl-anim {
                0%   { transform: translate(-50%,-50%) translate(0px,0px); opacity: 1; }
                100% { transform: translate(-50%,-50%) translate(var(--tx),var(--ty)); opacity: 0; }
            }
            /* Thin expanding ring on click */
            .aisu-click-ring {
                position: fixed;
                border-radius: 50%;
                pointer-events: none;
                z-index: 2147483644;
                border: 2px solid rgba(255,111,15,0.7);
                background: transparent;
                width: 8px; height: 8px;
                animation: aisu-ring-anim 0.5s ease-out forwards;
            }
            @keyframes aisu-ring-anim {
                0%   { transform: translate(-50%,-50%) scale(1); opacity: 0.8; width:  8px; height:  8px; }
                100% { transform: translate(-50%,-50%) scale(1); opacity: 0;   width: 70px; height: 70px; }
            }
        `;
        document.head.appendChild(cursorStyle);

        // Create dot (exact cursor tip — zero lag)
        const dot = document.createElement('div');
        dot.id = 'aisu-dot';

        // Create ring (lags via CSS transition)
        const ring = document.createElement('div');
        ring.id = 'aisu-ring';

        // Append to documentElement to escape any body transforms
        document.documentElement.appendChild(dot);
        document.documentElement.appendChild(ring);

        let mx = -999, my = -999;

        // Move dot instantly to exact cursor position
        document.addEventListener('mousemove', e => {
            mx = e.clientX;
            my = e.clientY;
            // Dot: instant (no transition)
            dot.style.left = mx + 'px';
            dot.style.top  = my + 'px';
            // Ring: CSS transition provides the spring lag
            ring.style.left = mx + 'px';
            ring.style.top  = my + 'px';
        });

        // Hover expand — event delegation
        document.addEventListener('mouseover', e => {
            if (e.target.closest('a, button, [role="button"], .btn-hero-primary, .nav-link-custom, .feature-card, .initiative-card, .option-btn, .login-role-tab, .sidebar-link, .stat-card, .filter-btn, .nav-link, .dropdown-item')) {
                ring.classList.add('hovering');
                dot.style.width  = '12px';
                dot.style.height = '12px';
            }
        });
        document.addEventListener('mouseout', e => {
            if (e.target.closest('a, button, [role="button"], .btn-hero-primary, .nav-link-custom, .feature-card, .initiative-card, .option-btn, .login-role-tab, .sidebar-link, .stat-card, .filter-btn, .nav-link, .dropdown-item')) {
                ring.classList.remove('hovering');
                dot.style.width  = '8px';
                dot.style.height = '8px';
            }
        });

        // Click → 8 pearl sparks shoot outward + thin expanding ring
        document.addEventListener('click', e => {
            // 8 pearl dots flying outward in all directions
            for (let i = 0; i < 8; i++) {
                const angle    = (i / 8) * Math.PI * 2;
                const distance = 38 + Math.random() * 28;
                const tx = Math.cos(angle) * distance;
                const ty = Math.sin(angle) * distance;
                const size = 4 + Math.random() * 4;
                const dur  = (0.45 + Math.random() * 0.25).toFixed(2);

                const pearl = document.createElement('div');
                pearl.className = 'aisu-pearl';
                pearl.style.cssText = `
                    left: ${e.clientX}px;
                    top:  ${e.clientY}px;
                    width:  ${size}px;
                    height: ${size}px;
                    --tx: ${tx}px;
                    --ty: ${ty}px;
                    --dur: ${dur}s;
                `;
                document.documentElement.appendChild(pearl);
                pearl.addEventListener('animationend', () => pearl.remove());
            }
            // Thin expanding ring at click point
            const cRing = document.createElement('div');
            cRing.className = 'aisu-click-ring';
            cRing.style.cssText = `left:${e.clientX}px; top:${e.clientY}px;`;
            document.documentElement.appendChild(cRing);
            cRing.addEventListener('animationend', () => cRing.remove());
        });
    }

    /* ─── NAVBAR SCROLL CLASS ─── */
    // FIX: Only one declaration for navbar (was duplicated causing SyntaxError)
    const navbar = document.querySelector('.aisu-navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 60) {
                navbar.classList.add('scrolled');
                navbar.style.boxShadow = '0 4px 40px rgba(0,0,0,0.4)';
            } else {
                navbar.classList.remove('scrolled');
                navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.3)';
            }
        }, { passive: true });
    }

    /* ─── SCROLL TOP BUTTON ─── */
    // FIX: Only one declaration for scrollTopBtn (was duplicated causing SyntaxError)
    const scrollTopBtn = document.getElementById('scrollTop') || document.querySelector('.scroll-top');
    if (scrollTopBtn) {
        window.addEventListener('scroll', () => {
            const show = window.scrollY > 400;
            scrollTopBtn.style.opacity       = show ? '1' : '0';
            scrollTopBtn.style.pointerEvents = show ? 'auto' : 'none';
            scrollTopBtn.classList.toggle('visible', show);
        }, { passive: true });
        scrollTopBtn.addEventListener('click', e => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ─── REVEAL ON SCROLL (Staggered) ─── */
    const revealItems = document.querySelectorAll('.reveal');
    if (revealItems.length) {
        const obs = new IntersectionObserver((entries) => {
            entries.forEach(el => {
                if (el.isIntersecting) el.target.classList.add('visible');
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        revealItems.forEach(item => obs.observe(item));
    }

    /* ─── COUNTER ANIMATION ─── */
    function animateCounter(el) {
        const target   = parseInt(el.getAttribute('data-target'));
        const duration = 2000;
        const step     = target / (duration / 16);
        let current    = 0;
        const timer = setInterval(() => {
            current += step;
            if (current >= target) { current = target; clearInterval(timer); }
            el.textContent = Math.floor(current).toLocaleString('en-IN');
        }, 16);
    }
    const counters = document.querySelectorAll('[data-target]');
    if (counters.length) {
        const cObs = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                    entry.target.classList.add('counted');
                    animateCounter(entry.target);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(c => cObs.observe(c));
    }

    /* ─── HERO PARTICLES ─── */
    const hero = document.querySelector('.hero-section');
    if (hero) {
        for (let i = 0; i < 14; i++) {
            const p = document.createElement('div');
            p.className = 'hero-particle';
            p.style.cssText = `
                left:${Math.random() * 100}%;
                bottom:${Math.random() * 30}%;
                --dur:${5 + Math.random() * 6}s;
                --delay:${Math.random() * 5}s;
                --dx:${(Math.random() - 0.5) * 80}px;
                width:${2 + Math.random() * 4}px;
                height:${2 + Math.random() * 4}px;
                opacity:0;
            `;
            hero.appendChild(p);
        }
    }

    /* ─── MAGNETIC BUTTONS ─── */
    if (!isTouchDevice()) {
        document.querySelectorAll('.btn-hero-primary, .btn-primary-custom, .btn-nav-login').forEach(btn => {
            btn.addEventListener('mousemove', e => {
                const rect = btn.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width  / 2;
                const y = e.clientY - rect.top  - rect.height / 2;
                btn.style.transform = `translate(${x * 0.18}px, ${y * 0.18}px) translateY(-2px)`;
            });
            btn.addEventListener('mouseleave', () => { btn.style.transform = ''; });
        });
    }

    /* ─── CARD TILT EFFECT ─── */
    if (!isTouchDevice()) {
        document.querySelectorAll('.feature-card, .initiative-card, .team-card, .reg-detail-card').forEach(card => {
            card.addEventListener('mousemove', e => {
                const rect = card.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width  - 0.5;
                const y = (e.clientY - rect.top)  / rect.height - 0.5;
                card.style.transform = `perspective(800px) rotateX(${-y * 6}deg) rotateY(${x * 6}deg) translateY(-8px)`;
            });
            card.addEventListener('mouseleave', () => { card.style.transform = ''; });
        });
    }

    /* ─── TEAM FILTER ─── */
    const filterBtns = document.querySelectorAll('.filter-btn');
    if (filterBtns.length) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const level = btn.getAttribute('data-filter');
                document.querySelectorAll('.team-card-wrapper').forEach(card => {
                    const show = level === 'all' || card.getAttribute('data-level') === level;
                    card.style.transition = 'opacity 0.4s, transform 0.4s';
                    if (show) {
                        card.style.opacity   = '1';
                        card.style.transform = '';
                        card.style.display   = '';
                    } else {
                        card.style.opacity   = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => { card.style.display = 'none'; }, 400);
                    }
                });
            });
        });
    }



    /* ─── AUTHENTICATION NAVBAR UPDATE ─── */
    try {
        const storedUser = JSON.parse(localStorage.getItem('aisu_user') || 'null');
        const token = localStorage.getItem('aisu_token');
        
        if (storedUser && token) {
            const loginBtnContainer = document.querySelector('.btn-nav-login')?.parentElement;
            if (loginBtnContainer) {
                const adminRoles = ['national','vp','secretary','treasurer','state','district','mandal','institutional','member'];
                const isAdmin = adminRoles.includes(storedUser.role);
                const firstName = storedUser.name ? storedUser.name.split(' ')[0] : 'My Account';
                
                loginBtnContainer.innerHTML = `
                    <div class="nav-item dropdown">
                        <a href="#" class="btn-nav-login nav-link-custom dropdown-toggle" data-bs-toggle="dropdown" style="display:flex;align-items:center;gap:6px;background:var(--primary);color:#fff;padding:8px 16px;border-radius:20px;text-decoration:none;">
                            <i class="bi bi-person-circle"></i> ${firstName}
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm" style="border:none; border-radius:12px; min-width: 180px; margin-top: 10px;">
                            ${isAdmin ? '<a href="admin.html" class="dropdown-item py-2 fw-600" style="color:var(--primary);"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a><div class="dropdown-divider"></div>' : ''}
                            <a href="#" id="global-logout-btn" class="dropdown-item py-2 fw-600 text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                        </div>
                    </div>
                `;
                
                document.getElementById('global-logout-btn')?.addEventListener('click', function(e) {
                    e.preventDefault();
                    localStorage.removeItem('aisu_token');
                    localStorage.removeItem('aisu_user');
                    window.location.reload();
                });
            }
        }
    } catch (e) {
        console.warn('Navbar auth update error:', e);
    }

    /* ─── COMPLAINT FORM ─── */
    const complaintForm = document.getElementById('complaint-form');
    if (complaintForm) {
        complaintForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submitBtn = complaintForm.querySelector('button[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...'; }

            const fd = new FormData(complaintForm);
            try {
                if (typeof Complaints !== 'undefined') {
                    const result = await Complaints.submit(fd);
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = 'Submit Complaint'; }
                    if (result && result.success) {
                        const cmpId = result.data?.complaint_id || '';
                        const dispEl = document.getElementById('complaint-id-display');
                        if (dispEl) dispEl.textContent = cmpId;
                        document.getElementById('complaint-success').style.display = 'block';
                        complaintForm.style.display = 'none';
                    } else {
                        const msg = result?.message || 'Submission failed. Please ensure you are logged in.';
                        if (typeof showToast === 'function') showToast(msg, 'error');
                        else alert(msg);
                    }
                } else {
                    // API client not loaded — fallback
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = 'Submit Complaint'; }
                    alert('Please ensure you are logged in to submit a complaint.');
                }
            } catch (err) {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = 'Submit Complaint'; }
                if (typeof showToast === 'function') showToast('Network error. Is the backend running?', 'error');
                else alert('Network error. Is the backend running?');
            }
        });
    }

    /* ─── PRIMARY MEMBERSHIP FORM ─── */
    const primaryForm = document.getElementById('primary-membership-form');
    if (primaryForm) {
        primaryForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const successEl = document.getElementById('primary-success');
            if (successEl) {
                successEl.style.display = 'block';
                const container = document.getElementById('primary-membership-form-container');
                if (container) container.style.display = 'none';
                window.scrollTo({ top: successEl.offsetTop - 100, behavior: 'smooth' });
            }
        });
    }

    /* ─── STUDENT MEMBERSHIP FORM ─── */
    const studentForm = document.getElementById('student-membership-form');
    if (studentForm) {
        studentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const successEl = document.getElementById('student-success');
            if (successEl) {
                successEl.style.display = 'block';
                studentForm.style.display = 'none';
                window.scrollTo({ top: successEl.offsetTop - 100, behavior: 'smooth' });
            }
        });
    }

    /* ─── INTERNSHIP FORM ─── */
    const internshipForm = document.getElementById('internship-form');
    if (internshipForm) {
        internshipForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = internshipForm.querySelector('button[type="submit"]');
            const origHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

            const fd = new FormData(internshipForm);
            try {
                const fallbackBase = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') ? 'http://localhost:8000/api' : '/backend-php/api';
                const API_BASE = localStorage.getItem('aisu_api') || fallbackBase;
                const resp = await fetch(API_BASE + '/internship/apply', { method: 'POST', body: fd });
                const result = await resp.json();
                btn.disabled = false; btn.innerHTML = origHTML;
                if (result.success) {
                    const successEl = document.getElementById('internship-success');
                    if (successEl) { successEl.style.display = 'block'; internshipForm.closest('.form-section').style.display = 'none'; }
                } else {
                    alert(result.message || 'Submission failed. Please check all fields.');
                }
            } catch (err) {
                btn.disabled = false; btn.innerHTML = origHTML;
                alert('Network error. Is the backend running?');
            }
        });
    }

    /* ─── AFFILIATION FORM ─── */
    const affiliationForm = document.getElementById('affiliation-form');
    if (affiliationForm) {
        affiliationForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = affiliationForm.querySelector('button[type="submit"]');
            const origHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

            const fd = new FormData(affiliationForm);
            try {
                const fallbackBase = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') ? 'http://localhost:8000/api' : '/backend-php/api';
                const API_BASE = localStorage.getItem('aisu_api') || fallbackBase;
                const resp = await fetch(API_BASE + '/affiliation/apply', { method: 'POST', body: fd });
                const result = await resp.json();
                btn.disabled = false; btn.innerHTML = origHTML;
                if (result.success) {
                    const successEl = document.getElementById('affiliation-success');
                    if (successEl) { successEl.style.display = 'block'; affiliationForm.closest('.form-section').style.display = 'none'; }
                } else {
                    alert(result.message || 'Submission failed. Please check all fields.');
                }
            } catch (err) {
                btn.disabled = false; btn.innerHTML = origHTML;
                alert('Network error. Is the backend running?');
            }
        });
    }

    /* ─── NEWSLETTER FORM ─── */
    document.querySelectorAll('.newsletter-form').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const emailInput = form.querySelector('input[type="email"]');
            const email = emailInput?.value.trim() || '';
            const btn = form.querySelector('button[type="submit"]');
            if (!email || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
                if (typeof showToast === 'function') showToast('Please enter a valid email address.', 'error');
                else alert('Please enter a valid email address.');
                return;
            }

            const origHTML = btn ? btn.innerHTML : 'Subscribe';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Subscribing...';
            }

            const fallbackBase = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') ? 'http://localhost:8000/api' : '/backend-php/api';
            const apiBase = (typeof API_BASE !== 'undefined') ? API_BASE : fallbackBase;

            try {
                const resp = await fetch(apiBase + '/newsletter/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, source: window.location.pathname })
                });
                const result = await resp.json();
                if (result.success) {
                    if (btn) {
                        btn.innerHTML = '✓ Subscribed!';
                        btn.style.background = 'linear-gradient(135deg,#22c55e,#16a34a)';
                    }
                    if (typeof showToast === 'function') showToast(result.message || 'Subscribed successfully!', 'success');
                    form.reset();
                    setTimeout(() => {
                        if (btn) {
                            btn.innerHTML = origHTML;
                            btn.style.background = '';
                            btn.disabled = false;
                        }
                    }, 3000);
                } else {
                    if (typeof showToast === 'function') showToast(result.message || 'Subscription failed.', 'error');
                    else alert(result.message || 'Subscription failed.');
                    if (btn) { btn.disabled = false; btn.innerHTML = origHTML; }
                }
            } catch (err) {
                if (typeof showToast === 'function') showToast('Unable to save subscription. Is the backend running?', 'error');
                else alert('Unable to save subscription. Is the backend running?');
                if (btn) { btn.disabled = false; btn.innerHTML = origHTML; }
            }
        });
    });

    /* ─── QUIZ TIMER ─── */
    const quizTimer = document.getElementById('quiz-timer');
    if (quizTimer) {
        let time = parseInt(quizTimer.getAttribute('data-time') || '30') * 60;
        const interval = setInterval(() => {
            time--;
            const m = String(Math.floor(time / 60)).padStart(2, '0');
            const s = String(time % 60).padStart(2, '0');
            quizTimer.textContent = m + ':' + s;
            if (time <= 0) {
                clearInterval(interval);
                quizTimer.textContent = '00:00';
                alert('Time is up! Your quiz has been auto-submitted.');
            }
        }, 1000);
    }

    /* ─── SMOOTH SCROLL ─── */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href && href !== '#') {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    /* ─── TYPEWRITER EFFECT (hero title) ─── */
    const twEl = document.querySelector('.hero-title .typewriter');
    if (twEl) {
        const words = twEl.getAttribute('data-words') ? JSON.parse(twEl.getAttribute('data-words')) : null;
        if (words) {
            let wi = 0, ci = 0, deleting = false;
            function type() {
                const word = words[wi];
                twEl.textContent = deleting ? word.slice(0, ci--) : word.slice(0, ci++);
                if (!deleting && ci > word.length) { deleting = true; setTimeout(type, 1200); return; }
                if (deleting && ci < 0) { deleting = false; wi = (wi + 1) % words.length; }
                setTimeout(type, deleting ? 50 : 90);
            }
            type();
        }
    }

    /* ─── SAME ADDRESS CHECKBOX ─── */
    const sameAddrChk = document.getElementById('same-address-check');
    if (sameAddrChk) {
        sameAddrChk.addEventListener('change', function () {
            const curAddrBlock = document.getElementById('current-address-block');
            if (curAddrBlock) {
                curAddrBlock.style.display = this.checked ? 'none' : 'block';
            }
        });
    }

    /* ─── PREV MEMBER ORG CHECKBOX ─── */
    const prevOrgRadios = document.querySelectorAll('[name="prev-org"]');
    if (prevOrgRadios.length) {
        prevOrgRadios.forEach(r => {
            r.addEventListener('change', function () {
                const extra = document.getElementById('prev-org-name-block');
                if (extra) extra.style.display = this.value === 'yes' ? 'block' : 'none';
            });
        });
    }

    /* ─── LOAD REACH DATA FROM CSV ─── */
    async function loadReachData() {
        try {
            const response = await fetch('data/reach_data.csv?' + Date.now()); // Cache busting
            if (!response.ok) {
                console.warn('CSV file not found, using default values');
                return;
            }
            const csvText = await response.text();
            const lines = csvText.trim().split('\n');
            const data = {};
            
            lines.forEach(line => {
                const [key, value] = line.split(',');
                if (key && value) {
                    data[key.trim()] = parseInt(value.trim(), 10);
                }
            });
            
            // Update data-target attributes based on labels
            document.querySelectorAll('[data-target]').forEach(el => {
                const labelEl = el.parentElement.querySelector('.stat-label') || el.nextElementSibling;
                if (labelEl && labelEl.textContent) {
                    const label = labelEl.textContent.replace(/\s+/g, ' ').trim();
                    if (data[label] !== undefined) {
                        const newTarget = data[label];
                        const currentTarget = parseInt(el.getAttribute('data-target'), 10);
                        if (newTarget !== currentTarget) {
                            el.setAttribute('data-target', newTarget);
                            // Animate from current displayed value to new value
                            const currentValue = parseInt(el.textContent.replace(/[^\d]/g, ''), 10) || 0;
                            animateCounter(el, currentValue, newTarget);
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error loading CSV:', error);
        }
    }

    // Separate animation function for smooth updates
    function animateCounter(el, from, to) {
        const suffix = el.dataset.suffix || '';
        const steps = 30;
        const duration = 1500;
        const increment = (to - from) / steps;
        let current = from;
        let step = 0;
        
        function fmt(n) {
            if (to >= 1000) {
                var k = Math.floor(n / 1000);
                return (k > 0 ? k + 'K+' : Math.floor(n).toString());
            }
            return Math.floor(n).toString() + suffix;
        }
        
        const timer = setInterval(() => {
            step++;
            current = Math.min(from + increment * step, to);
            el.textContent = fmt(current);
            if (step >= steps) {
                clearInterval(timer);
                el.textContent = fmt(to);
            }
        }, Math.floor(duration / steps));
    }

    /* ─── ANIMATED STAT COUNTER ─── */
    var statEls = document.querySelectorAll('[data-target]');

    function runCounter(el) {
        var target    = parseInt(el.getAttribute('data-target'), 10);
        var suffix    = el.dataset.suffix || '';
        var steps     = 60;
        var duration  = 1800;
        var increment = target / steps;
        var current   = 0;
        var step      = 0;
        function fmt(n) {
            if (target >= 1000) {
                var k = Math.floor(n / 1000);
                return (k > 0 ? k + 'K+' : Math.floor(n).toString());
            }
            return Math.floor(n).toString() + suffix;
        }
        el.textContent = fmt(0);
        var timer = setInterval(function () {
            step++;
            current = Math.min(increment * step, target);
            el.textContent = fmt(current);
            if (step >= steps) {
                clearInterval(timer);
                el.textContent = fmt(target);
            }
        }, Math.floor(duration / steps));
    }

    if (statEls.length) {
        // Load data from CSV first, then set up periodic updates
        loadReachData().then(() => {
            // Set up periodic data reloading (every 5 minutes)
            setInterval(loadReachData, 5 * 60 * 1000);
            
            if ('IntersectionObserver' in window) {
                var statObs = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            runCounter(entry.target);
                            statObs.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.4 });
                statEls.forEach(function (el) { statObs.observe(el); });
            } else {
                statEls.forEach(function (el) { runCounter(el); });
            }
        });
    }

}); // end DOMContentLoaded
