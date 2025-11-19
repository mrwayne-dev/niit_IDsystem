// assets/js/create.js

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('create-id-form');
    const processBtn = document.getElementById('process-btn'); 
    
    // --- Preview Elements ---
    const previewFullname = document.getElementById('preview-fullname');
    const previewStudentId = document.getElementById('preview-studentid');
    const previewSemester = document.getElementById('preview-semester');
    const previewBatch = document.getElementById('preview-batch');
    const previewCourse = document.getElementById('preview-course');
    const previewDuration = document.getElementById('preview-duration');
    const previewExpiry = document.getElementById('preview-expiry');
    const previewPhoto = document.getElementById('preview-photo');
    const previewSignature = document.getElementById('preview-signature');
    
    const photoInput = form.querySelector('input[name="photo"]');
    const signatureInput = form.querySelector('input[name="signature"]');
    
    // --- local preview file reader ---
    function setupImagePreview(input, imgElement, defaultSrc = '') {
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

    // --- live update block ---
    form.querySelectorAll('input:not([type="file"])').forEach(input => {
        input.addEventListener('input', () => {
            const fieldName = input.name;
            const value = input.value;
            
            switch (fieldName) {
                case 'first_name':
                case 'last_name':
                    const firstName = form.elements['first_name'].value;
                    const lastName = form.elements['last_name'].value;
                    previewFullname.textContent = `${firstName} ${lastName}`.toUpperCase();
                    break;
                case 'student_id': previewStudentId.textContent = value.toUpperCase(); break;
                case 'semester_code': previewSemester.textContent = value.toUpperCase(); break;
                case 'batch_code': previewBatch.textContent = value.toUpperCase(); break;
                case 'course': previewCourse.textContent = value; break;
                case 'duration': previewDuration.textContent = value; break;
                case 'expiry_date':
                    if (value) {
                        const date = new Date(value);
                        previewExpiry.textContent = date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }).toUpperCase();
                    } else {
                        previewExpiry.textContent = 'N/A';
                    }
                    break;
            }
        });
    });


    setupImagePreview(photoInput, previewPhoto, 'assets/img/placeholder.png');
    setupImagePreview(signatureInput, previewSignature, '');


    // --- save and generate the pdf ---
    processBtn.addEventListener('click', async (e) => {
        e.preventDefault(); 
        
        if (!form.checkValidity()) {
            form.reportValidity();
            showToast('Please fill out all required fields.', 'error');
            return;
        }

        showLoader();
        
        try {
            // save data
            const formData = new FormData(form);
            const saveUrl = '/backend/api/create_id.php';

            const saveResponse = await fetch(saveUrl, { method: 'POST', body: formData });
            const saveResult = await saveResponse.json();

            if (!saveResponse.ok || !saveResult.success) {
                throw new Error(saveResult.message || 'Database save failed.');
            }

            //generate pdf
            showToast('Details saved. Generating PDF...', 'success');
            
            const studentID = form.elements['student_id'].value;
            const downloadData = new FormData();
            downloadData.append('student_id', studentID);
            
            const downloadUrl = '/backend/api/download.php';
            const downloadResponse = await fetch(downloadUrl, { method: 'POST', body: downloadData });
            const downloadResult = await downloadResponse.json();

            if (!downloadResponse.ok || !downloadResult.success) {
                throw new Error(downloadResult.message || 'PDF Generation failed.');
            }

            // download pdf
            showToast('Downloading PDF...', 'success');

            const link = document.createElement('a');
            link.href = downloadResult.pdf_url;
            link.download = `NIIT_ID_${studentID}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

        } catch (error) {
            console.error('Process Error:', error);
            showToast(error.message, 'error');
        } finally {
            hideLoader();
        }
    });
});