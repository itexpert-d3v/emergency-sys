const initReportPhoto = () => {
    const input = document.getElementById('photo');
    const dropzone = document.querySelector('[data-photo-dropzone]');
    const preview = document.querySelector('[data-photo-preview]');
    const previewImage = document.querySelector('[data-photo-preview-image]');
    const previewName = document.querySelector('[data-photo-preview-name]');
    const previewSize = document.querySelector('[data-photo-preview-size]');
    const removeButton = document.querySelector('[data-photo-remove]');
    if (!input || !dropzone) return;

    let objectUrl = null;

    const clearPreview = () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        input.value = '';
        preview?.classList.add('hidden');
        previewImage?.removeAttribute('src');
        dropzone.classList.remove('hidden');
    };

    const showFile = (file) => {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            clearPreview();
            window.showFeedback?.('error', 'Choose a JPG, PNG, or WebP photo.');
            return;
        }
        if (file.size > 4 * 1024 * 1024) {
            clearPreview();
            window.showFeedback?.('error', 'This photo is larger than 4 MB. Choose a smaller image.');
            return;
        }

        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        previewImage.src = objectUrl;
        previewName.textContent = file.name;
        previewSize.textContent = file.size >= 1024 * 1024
            ? `${(file.size / (1024 * 1024)).toFixed(1)} MB`
            : `${Math.max(1, Math.round(file.size / 1024))} KB`;
        preview?.classList.remove('hidden');
        dropzone.classList.add('hidden');
    };

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (file) showFile(file);
    });

    dropzone.addEventListener('click', () => input.click());
    dropzone.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            input.click();
        }
    });

    ['dragenter', 'dragover'].forEach((name) => dropzone.addEventListener(name, (event) => {
        event.preventDefault();
        dropzone.classList.add('is-dragging');
    }));
    ['dragleave', 'drop'].forEach((name) => dropzone.addEventListener(name, (event) => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
    }));
    dropzone.addEventListener('drop', (event) => {
        const file = event.dataTransfer?.files?.[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        showFile(file);
    });

    removeButton?.addEventListener('click', clearPreview);
    window.addEventListener('pagehide', () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
    });
};

document.addEventListener('DOMContentLoaded', initReportPhoto);
