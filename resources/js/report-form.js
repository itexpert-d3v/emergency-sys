const form = document.getElementById('report-form');

if (form) {
    const submitBtn = form.querySelector('[type="submit"]');
    const submitLabel = submitBtn?.querySelector('span')?.textContent ?? 'Submit a report';
    let busy = false;

    const clearFieldError = (element) => {
        if (!element?.name) return;
        element.classList.remove('field-invalid');
        element.removeAttribute('aria-invalid');
        element.removeAttribute('aria-describedby');
        form.querySelectorAll(`[data-error-for="${element.name}"]`).forEach((node) => node.remove());
    };

    const clearErrors = () => {
        [...form.elements].forEach(clearFieldError);
    };

    const markInvalid = (element, message) => {
        clearFieldError(element);
        const paragraph = document.createElement('p');
        paragraph.dataset.errorFor = element.name;
        paragraph.id = `${element.id || element.name}-error`;
        paragraph.setAttribute('role', 'alert');
        paragraph.className = 'mt-1 text-xs font-semibold text-[var(--danger)]';
        paragraph.textContent = message;
        element.classList.add('field-invalid');
        element.setAttribute('aria-invalid', 'true');
        element.setAttribute('aria-describedby', paragraph.id);
        (element.closest('label') ?? element).insertAdjacentElement('afterend', paragraph);
    };

    const showAlert = (text) => window.showFeedback?.('error', text);

    const showErrors = (errors) => {
        let first = null;
        const general = [];

        for (const [name, messages] of Object.entries(errors)) {
            const key = name === 'latitude' || name === 'longitude' ? 'location_text' : name;
            const element = form.elements[key];
            if (!element || element.type === 'hidden') {
                general.push(messages[0]);
                continue;
            }
            markInvalid(element, messages[0]);
            first ??= element;
        }

        if (general.length) showAlert(general.join(' '));
        if (first) {
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            first.focus({ preventScroll: true });
        }
    };

    // Clear field errors as the user fixes them
    form.addEventListener('input', (event) => clearFieldError(event.target));
    form.addEventListener('change', (event) => clearFieldError(event.target));

    // ❌ REMOVED: form.addEventListener('invalid', ...) — this caused native
    // browser validation popups and repeated alerts on every keystroke.

    const setBusy = (busyState) => {
        busy = busyState;
        if (!submitBtn) return;
        submitBtn.disabled = busyState;
        const label = submitBtn.querySelector('span');
        if (label) label.textContent = busyState ? 'Submitting…' : submitLabel;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy) return;

        clearErrors();

        if (!form.checkValidity()) {
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus({ preventScroll: true });
            }
            showAlert('Please complete the highlighted fields before submitting.');
            return;
        }

        setBusy(true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json().catch(() => ({}));

            if (response.ok && data.tracking_url) {
                window.dispatchEvent(new CustomEvent('emergency-report-confirmed'));
                window.location.href = data.tracking_url;
                return;
            }

            if (response.status === 422 && data.errors) showErrors(data.errors);
            else if (response.status === 419) showAlert('Your session expired. Reload the page and submit again.');
            else if (response.status === 413) showAlert('The upload is too large. Remove or replace the photo and try again.');
            else if (response.status === 429) showAlert('Too many attempts. Wait a moment and try again.');
            else showAlert('Something went wrong on our side. Your details are still here, so please try again.');
        } catch {
            showAlert('No connection. Check your internet and try again. Your details are still here.');
        }

        setBusy(false);
    });

    const state = window.reportFormState || {};
    for (const [name, value] of Object.entries(state.old || {})) {
        const element = form.elements[name];
        if (!element || element.type === 'file' || element.type === 'hidden') continue;
        if (element.type === 'checkbox') element.checked = !!value;
        else element.value = value;
        element.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (state.errors && Object.keys(state.errors).length) showErrors(state.errors);
}