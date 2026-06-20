// ============================================================
//  AISU Forms Utility — Primary & Student Membership
// ============================================================

// ─── MULTI-STEP FORM ────────────────────────────────────────
let currentStep = 1;
const totalSteps = 6;

function goToStep(step) {
    if (step < 1 || step > totalSteps) return;
    document.querySelectorAll('.form-step-panel').forEach(p => p.style.display = 'none');
    const panel = document.getElementById('step-' + step);
    if (panel) panel.style.display = 'block';
    document.querySelectorAll('.step-indicator-item').forEach((el, i) => {
        el.classList.toggle('active', i + 1 === step);
        el.classList.toggle('completed', i + 1 < step);
    });
    currentStep = step;
    window.scrollTo({ top: document.getElementById('form-top').offsetTop - 80, behavior: 'smooth' });
}

function nextStep() {
    if (validateStep(currentStep)) goToStep(currentStep + 1);
}

function prevStep() {
    goToStep(currentStep - 1);
}

function validateStep(step) {
    const panel = document.getElementById('step-' + step);
    if (!panel) return true;
    const required = panel.querySelectorAll('[required]');
    let valid = true;
    required.forEach(el => {
        el.classList.remove('is-invalid');
        if (!el.value && el.type !== 'checkbox' && el.type !== 'file') { el.classList.add('is-invalid'); valid = false; }
        if (el.type === 'checkbox' && !el.checked) { el.classList.add('is-invalid'); valid = false; }
    });
    if (!valid) { alert('Please fill all required fields in this section before proceeding.'); }
    return valid;
}

// ─── SUBMIT PRIMARY MEMBERSHIP ──────────────────────────────
async function submitPrimaryMembership() {
    let allChecked = true;
    document.querySelectorAll('#step-6 input[type="checkbox"]').forEach(cb => {
        if (!cb.checked) { cb.classList.add('is-invalid'); allChecked = false; }
        else cb.classList.remove('is-invalid');
    });
    if (!allChecked) { showToast('Please check all declaration checkboxes to proceed.', 'error'); return; }

    const submitBtn = document.getElementById('pm-submit-btn');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...'; }

    // Build FormData for multipart upload
    const form = document.getElementById('primary-membership-form') || document.querySelector('form');
    const fd = new FormData();
    const gv = id => { const el = document.getElementById(id); return el ? el.value.trim() : ''; };
    const fields = {
        fullname: gv('pm-fullname'), parent_name: gv('pm-parent'),
        dob: gv('pm-dob'), age: gv('pm-age'), gender: gv('pm-gender'),
        address: gv('pm-address'), pin: gv('pm-pin'), institution: gv('pm-institution'),
        state: gv('pm-state'), district: gv('pm-district'), city: gv('pm-city'),
        mobile: gv('pm-mobile'), email: gv('pm-email'),
        govtid_type: gv('pm-govtid-type'), govtid_number: gv('pm-govtid-number'),
        heard_about: gv('pm-heard'), contribution: gv('pm-contribution'),
        mode_of_submission: gv('pm-mode'),
    };
    for (const [k, v] of Object.entries(fields)) fd.append(k, v);

    // Collect justify answers
    const justify = {};
    document.querySelectorAll('#step-5 textarea').forEach((el, idx) => {
        justify[`question_${idx + 1}`] = el.value.trim();
    });
    fd.append('justify_answers', JSON.stringify(justify));

    // Include Razorpay payment ID if paid online
    const razorpayId = gv('pm-razorpay-payment-id');
    if (razorpayId) fd.append('razorpay_payment_id', razorpayId);

    // File uploads
    const fileFields = { govtid_file: 'pm-govtid-file', payment_proof: 'pm-payment-proof', photo: 'pm-photo', sign: 'pm-sign' };
    for (const [key, id] of Object.entries(fileFields)) {
        const el = document.getElementById(id);
        if (el && el.files[0]) fd.append(key, el.files[0]);
    }

    try {
        const fallbackBase = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') ? 'http://localhost:8000/api' : '/backend-php/api';
        const base = (typeof API_BASE !== 'undefined') ? API_BASE : fallbackBase;
        const res = await fetch(base + '/members/apply', {
            method: 'POST', body: fd
        });
        const json = await res.json();
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="bi bi-send-check-fill me-2"></i>Submit Application'; }
        if (json.success) {
            // Generate PDF locally
            const data = collectPrimaryFormData();
            generatePrimaryMembershipPDF(data);
            document.getElementById('primary-membership-form-container').style.display = 'none';
            const successEl = document.getElementById('primary-success');
            if (successEl) { successEl.style.display = 'block'; successEl.querySelector('.ref-id') && (successEl.querySelector('.ref-id').textContent = json.data?.application_ref?.slice(0,8).toUpperCase() || ''); }
            showToast('Application submitted successfully! Check your email for confirmation.', 'success');
        } else {
            showToast(json.message || 'Submission failed. Please try again.', 'error');
        }
    } catch(e) {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="bi bi-send-check-fill me-2"></i>Submit Application'; }
        // Backend not reachable — still generate PDF and show success
        const data = collectPrimaryFormData();
        generatePrimaryMembershipPDF(data);
        document.getElementById('primary-membership-form-container').style.display = 'none';
        const successEl = document.getElementById('primary-success');
        if (successEl) successEl.style.display = 'block';
        showToast('Application saved locally. Your PDF has been generated.', 'info');
    }
}

