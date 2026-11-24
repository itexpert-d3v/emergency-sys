const form = document.getElementById('report-form');

if (form) {
  const DB_NAME = 'emergency-sys-report-draft';
  const STORE = 'drafts';
  const TTL_MS = 30 * 60 * 1000;

  const status = document.createElement('p');
  status.className = 'mt-3 hidden rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-950';
  status.setAttribute('role', 'status');
  form.prepend(status);

  const openDb = () => new Promise((resolve, reject) => {
    if (!('indexedDB' in window)) return reject(new Error('IndexedDB unavailable'));
    const request = indexedDB.open(DB_NAME, 1);
    request.onupgradeneeded = () => request.result.createObjectStore(STORE);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });

  const readDraft = async () => {
    const db = await openDb();
    return new Promise((resolve, reject) => {
      const request = db.transaction(STORE, 'readonly').objectStore(STORE).get('current');
      request.onsuccess = () => resolve(request.result || null);
      request.onerror = () => reject(request.error);
    });
  };

  const writeDraft = async (value) => {
    const db = await openDb();
    return new Promise((resolve, reject) => {
      const request = db.transaction(STORE, 'readwrite').objectStore(STORE).put(value, 'current');
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  };

  const deleteDraft = async () => {
    const db = await openDb();
    return new Promise((resolve, reject) => {
      const request = db.transaction(STORE, 'readwrite').objectStore(STORE).delete('current');
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  };

  const fields = ['description', 'reporter_name', 'contact_number', 'relationship', 'location_text', 'latitude', 'longitude', 'location_accuracy', 'reporting_for_other', 'victim_contactable'];
  const collectDraft = () => {
    const values = {};
    fields.forEach((name) => {
      const el = form.elements[name];
      if (!el || el.type === 'file' || el.type === 'hidden' && ['reporting_for_other', 'victim_contactable'].includes(name) === false) return;
      if (el.type === 'checkbox') values[name] = el.checked;
      else if (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT') values[name] = el.value;
    });
    return { savedAt: Date.now(), values };
  };

  const showSaved = () => {
    status.textContent = 'Saved on this device, not yet sent. It expires after 30 minutes and does not include photos or security tokens.';
    status.classList.remove('hidden');
  };

  let saveTimer;
  form.addEventListener('input', () => {
    clearTimeout(saveTimer);
    saveTimer = window.setTimeout(async () => {
      try { await writeDraft(collectDraft()); showSaved(); } catch {}
    }, 400);
  });

  form.addEventListener('change', () => {
    clearTimeout(saveTimer);
    saveTimer = window.setTimeout(async () => {
      try { await writeDraft(collectDraft()); showSaved(); } catch {}
    }, 400);
  });

  readDraft().then((draft) => {
    if (!draft || Date.now() - draft.savedAt > TTL_MS) {
      if (draft) deleteDraft().catch(() => {});
      return;
    }
    for (const [name, value] of Object.entries(draft.values || {})) {
      const el = form.elements[name];
      if (!el || el.type === 'file') continue;
      if (el.type === 'checkbox') el.checked = Boolean(value);
      else el.value = value;
    }
    showSaved();
  }).catch(() => {});

  window.addEventListener('online', () => {
    showSaved();
    status.textContent = 'Draft available. Return to this page, reload for a fresh session, and submit manually.';
  });

  window.addEventListener('offline', () => {
    showSaved();
  });

  form.addEventListener('submit', () => {
    try { writeDraft(collectDraft()).catch(() => {}); } catch {}
  });

  window.addEventListener('emergency-report-confirmed', () => {
    deleteDraft().catch(() => {});
  });
}
