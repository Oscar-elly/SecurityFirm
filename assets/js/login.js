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

  const formData = new FormData(loginForm);

  fetch('login.php', {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.redirect) {
        window.location.href = data.redirect;
      } else {
        alert(data.error || 'Login failed');
      }
    })
    .catch(error => {
      console.error('Login error:', error);
      alert('Something went wrong. Please try again.');
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