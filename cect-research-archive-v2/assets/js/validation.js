/**
 * assets/js/validation.js — Client-side form validation
 * - Real-time Student ID format validation (^\d{2}-\d{4}-\d{3}$)
 * - Real-time password match checker
 * - Show/hide password toggles
 */

(function () {
  'use strict';

  const STUDENT_ID_PATTERN = /^\d{2}-\d{4}-\d{3}$/;

  // ── Student ID validation ────────────────────────────────────────
  const sidInput    = document.getElementById('student_id');
  const sidIcon     = document.getElementById('sidIcon');
  const sidStatus   = document.getElementById('sidStatus');
  const sidFeedback = document.getElementById('sidFeedback');

  function validateStudentId(value) {
    return STUDENT_ID_PATTERN.test(value.trim());
  }

  if (sidInput) {
    sidInput.addEventListener('input', function () {
      const val   = this.value.trim();
      const valid = validateStudentId(val);

      if (val === '') {
        // Neutral state
        sidIcon.className = 'bi bi-dash text-muted';
        sidStatus.classList.remove('bg-success-subtle', 'bg-danger-subtle');
        if (sidFeedback) sidFeedback.textContent = '';
        sidInput.classList.remove('is-valid', 'is-invalid');
        return;
      }

      if (valid) {
        sidIcon.className = 'bi bi-check-circle-fill text-success';
        sidStatus.className = 'input-group-text bg-success-subtle';
        sidInput.classList.add('is-valid');
        sidInput.classList.remove('is-invalid');
        if (sidFeedback) {
          sidFeedback.innerHTML = '<span class="sid-valid"><i class="bi bi-check-circle me-1"></i>Valid Student ID format</span>';
        }
      } else {
        sidIcon.className = 'bi bi-x-circle-fill text-danger';
        sidStatus.className = 'input-group-text bg-danger-subtle';
        sidInput.classList.add('is-invalid');
        sidInput.classList.remove('is-valid');
        if (sidFeedback) {
          sidFeedback.innerHTML = '<span class="sid-invalid"><i class="bi bi-x-circle me-1"></i>Must match YY-NNNN-NNN (digits only)</span>';
        }
      }
    });

    // Also validate on form submit
    const form = sidInput.closest('form');
    if (form) {
      form.addEventListener('submit', function (e) {
        if (!validateStudentId(sidInput.value)) {
          sidInput.focus();
          e.preventDefault();
          if (sidFeedback) {
            sidFeedback.innerHTML = '<span class="sid-invalid"><i class="bi bi-x-circle me-1"></i>Invalid Student ID — required format: YY-NNNN-NNN</span>';
          }
        }
      });
    }
  }

  // ── Password match checker ───────────────────────────────────────
  const pwdInput    = document.getElementById('password');
  const confirmPwd  = document.getElementById('confirm_password');
  const pwdFeedback = document.getElementById('pwdMatchFeedback');

  function checkPasswordMatch() {
    if (!pwdInput || !confirmPwd || !pwdFeedback) return;
    const newVal = pwdInput.value;
    const conVal = confirmPwd.value;

    if (conVal === '') {
      pwdFeedback.textContent = '';
      confirmPwd.classList.remove('is-valid', 'is-invalid');
      return;
    }

    if (newVal === conVal) {
      pwdFeedback.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Passwords match</span>';
      confirmPwd.classList.add('is-valid');
      confirmPwd.classList.remove('is-invalid');
    } else {
      pwdFeedback.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Passwords do not match</span>';
      confirmPwd.classList.add('is-invalid');
      confirmPwd.classList.remove('is-valid');
    }
  }

  if (confirmPwd) confirmPwd.addEventListener('input', checkPasswordMatch);
  if (pwdInput)   pwdInput.addEventListener('input',   checkPasswordMatch);

  // ── Show / hide password toggles ─────────────────────────────────
  document.getElementById('togglePwd1')?.addEventListener('click', function () {
    toggle('password', 'eyeIcon1');
  });
  document.getElementById('togglePwd2')?.addEventListener('click', function () {
    toggle('confirm_password', 'eyeIcon2');
  });

  function toggle(inputId, iconId) {
    const inp  = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!inp) return;
    if (inp.type === 'password') {
      inp.type = 'text';
      if (icon) icon.className = 'bi bi-eye-slash';
    } else {
      inp.type = 'password';
      if (icon) icon.className = 'bi bi-eye';
    }
  }

})();
