<style>
    .req-star { color: #dc2626; font-weight: 800; }
    .pay-lock-note { margin: 8px 0 0; color: #b45309; font-size: 12px; font-weight: 700; }
    button.is-locked {
        opacity: 0.45 !important;
        filter: blur(1px);
        cursor: not-allowed !important;
        pointer-events: none !important;
    }
</style>
<script>
function markRequiredStars(root) {
    const scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('input[required], select[required], textarea[required]').forEach((field) => {
        if (field.type === 'radio' || field.type === 'checkbox' || field.type === 'hidden' || field.disabled) return;
        if (field.closest('[data-no-required-stars]')) return;
        let label = field.id ? document.querySelector('label[for="' + CSS.escape(field.id) + '"]') : null;
        if (!label) label = field.closest('label');
        if (!label) {
            const wrap = field.parentElement;
            label = wrap ? wrap.querySelector('label') : null;
        }
        if (!label || label.querySelector('.req-star')) return;
        const mark = document.createElement('span');
        mark.className = 'req-star';
        mark.setAttribute('aria-hidden', 'true');
        mark.textContent = '*';
        label.appendChild(document.createTextNode(' '));
        label.appendChild(mark);
    });
}
document.addEventListener('DOMContentLoaded', function () { markRequiredStars(document); });
document.addEventListener('change', function () { markRequiredStars(document); }, true);
</script>