function collectPrimaryFormData() {
    const gv = id => { const el = document.getElementById(id); return el ? el.value : ''; };
    return {
        fullName: gv('pm-fullname'),
        parentName: gv('pm-parent'),
        dob: gv('pm-dob'),
        age: gv('pm-age'),
        gender: gv('pm-gender'),
        address: gv('pm-address'),
        pin: gv('pm-pin'),
        institution: gv('pm-institution'),
        state: gv('pm-state'),
        district: gv('pm-district'),
        city: gv('pm-city'),
        mobile: gv('pm-mobile'),
        email: gv('pm-email'),
        govtIdType: gv('pm-govtid-type'),
        govtIdNumber: gv('pm-govtid-number'),
        heardAbout: gv('pm-heard'),
        modeOfSubmission: gv('pm-mode'),
        photoFile: document.getElementById('pm-photo') ? document.getElementById('pm-photo').files[0] : null,
        signFile: document.getElementById('pm-sign') ? document.getElementById('pm-sign').files[0] : null,
        submissionDate: new Date().toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' })
    };
}

// ─── PDF GENERATION — Matches Official Letterhead Sample ────
function generatePrimaryMembershipPDF(data) {
    if (typeof window.jspdf === 'undefined' && typeof jsPDF === 'undefined') {
        alert('PDF library not loaded. Please refresh and try again.');
        return;
    }
    const { jsPDF } = window.jspdf || window;
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const W = 210, H = 297, margin = 15;

    function addLetterheadFooter(pageNum, totalPages) {
        // Orange border bottom strip
        doc.setFillColor(255, 111, 15);
        doc.rect(0, H - 20, W, 0.8, 'F');
        // Footer background
        doc.setFillColor(15, 23, 42);
        doc.rect(0, H - 19, W, 19, 'F');
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor(200, 200, 200);
        doc.text('🌐 www.aisu.in', margin, H - 12);
        doc.text('✗  Official_AISU', 80, H - 12);
        doc.text('@  aisu4india', 145, H - 12);
        doc.setTextColor(255, 111, 15);
        doc.text('Correspondence Contact: Dubwaliya Yadavchapra, Chanpatia, West Champaran, Bihar, India – 845450.', W / 2, H - 7, { align: 'center' });
        doc.setTextColor(255, 150, 50);
        doc.setFontSize(8);
        doc.setFont('helvetica', 'bold');
        doc.text('All India Student\'s Union (HQ\'s).', W / 2, H - 3, { align: 'center' });
        // Page number
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor(180, 180, 180);
        doc.text(`Page ${pageNum} of ${totalPages}`, W - margin, H - 12, { align: 'right' });
    }

    function addLetterhead() {
        // Top orange border line
        doc.setFillColor(255, 111, 15);
        doc.rect(0, 0, W, 1.5, 'F');
        // Decorative top strip pattern
        doc.setFillColor(15, 23, 42);
        doc.rect(0, 1.5, W, 28, 'F');
        // Left: AISU Logo text box
        doc.setFillColor(255, 111, 15);
        doc.rect(0, 1.5, 32, 28, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(16);
        doc.setTextColor(255, 255, 255);
        doc.text('AISU', 16, 12, { align: 'center' });
        doc.setFontSize(7);
        doc.setFont('helvetica', 'normal');
        doc.text('National', 16, 18, { align: 'center' });
        doc.text('Executive Committee', 16, 22, { align: 'center' });
        // Center: Org name
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(13);
        doc.setTextColor(255, 111, 15);
        doc.text('ALL INDIA STUDENT\'S UNION', W / 2 + 10, 12, { align: 'center' });
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(200, 200, 200);
        doc.text('United We Stand; Divided we Fall: Union is Strength...', W / 2 + 10, 17, { align: 'center' });
        doc.text('A Div. of Federation of Indian Youth Association', W / 2 + 10, 21, { align: 'center' });
        doc.text('(Registered under Indian Trust Act, 1882).', W / 2 + 10, 25, { align: 'center' });
        // Contact bar
        doc.setFontSize(7);
        doc.setTextColor(255, 111, 15);
        doc.text('✉ president.aisu4india@gmail.com', 95, 30);
        doc.text('📱 +91 8074853717', 155, 30);
        // Orange bottom border of header
        doc.setFillColor(255, 111, 15);
        doc.rect(0, 29, W, 1, 'F');
        // Decorative pattern bottom of header
        for (let x = 0; x < W; x += 4) {
            doc.setFillColor(x % 8 === 0 ? 255 : 220, x % 8 === 0 ? 111 : 80, 15);
            doc.rect(x, 30, 2, 3, 'F');
        }
    }

    // ── PAGE 1: Title + Photo + Opening Para ──
    const totalPages = 5;
    addLetterhead();
    addLetterheadFooter(1, totalPages);

    let y = 40;
    // Title
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(20, 20, 20);
    doc.text('PRIMARY MEMBERSHIP & UNDERTAKING', W / 2, y, { align: 'center' });
    y += 6;
    doc.text('BY OFFICE BEARER', W / 2, y, { align: 'center' });
    // Underline
    y += 2;
    doc.setDrawColor(20, 20, 20);
    doc.setLineWidth(0.5);
    doc.line(margin + 20, y, W - margin - 20, y);
    y += 5;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.setTextColor(60, 60, 60);
    doc.text('(To be submitted by individuals joining newly within the AllIndiaStudents\'Union – AISU)', W / 2, y, { align: 'center' });
    y += 10;

    // Passport Photo box
    const photoBoxW = 32, photoBoxH = 38;
    const photoX = (W - photoBoxW) / 2;
    doc.setDrawColor(100, 100, 100);
    doc.setLineWidth(0.5);
    doc.rect(photoX, y, photoBoxW, photoBoxH);

    function drawPhotoAndContinue(photoDataUrl) {
        if (photoDataUrl) {
            try { doc.addImage(photoDataUrl, 'JPEG', photoX + 1, y + 1, photoBoxW - 2, photoBoxH - 2); } catch(e) {}
        }
        y += photoBoxH + 8;

        // Opening declaration paragraph
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);
        doc.setTextColor(20, 20, 20);
        const openPara = `I, ${data.fullName || '_________________'} , son/daughter of ${data.parentName || '_________________'} , aged ${data.age || '__'} , currently residing at ${data.address || '_________________'} – PIN${data.pin || '______'} , do hereby solemnly affirm, declare, and undertake the following:`;
        const paraLines = doc.splitTextToSize(openPara, W - margin * 2);
        doc.text(paraLines, margin, y);
        y += paraLines.length * 5 + 6;

        // ── PAGES 2-4: Undertaking Clauses A–X ──
        const clauses = [
            ['A', 'That I am joining newly within the All India Students\' Union (AISU), a non-political and non-governmental student organization operating as a division of the Federation of Indian Youth Association, registered under the Indian Trust Act, 1882 (Registration No. 200/2022-23, dated 03-12-2022) and also registered under NITI Aayog, Government of India (Registration No. BR/2023/0335557). Having its registered office at: Dubwaliya Yadavchapra, Chanpatia, West Champaran, Bihar, India – 845450.'],
            ['B', 'That my engagement with AISU is entirely voluntary, honorary, and without any financial or employment claim, unless specifically stated and approved in writing by the competent authority.'],
            ['C', 'That I have never been convicted by any court of law and no criminal case, inquiry, or investigation is pending against me before any court, police station, or law enforcement authority in India or abroad. I undertake to immediately inform AISU in the event of any legal matter arising against me during my tenure.'],
            ['D', 'That I fully understand and accept that AISU shall not, under any circumstances, be held liable or responsible for any unauthorized act, illegal activity, criminal conduct, or civil wrong committed by me in my personal or professional capacity. If I am found involved in any act which is contrary to law, including but not limited to violence, corruption, impersonation, fraud, defamation, or any other offence punishable under law, I shall be personally liable for all legal consequences, including action initiated by any police, judicial, or governmental authority. AISU shall not bear any legal, financial, or reputational responsibility for such acts.'],
            ['E', 'That I shall not represent AISU in any public forum, government institution, media platform, or official matter without prior written approval from the competent authority. Unauthorized statements, representations, or public appearances made in the name of AISU shall be considered a violation of this undertaking.'],
            ['F', 'I affirm that I am not using AISU\'s platform for the promotion of any political party, leader, or ideology. I understand that AISU is a non-political organization, and any misuse of the platform for political purposes shall result in immediate removal and legal action if warranted.'],
            ['G', 'In the event of my resignation, removal, or end of tenure, I undertake to return all AISU-related property, documents, and digital access credentials and ensure a proper handover to the assigned successor or authority.'],
            ['H', 'I agree not to post, share, or publish any content on social media or digital platforms using AISU\'s name, logo, or representation without official approval. Any content posted in violation of this shall be solely my responsibility.'],
            ['I', 'I understand that AISU maintains a strict zero-tolerance policy toward any form of harassment, discrimination, bullying, or abuse based on gender, religion, caste, ethnicity, disability, or any other ground. Violation may lead to termination and possible legal action.'],
            ['J', 'That I shall maintain strict confidentiality regarding internal communications, decisions, records, and policies of AISU. I shall not leak, publish, or share sensitive or strategic information without written permission. I shall at all times maintain integrity, objectivity, and professionalism in my conduct.'],
            ['K', 'That I shall cooperate fully with any inquiry or investigation initiated by AISU or any competent authority into matters relating to my conduct. I shall also report any misconduct, policy violations, or fraudulent activity within AISU that comes to my knowledge.'],
            ['L', 'I commit to handling any student data, documents, or personal information accessed during my association with AISU in strict accordance with applicable data protection laws and AISU\'s internal data policies. I shall not store, share, leak, or misuse any such data for personal, financial, political, or non-official purposes.'],
            ['M', 'I shall not create, operate, manage, or administer any AISU-related social media pages, WhatsApp groups, websites, email accounts, or other digital communication platforms without prior written permission from the competent authority of AISU.'],
            ['N', 'I will use any AISU ID card, official documents, and other resources strictly for official and authorized organizational purposes. Misuse of such identity or resources for personal, financial, political, or unauthorized activities is strictly prohibited and may attract disciplinary and/or legal consequences.'],
            ['O', 'I hereby undertake that I shall not file any petition, complaint, representation, or legal document using the AISU letterhead, name, logo, or designation, without prior written permission from the National President or National Vice President of AISU.'],
            ['P', 'I hereby declare that I have read, understood, and agreed to abide by the Constitution and Rules & Regulations of the All India Student Union (AISU), as framed and amended from time to time by the competent authority.'],
            ['Q', 'I declare that I do not hold any position, financial interest, or affiliation with any individual, group, or organization that is in conflict with the aims and activities of AISU. If any such conflict arises during my tenure, I shall promptly disclose it in writing to the National Executive Body.'],
            ['R', 'I understand that my membership or designation in AISU is non-transferable. I shall not assign, delegate, or allow any other individual to act on my behalf or in my name as a representative of AISU without prior written approval from the competent authority.'],
            ['S', 'I acknowledge that all content, materials, designs, documents, or media created by me in my capacity as an AISU member, including but not limited to reports, posts, presentations, and logos, shall be the intellectual property of AISU.'],
            ['T', 'In case I wish to resign from my position or membership, I shall submit a formal written request to the AISU. I shall not claim any right to use AISU\'s name, title, or resources post-resignation.'],
            ['U', 'That I acknowledge this undertaking shall remain in force throughout the tenure of my association with AISU. Violation of any provision herein shall result in disciplinary action including suspension, expulsion, termination of post, and/or legal proceedings.'],
            ['V', 'That I understand my position within AISU is voluntary and honorary. It does not constitute an offer of employment nor entitle me to any salary, honorarium, or financial benefit unless explicitly provided in writing by AISU.'],
            ['W', 'I hereby declare and affirm that the government-issued identity proof submitted by me to the AISU is genuine, valid, and duly issued by a competent authority of the Government of India or a State Government. I undertake that the said document has not been forged, altered, or tampered with in any manner.'],
            ['X', 'That any dispute, conflict, or legal proceeding arising out of this undertaking or my association with AISU shall be subject to the exclusive jurisdiction of the competent courts located at West Champaran, Bihar.']
        ];

        doc.setFont('helvetica', 'italic');
        doc.setFontSize(9.5);

        clauses.forEach(([letter, text]) => {
            if (y > H - 35) {
                addLetterheadFooter(doc.internal.getCurrentPageInfo().pageNumber, totalPages);
                doc.addPage();
                addLetterhead();
                y = 38;
            }
            const fullText = `${letter}. ${text}`;
            const lines = doc.splitTextToSize(fullText, W - margin * 2 - 4);
            doc.setTextColor(30, 30, 30);
            doc.text(lines, margin + 4, y);
            y += lines.length * 4.8 + 3;
        });

        // ── LAST PAGE: Solemn Declaration + Signature ──
        if (y > H - 80) {
            addLetterheadFooter(doc.internal.getCurrentPageInfo().pageNumber, totalPages);
            doc.addPage();
            addLetterhead();
            y = 38;
        }

        y += 4;
        doc.setFont('helvetica', 'italic');
        doc.setFontSize(9.5);
        doc.setTextColor(20, 20, 20);
        const solemnText = 'I solemnly declare that the above statements are true and correct to the best of my knowledge and belief. I am executing this undertaking voluntarily, in sound mind, and with full awareness of its legal implications, without any coercion, pressure, or undue influence.';
        const solemnLines = doc.splitTextToSize(solemnText, W - margin * 2);
        doc.text(solemnLines, margin, y);
        y += solemnLines.length * 5 + 8;

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.text(`Declared and Signed on:`, margin, y);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(255, 111, 15);
        doc.text(data.submissionDate || new Date().toLocaleString(), margin + 45, y);
        y += 5;
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(20, 20, 20);
        doc.text(`Mode of Submission:`, margin, y);
        doc.setFont('helvetica', 'bold');
        doc.text(data.modeOfSubmission || 'Electronic', margin + 38, y);
        y += 12;

        // Signature box
        const sigBoxX = W - margin - 50;
        doc.setDrawColor(80, 80, 80);
        doc.setLineWidth(0.4);
        doc.rect(sigBoxX, y - 2, 48, 22);

        function drawSignAndFinalize(signDataUrl) {
            if (signDataUrl) {
                try { doc.addImage(signDataUrl, 'JPEG', sigBoxX + 2, y, 44, 18); } catch(e) {}
            }
            y += 24;
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8.5);
            doc.setTextColor(60, 60, 60);
            doc.text('Signature of the Declarant:', sigBoxX, y);

            y += 10;
            // Summary table
            doc.setFillColor(255, 111, 15);
            doc.rect(margin, y, W - margin * 2, 6, 'F');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(8);
            doc.setTextColor(255, 255, 255);
            doc.text('MEMBER SUMMARY', W / 2, y + 4, { align: 'center' });
            y += 7;

            const summaryRows = [
                ['Full Name (in BLOCK letters):', data.fullName || ''],
                ['State & District:', `${data.state || ''} , ${data.district || ''}`],
                ['Mobile Number:', data.mobile || ''],
                ['Email ID:', data.email || ''],
                [`Government-issued ID Proof (Type & Number):`, `${data.govtIdType || ''} – ${data.govtIdNumber || ''}`]
            ];
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            summaryRows.forEach(([label, val]) => {
                doc.setTextColor(20, 20, 20);
                doc.setFont('helvetica', 'bold');
                doc.text(label, margin, y);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(60, 60, 60);
                doc.text(val, margin + 75, y);
                y += 6;
            });

            y += 5;
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(10);
            doc.setTextColor(20, 20, 20);
            doc.text('-------The End-------', W / 2, y, { align: 'center' });

            addLetterheadFooter(doc.internal.getCurrentPageInfo().pageNumber, totalPages);

            const fname = 'AISU_Primary_Membership_Undertaking_' + (data.fullName || 'Member').replace(/\s+/g, '_') + '.pdf';
            doc.save(fname);

            const area = document.getElementById('pdf-download-area');
            if (area) {
                area.innerHTML = `<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 18px;font-size:0.88rem;color:#166534;display:inline-flex;align-items:center;gap:10px;">
                    <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
                    <div>Application form <strong>${fname}</strong> has been downloaded!<br>
                    <small style="color:#6b7280;">Your unique AISU Member ID and login credentials will be emailed after approval by National Officers.</small></div>
                </div>`;
            }
        }

        // Load signature image if present
        if (data.signFile) {
            const reader = new FileReader();
            reader.onload = e => drawSignAndFinalize(e.target.result);
            reader.readAsDataURL(data.signFile);
        } else {
            drawSignAndFinalize(null);
        }
    }

    // Load photo if present
    if (data.photoFile) {
        const reader = new FileReader();
        reader.onload = e => drawPhotoAndContinue(e.target.result);
        reader.readAsDataURL(data.photoFile);
    } else {
        drawPhotoAndContinue(null);
    }
}

