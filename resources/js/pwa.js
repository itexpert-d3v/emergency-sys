const isPrivateShell = document.body?.classList.contains('operations-surface')
  || document.body?.classList.contains('auth-surface');

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
  });

  navigator.serviceWorker.addEventListener('controllerchange', () => {});
}

if (!isPrivateShell) {
  let installPrompt = null;
  const promptBox = document.createElement('aside');
  promptBox.hidden = true;
  promptBox.setAttribute('aria-live', 'polite');
  promptBox.className = 'fixed bottom-4 left-4 right-4 z-[100] mx-auto flex max-w-xl items-center justify-between gap-3 rounded-lg border border-slate-300 bg-white p-3 text-sm text-slate-900 shadow-lg dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100';
  promptBox.innerHTML = '<p class="font-semibold">Add MDRRMO SOU for quicker access.</p><div class="flex shrink-0 gap-2"><button type="button" data-pwa-dismiss class="rounded px-3 py-2 font-bold">Not now</button><button type="button" data-pwa-install class="rounded bg-blue-800 px-3 py-2 font-bold text-white">Install</button></div>';
  document.addEventListener('DOMContentLoaded', () => document.body.append(promptBox));

  const ios = /iphone|ipad|ipod/i.test(navigator.userAgent);
  if (ios && !navigator.standalone) {
    promptBox.hidden = false;
    promptBox.innerHTML = '<p class="font-semibold">On iPhone or iPad: open Share, then choose “Add to Home Screen”.</p><button type="button" data-pwa-dismiss class="rounded px-3 py-2 font-bold">Dismiss</button>';
  }

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    try {
      if (localStorage.getItem('emergency-sys-pwa-dismissed') === '1') return;
    } catch {}
    promptBox.hidden = false;
  });

  promptBox.addEventListener('click', async (event) => {
    const dismiss = event.target.closest('[data-pwa-dismiss]');
    const install = event.target.closest('[data-pwa-install]');
    if (dismiss) {
      try { localStorage.setItem('emergency-sys-pwa-dismissed', '1'); } catch {}
      promptBox.hidden = true;
    }
    if (install && installPrompt) {
      installPrompt.prompt();
      await installPrompt.userChoice;
      installPrompt = null;
      promptBox.hidden = true;
    }
  });
}

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.ready.then((registration) => {
    if (!registration.waiting) return;
    const prompt = document.createElement('button');
    prompt.type = 'button';
    prompt.className = 'fixed bottom-4 right-4 z-[101] rounded-lg bg-amber-300 px-4 py-3 text-sm font-bold text-slate-950 shadow-lg';
    prompt.textContent = 'Update available — refresh';
    prompt.addEventListener('click', () => {
      if (document.querySelector('#report-form')) {
        prompt.textContent = 'Update ready — submit or leave this report, then refresh';
        prompt.disabled = true;
        return;
      }
      registration.waiting?.postMessage({ type: 'SKIP_WAITING' });
    });
    document.addEventListener('DOMContentLoaded', () => document.body.append(prompt));
  });
}
