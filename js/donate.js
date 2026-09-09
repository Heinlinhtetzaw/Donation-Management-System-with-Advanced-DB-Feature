(function () {
    const form = document.getElementById('donate-form');
    const phoneInput = document.getElementById('phone');
    const phoneError = document.getElementById('phone-error');
    if (!form || !phoneInput || !phoneError) {
        return;
    }

    function validatePhoneNumber() {
        const isValid = /^(?:[၀-၉]{11}|[0-9]{11})$/u.test(phoneInput.value.trim());
        phoneError.hidden = isValid;
        phoneInput.setAttribute('aria-invalid', isValid ? 'false' : 'true');
        return isValid;
    }

    phoneInput.addEventListener('input', function () {
        if (phoneInput.value.length === 11) {
            validatePhoneNumber();
        } else {
            phoneError.hidden = true;
            phoneInput.removeAttribute('aria-invalid');
        }
    });

    form.addEventListener('submit', function (event) {
        if (!validatePhoneNumber()) {
            event.preventDefault();
            phoneInput.focus();
        }
    });
}());
