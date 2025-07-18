document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('.login-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const loginButton = document.querySelector('.login-button');
    
    // Focus on email input when page loads
    if (emailInput) {
        emailInput.focus();
    }
    
    // Add simple validation before form submission
    loginForm.addEventListener('submit', function (event) {
        event.preventDefault();
        
        // Clear previous errors
        document.querySelectorAll('.field-error').forEach(el => el.remove());
        
        // Validate inputs
        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();
        let isValid = true;
        
        if (!email) {
            showError(emailInput, 'Email is required');
            isValid = false;
        } else if (!isValidEmail(email)) {
            showError(emailInput, 'Please enter a valid email');
            isValid = false;
        }
        
        if (!password) {
            showError(passwordInput, 'Password is required');
            isValid = false;
        }
        
        if (!isValid) return;
        
        // Show loading state
        loginButton.disabled = true;
        loginButton.textContent = 'Logging in...';
        
        const formData = new FormData(loginForm);
        
        fetch('login.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.error || 'Login failed');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            } else {
                throw new Error(data.error || 'Login failed');
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            alert(error.message || 'Something went wrong. Please try again.');
        })
        .finally(() => {
            loginButton.disabled = false;
            loginButton.textContent = 'Login';
        });
    });
    
    // Helper function to validate email format
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Helper function to show error message
    function showError(inputElement, message) {
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.textContent = message;
        errorElement.style.color = 'var(--error-color)';
        errorElement.style.fontSize = 'var(--font-size-sm)';
        errorElement.style.marginTop = 'var(--space-xs)';
        errorElement.style.animation = 'fadeIn var(--transition-fast) ease-in-out';
        
        // Insert error message after the input element
        inputElement.parentNode.appendChild(errorElement);
        
        // Add error styling to input
        inputElement.style.borderColor = 'var(--error-color)';
        
        // Add event listener to remove error when input changes
        inputElement.addEventListener('input', function() {
            errorElement.remove();
            inputElement.style.borderColor = '';
        }, { once: true });
    }
    
    // Add animation class to body after page is loaded
    document.body.classList.add('page-loaded');
});