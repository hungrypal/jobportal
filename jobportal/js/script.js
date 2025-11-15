// Custom JavaScript for Job Portal

document.addEventListener('DOMContentLoaded', function() {
    
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.job-card, .dashboard-card, .form-container');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
    
    // Search functionality for jobs
    const searchInput = document.getElementById('jobSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const jobCards = document.querySelectorAll('.job-card');
            
            jobCards.forEach(card => {
                const title = card.querySelector('h5').textContent.toLowerCase();
                const company = card.querySelector('.company-name').textContent.toLowerCase();
                const description = card.querySelector('p').textContent.toLowerCase();
                
                if (title.includes(filter) || company.includes(filter) || description.includes(filter)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
    
    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Dynamic salary range display
    const salaryMin = document.getElementById('salary_min');
    const salaryMax = document.getElementById('salary_max');
    const salaryDisplay = document.getElementById('salaryDisplay');
    
    if (salaryMin && salaryMax && salaryDisplay) {
        function updateSalaryDisplay() {
            const min = salaryMin.value || 0;
            const max = salaryMax.value || 0;
            salaryDisplay.textContent = `$${parseInt(min).toLocaleString()} - $${parseInt(max).toLocaleString()}`;
        }
        
        salaryMin.addEventListener('input', updateSalaryDisplay);
        salaryMax.addEventListener('input', updateSalaryDisplay);
        updateSalaryDisplay();
    }
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Character counter for textareas
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        const counter = document.createElement('small');
        counter.className = 'form-text text-muted';
        counter.textContent = `0/${maxLength} characters`;
        textarea.parentNode.appendChild(counter);
        
        textarea.addEventListener('input', function() {
            const length = this.value.length;
            counter.textContent = `${length}/${maxLength} characters`;
            counter.className = length > maxLength * 0.9 ? 'form-text text-warning' : 'form-text text-muted';
        });
    });
    
    // Job type filter
    const jobTypeFilter = document.getElementById('jobTypeFilter');
    if (jobTypeFilter) {
        jobTypeFilter.addEventListener('change', function() {
            const filterValue = this.value;
            const jobCards = document.querySelectorAll('.job-card');
            
            jobCards.forEach(card => {
                const jobType = card.querySelector('.job-detail:nth-child(2)').textContent.toLowerCase();
                if (filterValue === '' || jobType.includes(filterValue.toLowerCase())) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
    
    // Real-time password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        const strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength mt-2';
        passwordInput.parentNode.appendChild(strengthIndicator);
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = getPasswordStrength(password);
            
            strengthIndicator.innerHTML = `
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar ${strength.class}" style="width: ${strength.width}%"></div>
                </div>
                <small class="text-muted">${strength.text}</small>
            `;
        });
    }
    
    function getPasswordStrength(password) {
        let strength = 0;
        let text = '';
        let className = 'bg-danger';
        
        if (password.length >= 8) strength += 25;
        if (/[a-z]/.test(password)) strength += 25;
        if (/[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password)) strength += 25;
        
        if (strength < 50) {
            text = 'Weak';
            className = 'bg-danger';
        } else if (strength < 75) {
            text = 'Fair';
            className = 'bg-warning';
        } else {
            text = 'Strong';
            className = 'bg-success';
        }
        
        return { width: strength, text: text, class: className };
    }
});

// Utility functions
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function timeAgo(date) {
    const now = new Date();
    const diffTime = Math.abs(now - new Date(date));
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) return '1 day ago';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
    return `${Math.floor(diffDays / 30)} months ago`;
}