
export function initSubmitLoading(form, btnId, textId, loadingId) {
    form.addEventListener('submit', () => {
        const btn     = document.getElementById(btnId);
        const text    = document.getElementById(textId);
        const loading = document.getElementById(loadingId);

        if (btn && text && loading) {
            btn.disabled = true;
            text.classList.add('hidden');
            loading.classList.remove('hidden');
        }
    });
}
