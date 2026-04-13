document.addEventListener('DOMContentLoaded', () => {
    const form        = document.getElementById('verify-form');
    const modal       = document.getElementById('verification-modal');
    const downloadBtn = document.getElementById('modal-download-btn');
    const verifyBtn   = document.getElementById('verify-btn');

    let verifiedStudentId = null;

    function formatDate(dateStr) {
        return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-GB', {
            day: 'numeric', month: 'long', year: 'numeric'
        });
    }

    const openVerifyModal = () => {
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        if (downloadBtn) downloadBtn.focus();
    };

    window.closeVerifyModal = () => {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        if (verifyBtn) verifyBtn.focus();
    };

    // Close modal on backdrop click
    modal && modal.addEventListener('click', (e) => {
        if (e.target === modal) closeVerifyModal();
    });

    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            closeVerifyModal();
        }
    });

    function populateModal(result) {
        const s         = result.student;
        const isExpired = s.is_expired;

        // Icon and title
        const icon = document.getElementById('modal-icon');
        if (icon) icon.dataset.icon = isExpired ? 'mdi:alert-circle' : 'mdi:check-circle';

        const title = document.getElementById('modal-title');
        if (title) {
            title.textContent  = isExpired ? 'ID Card Expired' : 'Verification Successful!';
            title.style.color  = isExpired ? '#c62828' : '';
        }

        // Expiry badge
        const badge = document.getElementById('modal-expiry-badge');
        if (badge) {
            badge.textContent = isExpired
                ? `Expired: ${formatDate(s.expiry_date)}`
                : `Valid until: ${formatDate(s.expiry_date)}`;
            badge.className = `expiry-badge ${isExpired ? 'expired' : 'valid'}`;
        }

        // Student details grid
        const details = document.getElementById('modal-student-details');
        if (details) {
            details.innerHTML = `
                <div class="detail-row"><span>Name</span><span>${s.full_name}</span></div>
                <div class="detail-row"><span>Student ID</span><span>${s.student_id}</span></div>
                <div class="detail-row"><span>Course</span><span>${s.course}</span></div>
                <div class="detail-row"><span>Semester</span><span>${s.semester_code}</span></div>
                <div class="detail-row"><span>Batch</span><span>${s.batch_code}</span></div>
            `;
        }

        // Subtext
        const subtext = document.getElementById('modal-subtext');
        if (subtext) {
            subtext.textContent = isExpired
                ? 'This ID card has expired. Please contact NIIT to renew.'
                : 'We found a valid student record matching these details.';
        }

        // Disable download for expired cards
        if (downloadBtn) {
            downloadBtn.disabled = isExpired;
            downloadBtn.title    = isExpired ? 'Cannot download — ID card is expired.' : '';
        }
    }

    // ── Verify form submit ───────────────────────────────────────
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!form.checkValidity()) {
                form.reportValidity();
                showToast('Please fill out all required fields.', 'error');
                return;
            }

            showLoader();

            try {
                const formData  = new FormData(form);
                const response  = await fetch('/backend/api/verify_id.php', { method: 'POST', body: formData });
                const result    = await response.json();

                if (!response.ok || !result.success) {
                    // Still show modal for expired cards (success=true but is_expired=true)
                    if (result.student) {
                        verifiedStudentId = result.student_id;
                        populateModal(result);
                        openVerifyModal();
                    } else {
                        throw new Error(result.message || 'Verification failed.');
                    }
                    return;
                }

                verifiedStudentId = result.student_id;
                populateModal(result);
                showToast('Student details verified!', 'success');
                openVerifyModal();

            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                hideLoader();
            }
        });
    }

    // ── PDF Download ─────────────────────────────────────────────
    if (downloadBtn) {
        downloadBtn.addEventListener('click', async () => {
            if (!verifiedStudentId) {
                showToast('Verification session expired. Please re-verify.', 'error');
                closeVerifyModal();
                return;
            }

            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';

            closeVerifyModal();
            showLoader();
            showToast('Generating ID Card PDF...', 'info');

            try {
                const downloadData = new FormData();
                downloadData.append('student_id', verifiedStudentId);
                downloadData.append('csrf_token', csrfToken);

                const response = await fetch('/backend/api/download.php', { method: 'POST', body: downloadData });
                const result   = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'PDF generation failed.');
                }

                const link = document.createElement('a');
                link.href     = result.pdf_url;
                link.download = `NIIT_ID_${verifiedStudentId}.pdf`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                showToast('Download started!', 'success');

            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                hideLoader();
            }
        });
    }
});
