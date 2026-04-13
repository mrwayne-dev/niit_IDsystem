document.addEventListener('DOMContentLoaded', () => {
    const form       = document.getElementById('create-id-form');
    const processBtn = document.getElementById('process-btn');

    if (!form || !processBtn) return;

    // Preview elements
    const previewFullname  = document.getElementById('preview-fullname');
    const previewStudentId = document.getElementById('preview-studentid');
    const previewSemester  = document.getElementById('preview-semester');
    const previewBatch     = document.getElementById('preview-batch');
    const previewCourse    = document.getElementById('preview-course');
    const previewDuration  = document.getElementById('preview-duration');
    const previewExpiry    = document.getElementById('preview-expiry');
    const previewPhoto     = document.getElementById('preview-photo');
    const previewSignature = document.getElementById('preview-signature');

    const photoInput     = form.querySelector('input[name="photo"]');
    const signatureInput = form.querySelector('input[name="signature"]');

    // Live image preview from file input
    function setupImagePreview(input, imgElement, defaultSrc = '') {
        if (!input || !imgElement) return;
        input.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => { imgElement.src = e.target.result; };
                reader.readAsDataURL(file);
            } else {
                imgElement.src = defaultSrc;
            }
        });
    }

    // Live preview updates on text input
    form.querySelectorAll('input:not([type="file"]):not([type="hidden"])').forEach(input => {
        input.addEventListener('input', () => {
            const value = input.value;
            switch (input.name) {
                case 'first_name':
                case 'last_name': {
                    const fn = form.elements['first_name'].value;
                    const ln = form.elements['last_name'].value;
                    previewFullname.textContent = `${fn} ${ln}`.toUpperCase();
                    break;
                }
                case 'student_id':    previewStudentId.textContent = value.toUpperCase(); break;
                case 'semester_code': previewSemester.textContent  = value.toUpperCase(); break;
                case 'batch_code':    previewBatch.textContent     = value.toUpperCase(); break;
                case 'course':        previewCourse.textContent    = value; break;
                case 'duration':      previewDuration.textContent  = value; break;
                case 'expiry_date':
                    previewExpiry.textContent = value
                        ? new Date(value + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', year: 'numeric' }).toUpperCase()
                        : 'N/A';
                    break;
            }
        });
    });

    setupImagePreview(photoInput,     previewPhoto,     'assets/img/placeholder.png');
    setupImagePreview(signatureInput, previewSignature, '');

    function resetPreview() {
        previewFullname.textContent  = 'JOHN DOE';
        previewStudentId.textContent = 'NIIT12345';
        previewSemester.textContent  = 'SEM-2025A';
        previewBatch.textContent     = 'BCH-21';
        previewCourse.textContent    = 'Software Engineering';
        previewDuration.textContent  = '6 Months';
        previewExpiry.textContent    = 'DEC, 2027';
        previewPhoto.src             = 'assets/img/placeholder.png';
        previewSignature.src         = '';
    }

    // ── Save + Generate PDF ──────────────────────────────────────
    processBtn.addEventListener('click', async () => {
        if (!form.checkValidity()) {
            form.reportValidity();
            showToast('Please fill out all required fields.', 'error');
            return;
        }

        showLoader();

        try {
            // Step 1: Save student data
            const formData    = new FormData(form);
            const saveResponse = await fetch('/backend/api/create_id.php', { method: 'POST', body: formData });
            const saveResult   = await saveResponse.json();

            if (!saveResponse.ok || !saveResult.success) {
                throw new Error(saveResult.message || 'Failed to save student details.');
            }

            showToast('Details saved. Generating PDF...', 'success');

            // Step 2: Generate PDF (append CSRF token from form)
            const csrfToken    = formData.get('csrf_token') || '';
            const studentId    = form.elements['student_id'].value.toUpperCase();
            const downloadData = new FormData();
            downloadData.append('student_id', studentId);
            downloadData.append('csrf_token', csrfToken);

            const dlResponse = await fetch('/backend/api/download.php', { method: 'POST', body: downloadData });
            const dlResult   = await dlResponse.json();

            if (!dlResponse.ok || !dlResult.success) {
                throw new Error(dlResult.message || 'PDF generation failed.');
            }

            // Step 3: Trigger download
            showToast('Downloading PDF...', 'success');
            const link = document.createElement('a');
            link.href     = dlResult.pdf_url;
            link.download = `NIIT_ID_${studentId}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Step 4: Reset form for next entry
            form.reset();
            resetPreview();
            showToast('ID card created successfully!', 'success');

        } catch (error) {
            showToast(error.message, 'error');
        } finally {
            hideLoader();
        }
    });
});
