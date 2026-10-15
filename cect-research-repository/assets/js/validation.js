document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registerForm') || document.getElementById('addStudentForm');
    if (!form) {
        return;
    }

    const studentIdInput = document.getElementById('student_id');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');

    const STUDENT_ID_REGEX = /^\d{2}-\d{4}-\d{3}$/;
    const INSTITUTIONAL_EMAIL_REGEX = /^[a-z]+\.[a-z]+@wesleyan\.edu\.ph$/i;

    function setFieldState(input, isValid, message) {
        if (!input) {
            return;
        }
        input.classList.toggle('is-invalid', !isValid);
        input.classList.toggle('is-valid', isValid);

        let feedback = input.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            input.parentElement.appendChild(feedback);
        }
        feedback.textContent = message || '';
    }

    function validStudentId() {
        if (!studentIdInput) {
            return true;
        }
        const ok = STUDENT_ID_REGEX.test(studentIdInput.value.trim());
        setFieldState(studentIdInput, ok, ok ? '' : 'Format must be 00-0000-000.');
        return ok;
    }

    function validPassword() {
        if (!passwordInput) {
            return true;
        }
        const value = passwordInput.value;
        const ok = value.length >= 8 && /[A-Za-z]/.test(value) && /\d/.test(value);
        setFieldState(passwordInput, ok, ok ? '' : 'At least 8 characters, with a letter and a number.');
        return ok;
    }

    function validConfirm() {
        if (!confirmInput) {
            return true;
        }
        const ok = confirmInput.value === passwordInput.value && confirmInput.value.length > 0;
        setFieldState(confirmInput, ok, ok ? '' : 'Passwords do not match.');
        return ok;
    }

    function validEmail() {
        if (!emailInput) {
            return true;
        }
        const ok = INSTITUTIONAL_EMAIL_REGEX.test(emailInput.value.trim());
        setFieldState(emailInput, ok, ok ? '' : 'Format must be lastname.firstname@wesleyan.edu.ph (e.g. delacruz.juan@wesleyan.edu.ph).');
        return ok;
    }

    if (studentIdInput) studentIdInput.addEventListener('input', validStudentId);
    if (emailInput) emailInput.addEventListener('input', validEmail);
    if (passwordInput) passwordInput.addEventListener('input', function () { validPassword(); validConfirm(); });
    if (confirmInput) confirmInput.addEventListener('input', validConfirm);

    form.addEventListener('submit', function (e) {
        const validations = [validStudentId(), validEmail(), validPassword(), validConfirm()];
        if (validations.includes(false)) {
            e.preventDefault();
        }
    });
});
