// Student Portal JavaScript Validation

// Form validation utilities
const Validator = {
    // Email validation
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // Phone number validation (Indian format)
    isValidPhone: function(phone) {
        const phoneRegex = /^[6-9]\d{9}$/;
        return phoneRegex.test(phone);
    },

    // Student ID validation
    isValidStudentId: function(studentId) {
        const idRegex = /^[A-Za-z0-9]{6,20}$/;
        return idRegex.test(studentId);
    },

    // Password validation
    isValidPassword: function(password) {
        return password.length >= 6;
    },

    // Required field validation
    isRequired: function(value) {
        return value.trim() !== '';
    },

    // Percentage validation
    isValidPercentage: function(percentage) {
        const num = parseFloat(percentage);
        return !isNaN(num) && num >= 0 && num <= 100;
    },

    // Year validation
    isValidYear: function(year) {
        const currentYear = new Date().getFullYear();
        const num = parseInt(year);
        return !isNaN(num) && num >= 1990 && num <= currentYear;
    }
};

// Show error message
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorDiv = document.getElementById(fieldId + '_error');
    
    if (field) {
        field.classList.add('error');
    }
    
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    } else {
        // Create error message if doesn't exist
        const errorElement = document.createElement('div');
        errorElement.id = fieldId + '_error';
        errorElement.className = 'error-message';
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        
        if (field) {
            field.parentNode.appendChild(errorElement);
        }
    }
}

