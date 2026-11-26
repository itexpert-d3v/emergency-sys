export function shakeElement(el) {
    if (!(el instanceof Element)) return;
    el.classList.remove('is-shaking');
    void el.offsetWidth;
    el.classList.add('is-shaking');
    el.addEventListener('animationend', () => el.classList.remove('is-shaking'), { once: true });
}

const bindShakeTargets = (root = document) => {
    root.querySelectorAll('[data-shake-target]').forEach((target) => {
        if (target.dataset.shakeBound === 'true') return;
        target.dataset.shakeBound = 'true';

        const form = target.closest('form') ?? target.form;
        if (!form) return;

        form.addEventListener('invalid', (event) => {
            event.preventDefault();
            shakeElement(target);
        }, true);
    });
};

bindShakeTargets();
document.addEventListener('DOMContentLoaded', () => bindShakeTargets());

export function showFeedback(type, message) {
    let stack = document.querySelector('[data-feedback-client-stack]');
    if (!stack) {
        stack = document.createElement('div');
        stack.className = 'feedback-stack feedback-stack-top-right';
        stack.dataset.feedbackClientStack = '';
        document.body.append(stack);
    }

    stack.querySelectorAll('.feedback-banner').forEach((banner) => banner.remove());
    const banner = document.createElement('div');
    banner.className = `feedback-banner feedback-banner-${type === 'error' ? 'error' : 'success'} is-entering${type === 'error' ? ' is-shaking' : ''}`;
    banner.setAttribute('role', type === 'error' ? 'alert' : 'status');

    const icon = document.createElement('span');
    icon.className = 'feedback-banner-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = type === 'error' ? '!' : '✓';

    const body = document.createElement('div');
    body.className = 'feedback-banner-body';
    const text = document.createElement('p');
    text.textContent = message;
    body.append(text);

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'feedback-banner-close';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.dataset.feedbackDismiss = '';
    close.textContent = '×';

    banner.append(icon, body, close);
    stack.append(banner);
}

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;
    const close = event.target.closest('[data-feedback-dismiss]');
    close?.closest('[data-feedback-banner], .feedback-banner')?.remove();
});

window.shakeElement = shakeElement;
window.showFeedback = showFeedback;