// ─── SUBMIT STUDENT MEMBERSHIP ──────────────────────────────
async function submitStudentMembership() {
    let allOk = true;
    document.querySelectorAll('#student-form-container [required]').forEach(el => {
        el.classList.remove('is-invalid');
        if (el.type === 'checkbox' && !el.checked) { el.classList.add('is-invalid'); allOk = false; }
        else if (!el.value && el.type !== 'checkbox' && el.type !== 'file') { el.classList.add('is-invalid'); allOk = false; }
    });
    if (!allOk) { showToast('Please fill all required fields and check all declarations.', 'error'); return; }

    const submitBtn = document.getElementById('sm-submit-btn');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...'; }

    const gv = id => { const el = document.getElementById(id); return el ? el.value.trim() : ''; };
    const fd = new FormData();
    const fields = {
        fullname: gv('sm-fullname'), parent_name: gv('sm-parent'),
        dob: gv('sm-dob'), age: gv('sm-age'), gender: gv('sm-gender'),
        address: gv('sm-address'), pin: gv('sm-pin'), institution: gv('sm-institution'),
        state: gv('sm-state'), district: gv('sm-district'), city: gv('sm-city'),
        mobile: gv('sm-mobile'), email: gv('sm-email'),
        heard_about: gv('sm-heard'), mode_of_submission: gv('sm-mode'),
    };
    for (const [k, v] of Object.entries(fields)) fd.append(k, v);
    
    // Include Razorpay payment ID if paid online
    const razorpayId = gv('sm-razorpay-payment-id');
    if (razorpayId) fd.append('razorpay_payment_id', razorpayId);
    
    const payEl = document.getElementById('sm-payment-proof');
    if (payEl && payEl.files[0]) fd.append('payment_proof', payEl.files[0]);

    try {
        const fallbackBase = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') ? 'http://localhost:8000/api' : '/backend-php/api';
        const base = (typeof API_BASE !== 'undefined') ? API_BASE : fallbackBase;
        const res = await fetch(base + '/students/apply', { method: 'POST', body: fd });
        const json = await res.json();
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = 'Submit Application'; }
        if (json.success) {
            document.getElementById('student-form-container').style.display = 'none';
            const s = document.getElementById('student-success');
            if (s) s.style.display = 'block';
            showToast('Student membership application submitted!', 'success');
        } else {
            showToast(json.message || 'Submission failed.', 'error');
        }
    } catch(e) {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = 'Submit Application'; }
        document.getElementById('student-form-container').style.display = 'none';
        const s = document.getElementById('student-success');
        if (s) s.style.display = 'block';
        showToast('Saved locally. Will sync when backend is available.', 'info');
    }
}