// Clear error message
function clearError(fieldId) {
    const field = document.getElementById(fieldId);
    const errorDiv = document.getElementById(fieldId + '_error');
    
    if (field) {
        field.classList.remove('error');
    }
    
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

// Clear all errors
function clearAllErrors() {
    const errorMessages = document.querySelectorAll('.error-message');
    const errorFields = document.querySelectorAll('.error');
    
    errorMessages.forEach(error => {
        error.style.display = 'none';
    });
    
    errorFields.forEach(field => {
        field.classList.remove('error');
    });
}

// Login form validation
function validateLogin() {
    let isValid = true;
    clearAllErrors();
    
    const studentId = document.getElementById('student_id').value;
    const password = document.getElementById('password').value;
    
    // Validate student ID or admin username
    if (!Validator.isRequired(studentId)) {
        showError('student_id', 'Student ID or Admin username is required');
        isValid = false;
    } else if (studentId.toLowerCase() !== 'admin' && !Validator.isValidStudentId(studentId)) {
        // Only apply student ID format validation if it's not admin login
        showError('student_id', 'Student ID must be 6-20 characters (letters and numbers only)');
        isValid = false;
    }
    
    // Validate password
    if (!Validator.isRequired(password)) {
        showError('password', 'Password is required');
        isValid = false;
    } else if (!Validator.isValidPassword(password)) {
        showError('password', 'Password must be at least 6 characters long');
        isValid = false;
    }
    
    return isValid;
}

// Registration form validation
function validateRegistration() {
    let isValid = true;
    clearAllErrors();
    
    // Basic details
    const studentId = document.getElementById('student_id').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const firstName = document.getElementById('first_name').value;
    const lastName = document.getElementById('last_name').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const dateOfBirth = document.getElementById('date_of_birth').value;
    const departmentId = document.getElementById('department_id').value;
    
    // Father details
    const fatherName = document.getElementById('father_name').value;
    const fatherPhone = document.getElementById('father_phone').value;
    
    // Education details
    const tenthPercentage = document.getElementById('tenth_percentage').value;
    const tenthBoard = document.getElementById('tenth_board').value;
    const tenthYear = document.getElementById('tenth_year').value;
    const twelfthPercentage = document.getElementById('twelfth_percentage').value;
    const twelfthBoard = document.getElementById('twelfth_board').value;
    const twelfthYear = document.getElementById('twelfth_year').value;
    
    // Validate required fields
    const requiredFields = [
        {id: 'student_id', value: studentId, name: 'Student ID'},
        {id: 'password', value: password, name: 'Password'},
        {id: 'confirm_password', value: confirmPassword, name: 'Confirm Password'},
        {id: 'first_name', value: firstName, name: 'First Name'},
        {id: 'last_name', value: lastName, name: 'Last Name'},
        {id: 'email', value: email, name: 'Email'},
        {id: 'phone', value: phone, name: 'Phone'},
        {id: 'date_of_birth', value: dateOfBirth, name: 'Date of Birth'},
        {id: 'department_id', value: departmentId, name: 'Department'},
        {id: 'father_name', value: fatherName, name: 'Father Name'},
        {id: 'father_phone', value: fatherPhone, name: 'Father Phone'},
        {id: 'tenth_percentage', value: tenthPercentage, name: '10th Percentage'},
        {id: 'tenth_board', value: tenthBoard, name: '10th Board'},
        {id: 'tenth_year', value: tenthYear, name: '10th Year'},
        {id: 'twelfth_percentage', value: twelfthPercentage, name: '12th Percentage'},
        {id: 'twelfth_board', value: twelfthBoard, name: '12th Board'},
        {id: 'twelfth_year', value: twelfthYear, name: '12th Year'}
    ];
    
    requiredFields.forEach(field => {
        if (!Validator.isRequired(field.value)) {
            showError(field.id, field.name + ' is required');
            isValid = false;
        }
    });
    
    // Specific validations
    if (Validator.isRequired(studentId) && !Validator.isValidStudentId(studentId)) {
        showError('student_id', 'Student ID must be 6-20 characters (letters and numbers only)');
        isValid = false;
    }
    
    if (Validator.isRequired(password) && !Validator.isValidPassword(password)) {
        showError('password', 'Password must be at least 6 characters long');
        isValid = false;
    }
    
    if (password !== confirmPassword) {
        showError('confirm_password', 'Passwords do not match');
        isValid = false;
    }
    
    if (Validator.isRequired(email) && !Validator.isValidEmail(email)) {
        showError('email', 'Please enter a valid email address');
        isValid = false;
    }
    
    if (Validator.isRequired(phone) && !Validator.isValidPhone(phone)) {
        showError('phone', 'Please enter a valid 10-digit phone number');
        isValid = false;
    }
    
    if (Validator.isRequired(fatherPhone) && !Validator.isValidPhone(fatherPhone)) {
        showError('father_phone', 'Please enter a valid 10-digit phone number');
        isValid = false;
    }
    
    if (Validator.isRequired(tenthPercentage) && !Validator.isValidPercentage(tenthPercentage)) {
        showError('tenth_percentage', 'Please enter a valid percentage (0-100)');
        isValid = false;
    }
    
    if (Validator.isRequired(twelfthPercentage) && !Validator.isValidPercentage(twelfthPercentage)) {
        showError('twelfth_percentage', 'Please enter a valid percentage (0-100)');
        isValid = false;
    }
    
    if (Validator.isRequired(tenthYear) && !Validator.isValidYear(tenthYear)) {
        showError('tenth_year', 'Please enter a valid year');
        isValid = false;
    }
    
    if (Validator.isRequired(twelfthYear) && !Validator.isValidYear(twelfthYear)) {
        showError('twelfth_year', 'Please enter a valid year');
        isValid = false;
    }
    
    return isValid;
}

// Profile update validation
function validateProfileUpdate() {
    let isValid = true;
    clearAllErrors();
    
    const firstName = document.getElementById('first_name').value;
    const lastName = document.getElementById('last_name').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const fatherPhone = document.getElementById('father_phone').value;
    const tenthPercentage = document.getElementById('tenth_percentage').value;
    const twelfthPercentage = document.getElementById('twelfth_percentage').value;
    const tenthYear = document.getElementById('tenth_year').value;
    const twelfthYear = document.getElementById('twelfth_year').value;
    
    // Validate required fields
    if (!Validator.isRequired(firstName)) {
        showError('first_name', 'First Name is required');
        isValid = false;
    }
    
    if (!Validator.isRequired(lastName)) {
        showError('last_name', 'Last Name is required');
        isValid = false;
    }
    
    if (!Validator.isRequired(email)) {
        showError('email', 'Email is required');
        isValid = false;
    } else if (!Validator.isValidEmail(email)) {
        showError('email', 'Please enter a valid email address');
        isValid = false;
    }
    
    if (Validator.isRequired(phone) && !Validator.isValidPhone(phone)) {
        showError('phone', 'Please enter a valid 10-digit phone number');
        isValid = false;
    }
    
    if (Validator.isRequired(fatherPhone) && !Validator.isValidPhone(fatherPhone)) {
        showError('father_phone', 'Please enter a valid 10-digit phone number');
        isValid = false;
    }
    
    if (Validator.isRequired(tenthPercentage) && !Validator.isValidPercentage(tenthPercentage)) {
        showError('tenth_percentage', 'Please enter a valid percentage (0-100)');
        isValid = false;
    }
    
    if (Validator.isRequired(twelfthPercentage) && !Validator.isValidPercentage(twelfthPercentage)) {
        showError('twelfth_percentage', 'Please enter a valid percentage (0-100)');
        isValid = false;
    }
    
    if (Validator.isRequired(tenthYear) && !Validator.isValidYear(tenthYear)) {
        showError('tenth_year', 'Please enter a valid year');
        isValid = false;
    }
    
    if (Validator.isRequired(twelfthYear) && !Validator.isValidYear(twelfthYear)) {
        showError('twelfth_year', 'Please enter a valid year');
        isValid = false;
    }
    
    return isValid;
}

// Real-time validation
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for real-time validation
    const inputs = document.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            const fieldId = this.id;
            const value = this.value;
            
            // Clear previous error
            clearError(fieldId);
            
            // Field-specific validation
            switch(fieldId) {
                case 'email':
                    if (value && !Validator.isValidEmail(value)) {
                        showError(fieldId, 'Please enter a valid email address');
                    }
                    break;
                    
                case 'phone':
                case 'father_phone':
                    if (value && !Validator.isValidPhone(value)) {
                        showError(fieldId, 'Please enter a valid 10-digit phone number');
                    }
                    break;
                    
                case 'student_id':
                    // Only validate student ID format if it's not admin and not empty
                    if (value && value.toLowerCase() !== 'admin' && !Validator.isValidStudentId(value)) {
                        showError(fieldId, 'Student ID must be 6-20 characters (letters and numbers only)');
                    }
                    break;
                    
                case 'password':
                    if (value && !Validator.isValidPassword(value)) {
                        showError(fieldId, 'Password must be at least 6 characters long');
                    }
                    break;
                    
                case 'confirm_password':
                    const password = document.getElementById('password');
                    if (password && value && value !== password.value) {
                        showError(fieldId, 'Passwords do not match');
                    }
                    break;
                    
                case 'tenth_percentage':
                case 'twelfth_percentage':
                    if (value && !Validator.isValidPercentage(value)) {
                        showError(fieldId, 'Please enter a valid percentage (0-100)');
                    }
                    break;
                    
                case 'tenth_year':
                case 'twelfth_year':
                    if (value && !Validator.isValidYear(value)) {
                        showError(fieldId, 'Please enter a valid year');
                    }
                    break;
            }
        });
        
        // Clear error on focus
        input.addEventListener('focus', function() {
            clearError(this.id);
        });
    });
});

// Confirm delete
function confirmDelete(studentName) {
    return confirm('Are you sure you want to delete the record for ' + studentName + '? This action cannot be undone.');
}

// File type validation
function validateFile(input, allowedTypes) {
    const file = input.files[0];
    if (!file) return true;
    
    const fileType = file.type.toLowerCase();
    const isValid = allowedTypes.some(type => fileType.includes(type));
    
    if (!isValid) {
        alert('Please select a valid file type: ' + allowedTypes.join(', '));
        input.value = '';
        return false;
    }
    
    // Check file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB');
        input.value = '';
        return false;
    }
    
    return true;
}