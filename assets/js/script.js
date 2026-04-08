// assets/js/script.js - Client-side validations and interactions

// SweetAlert2 CDN for notifications (load in HTML)
// https://cdn.jsdelivr.net/npm/sweetalert2@11

function showAlert(icon, title, text) {
    Swal.fire({
        icon: icon,
        title: title,
        text: text,
        confirmButtonColor: '#667eea'
    });
}

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    form.addEventListener('submit', function(e) {
        // Server-side is primary, this is just UX
        const required = form.querySelectorAll('[required]');
        let valid = true;
        required.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                valid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        if (!valid) {
            e.preventDefault();
            showAlert('error', 'Error', 'Mohon isi semua field wajib');
        }
    });
}

// Image preview for book cover upload
function previewImage(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) { // 2MB
                showAlert('error', 'Error', 'Ukuran file maksimal 2MB');
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// Edit book modal population
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('editBookModal'));
            modal.show();
            
            // Fill form with book data
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_title').value = this.dataset.title;
            document.getElementById('edit_author').value = this.dataset.author;
            document.getElementById('edit_publisher').value = this.dataset.publisher;
            document.getElementById('edit_year').value = this.dataset.year;
            document.getElementById('edit_stock').value = this.dataset.stock;
            document.getElementById('edit_category').value = this.dataset.category || '';
        });
    });

    // Edit image preview
    previewImage('cover_image', 'edit_preview'); // For add modal
    const editFileInput = document.getElementById('cover_image'); // Wait for dynamic load
    if (editFileInput) {
        editFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('edit_preview').src = e.target.result;
                    document.getElementById('edit_preview').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    }
});