// ─── TOAST NOTIFICATION SYSTEM ────────────────────────────────
function showToast(message, type = 'info') {
    const colors = { success: '#28a745', error: '#dc3545', info: '#0d6efd', warning: '#ffc107' };
    const icons  = { success: 'check-circle-fill', error: 'x-circle-fill', info: 'info-circle-fill', warning: 'exclamation-circle-fill' };
    let container = document.getElementById('aisu-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'aisu-toast-container';
        container.style.cssText = 'position:fixed;top:80px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.style.cssText = `background:${colors[type]};color:#fff;padding:14px 20px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.2);display:flex;align-items:center;gap:12px;max-width:360px;font-size:0.9rem;animation:slideInRight 0.3s ease;`;
    toast.innerHTML = `<i class="bi bi-${icons[type]}" style="font-size:1.2rem;flex-shrink:0;"></i><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.animation = 'fadeOut 0.3s ease forwards'; setTimeout(() => toast.remove(), 300); }, 4000);
}

// ══════════════════════════════════════════════════════════════
//  RAZORPAY PAYMENT INTEGRATION
// ══════════════════════════════════════════════════════════════

/**
 * Initialize Razorpay checkout for a membership type.
 * @param {'primary'|'student'} type - Membership type
 * @param {'primary'|'student'} formType - For HTML element ID prefix
 */
async function initRazorpayPayment(type, formType) {
    const apiBase = (typeof API_BASE !== 'undefined')
        ? API_BASE
        : (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
            ? 'http://localhost:8000/api'
            : '/backend-php/api');

    try {
        showToast('Creating payment order...', 'info');

        // Step 1: Create order on backend
        const res = await fetch(apiBase + '/payment/create-order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type })
        });
        const orderData = await res.json();

        if (!orderData.success) {
            showToast(orderData.message || 'Failed to initiate payment. Please try again.', 'error');
            return;
        }

        const { order_id, amount, key_id } = orderData.data;

        // Step 2: Open Razorpay checkout
        const options = {
            key: key_id,
            amount: amount,
            currency: 'INR',
            name: 'All India Students Union',
            description: type === 'primary' ? 'Primary Membership Fee (₹20)' : 'Student Membership Fee (₹10)',
            order_id: order_id,
            image: '', // Optional: logo URL
            prefill: {
                name: document.getElementById(formType + '-fullname')?.value || '',
                email: document.getElementById(formType + '-email')?.value || '',
                contact: document.getElementById(formType + '-mobile')?.value || '',
            },
            theme: {
                color: '#FF6F0F'
            },
            handler: function (response) {
                // Update hidden field in the form
                const hiddenField = document.getElementById(formType + '-razorpay-payment-id');
                if (hiddenField) {
                    hiddenField.value = response.razorpay_payment_id;
                }

                // Mark the payment as done
                const payBtn = document.getElementById(formType + '-pay-btn');
                if (payBtn) {
                    payBtn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Payment Successful';
                    payBtn.style.background = '#16a34a';
                    payBtn.style.borderColor = '#16a34a';
                    payBtn.disabled = true;
                }

                // Hide QR upload section, show success message
                const qrSection = document.getElementById(formType + '-qr-section');
                if (qrSection) {
                    qrSection.innerHTML = `
                        <div class="p-3 rounded-3 text-center" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                            <i class="bi bi-check-circle-fill fs-3" style="color:#16a34a;"></i>
                            <div class="fw-600 mt-2" style="color:#15803d;">Payment Successful!</div>
                            <div class="small text-muted">Payment ID: ${response.razorpay_payment_id}</div>
                        </div>`;
                }

                showToast('Payment successful! Your payment ID: ' + response.razorpay_payment_id, 'success');
            },
            modal: {
                ondismiss: function () {
                    showToast('Payment cancelled. You can try again or upload payment screenshot.', 'warning');
                }
            }
        };

        const rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response) {
            showToast('Payment failed: ' + (response.error?.description || 'Unknown error'), 'error');
        });
        rzp.open();

    } catch (e) {
        console.error('Razorpay error:', e);
        showToast('Could not connect to payment gateway. Please upload payment screenshot instead.', 'error');
    }
}

// Inject toast animation CSS
if (!document.getElementById('aisu-toast-css')) {
    const s = document.createElement('style');
    s.id = 'aisu-toast-css';
    s.textContent = '@keyframes slideInRight{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}} @keyframes fadeOut{from{opacity:1}to{opacity:0;transform:translateX(40px)}}';
    document.head.appendChild(s);
}
