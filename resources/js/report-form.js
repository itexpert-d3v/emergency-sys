const form = document.getElementById('report-form');

if (form) {
  const alertBox = document.getElementById('form-alert');
  const submitBtn = form.querySelector('[type="submit"]');
  const submitLabel = submitBtn?.textContent;
  const MAX_EDGE = 1600;
  const QUALITY = 0.8;
  let busy = false;

  // ---------- error display ----------
  const clearFieldError = (el) => {
    if (!el?.name) return;
    el.classList.remove('field-invalid');
    el.removeAttribute('aria-invalid');
    el.removeAttribute('aria-describedby');
    form.querySelectorAll(`[data-error-for="${el.name}"]`).forEach((n) => n.remove());
  };

  const clearErrors = () => {
    [...form.elements].forEach(clearFieldError);
    alertBox?.classList.add('hidden');
  };

  const markInvalid = (el, message) => {
    clearFieldError(el);
    const p = document.createElement('p');
    p.dataset.errorFor = el.name;
    p.id = `${el.id || el.name}-error`;
    p.setAttribute('role', 'alert');
    p.className = 'mt-1 text-xs font-semibold text-red-600 dark:text-red-400';
    p.textContent = message;
    el.classList.add('field-invalid');
    el.setAttribute('aria-invalid', 'true');
    el.setAttribute('aria-describedby', p.id);
    (el.closest('label') ?? el).insertAdjacentElement('afterend', p);
  };

  const showAlert = (text) => {
    if (!alertBox) return;
    alertBox.textContent = text;
    alertBox.classList.remove('hidden');
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  };

  const showErrors = (errors) => {
    let first = null;
    const general = [];

    for (const [name, messages] of Object.entries(errors)) {
      // GPS has no visible input, so show its error under the location field
      const key = name === 'latitude' || name === 'longitude' ? 'location_text' : name;
      const el = form.elements[key];
      if (!el || el.type === 'hidden') {
        general.push(messages[0]);
        continue;
      }
      markInvalid(el, messages[0]);
      first ??= el;
    }

    if (general.length) showAlert(general.join(' '));
    if (first) {
      first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      first.focus({ preventScroll: true });
    }
  };

  // Typing in a field clears only that field's error; everything else stays.
  form.addEventListener('input', (e) => clearFieldError(e.target));
  form.addEventListener('change', (e) => clearFieldError(e.target));

  // ---------- image compression + WebP conversion ----------
  // Convenience only: the server re-validates and re-encodes every upload.
  async function compressImage(file) {
    const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
    const scale = Math.min(1, MAX_EDGE / Math.max(bitmap.width, bitmap.height));
    const w = Math.max(1, Math.round(bitmap.width * scale));
    const h = Math.max(1, Math.round(bitmap.height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    canvas.getContext('2d').drawImage(bitmap, 0, 0, w, h);
    bitmap.close?.();

    const toBlob = (type) => new Promise((resolve) => canvas.toBlob(resolve, type, QUALITY));

    // Older Safari silently returns PNG when WebP isn't supported, so check the result.
    let blob = await toBlob('image/webp');
    let ext = 'webp';
    if (!blob || blob.type !== 'image/webp') {
      blob = await toBlob('image/jpeg');
      ext = 'jpg';
    }

    // Don't swap in a bigger file when the original was already small and unscaled.
    if (!blob || (blob.size >= file.size && scale === 1)) return file;

    return new File([blob], file.name.replace(/\.\w+$/, '') + '.' + ext, {
      type: blob.type,
      lastModified: Date.now(),
    });
  }

  const photo = form.elements.photo;
  photo?.addEventListener('change', async () => {
    const file = photo.files[0];
    if (!file) return;

    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
      photo.value = '';
      markInvalid(photo, 'Choose a JPG, PNG, or WebP image.');
      return;
    }

    try {
      const compressed = await compressImage(file);
      const dt = new DataTransfer();
      dt.items.add(compressed);
      photo.files = dt.files;
    } catch {
      /* keep the original; the server will process it */
    }
  });

  // ---------- submit ----------
  const setBusy = (state) => {
    busy = state;
    if (!submitBtn) return;
    submitBtn.disabled = state;
    submitBtn.textContent = state ? 'Submitting…' : submitLabel;
  };

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (busy) return;

    clearErrors();
    setBusy(true);

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await res.json().catch(() => ({}));

      if (res.ok && data.tracking_url) {
        window.location.href = data.tracking_url;
        return; // keep the button disabled while navigating
      }

      if (res.status === 422 && data.errors) showErrors(data.errors);
      else if (res.status === 419) showAlert('Your session expired. Reload the page and submit again.');
      else if (res.status === 413) showAlert('The upload is too large. Remove or replace the photo and try again.');
      else if (res.status === 429) showAlert('Too many attempts. Wait a moment and try again.');
      else showAlert('Something went wrong on our side. Your details are still here, so please try again.');
    } catch {
      showAlert('No connection. Check your internet and try again. Your details are still here.');
    }

    setBusy(false);
  });

  // ---------- restore state after a full page reload (no-JS fallback) ----------
  const state = window.reportFormState || {};
  for (const [name, value] of Object.entries(state.old || {})) {
    const el = form.elements[name];
    if (!el || el.type === 'file' || el.type === 'hidden') continue;
    if (el.type === 'checkbox') el.checked = !!value;
    else el.value = value;
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }
  if (state.errors && Object.keys(state.errors).length) showErrors(state.errors);
}