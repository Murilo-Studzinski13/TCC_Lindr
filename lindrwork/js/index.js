document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.post-photo').forEach((photo) => {
        photo.addEventListener('error', () => {
            photo.style.display = 'none';
        });
    });

    document.querySelectorAll('.ab').forEach((button) => {
        button.addEventListener('click', () => button.classList.add('pressed'));
    });
});
