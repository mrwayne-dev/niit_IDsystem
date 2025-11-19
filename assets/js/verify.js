document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('verify-form'); 
    const modal = document.getElementById('verification-modal');
    const downloadBtn = document.getElementById('modal-download-btn');
    
    let verifiedStudentId = null;

    // modal controls
    const openVerifyModal = () => modal.classList.add('active'); 
    window.closeVerifyModal = () => { 
        modal.classList.remove('active');
    };

    // handler for student verification
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
                const formData = new FormData(form);
                
            
                const verifyUrl = '/backend/api/verify_id.php';

                const response = await fetch(verifyUrl, { method: 'POST', body: formData });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Verification failed. Server returned an unexpected status.');
                }

                // after success open modal and show toast
                verifiedStudentId = result.student_id;
                showToast('Student details verified!', 'success');
                openVerifyModal();

            } catch (error) {
                console.error('Verification Error:', error);
                showToast(error.message, 'error');
            } finally {
                hideLoader();
            }
        });
    }


    // handler for downloading pdf
    if (downloadBtn) {
        downloadBtn.addEventListener('click', async () => {
            if (!verifiedStudentId) {
                showToast('Verification session expired. Please re-verify.', 'error');
                closeVerifyModal();
                return;
            }

            closeVerifyModal();
            showLoader();
            showToast('Generating ID Card PDF...', 'success');

            try {
                const downloadData = new FormData();
                downloadData.append('student_id', verifiedStudentId);
                
                const downloadUrl = '/backend/api/download.php';
                const response = await fetch(downloadUrl, { method: 'POST', body: downloadData });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'PDF generation failed. Check server logs.');
                }

              
                const link = document.createElement('a');
                link.href = result.pdf_url; 
                link.download = `NIIT_ID_${verifiedStudentId}.pdf`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                showToast('Download started!', 'success');

            } catch (error) {
                console.error('Download Error:', error);
                showToast(error.message, 'error');
            } finally {
                hideLoader();
            }
        });
    }
});