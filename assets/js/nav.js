document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('.gs-menu-toggle');
    var menu = document.querySelector('.gs-primary-nav');

    if (!toggle || !menu) {
        return;
    }

    toggle.addEventListener('click', function () {
        var isOpen = menu.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
});
