document.addEventListener('DOMContentLoaded', () => {
    const root = document.body;
    const storedTheme = localStorage.getItem('epinx-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    function applyTheme(theme) {
        root.setAttribute('data-bs-theme', theme);
        const toggleIcon = document.querySelector('#themeToggle span');
        if (toggleIcon) {
            toggleIcon.textContent = theme === 'dark' ? toggleIcon.getAttribute('data-dark-label') ?? '🌜' : toggleIcon.getAttribute('data-light-label') ?? '🌞';
        }
        localStorage.setItem('epinx-theme', theme);
    }

    applyTheme(storedTheme ?? (prefersDark ? 'dark' : 'light'));

    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const current = root.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });
    }

    // Bootstrap validation helper
    document.querySelectorAll('.needs-validation').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
