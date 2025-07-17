document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggleBtn = document.getElementById('mobile-sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
        });
    }
});
