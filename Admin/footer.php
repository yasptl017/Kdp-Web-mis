<!-- footer.php (FINAL FIXED VERSION) -->

<!-- jQuery FIRST (required for DataTables + plugins) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap JS (single and correct include) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
/* 
========================================================
SAFE & STABLE SIDEBAR + MENU SCRIPT
- No runtime errors
- Works on all pages
- Prevents breakage of editors (CKEditor, TinyMCE, etc.)
========================================================
*/
document.addEventListener('DOMContentLoaded', function () {

    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const header = document.getElementById('header');
    const mainContent = document.getElementById('mainContent');

    function safeToggle(el, cls){ if(el) el.classList.toggle(cls); }
    function safeAdd(el, cls){ if(el) el.classList.add(cls); }
    function safeRemove(el, cls){ if(el) el.classList.remove(cls); }

    // Toggle Sidebar
    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            if (!sidebar) return;

            if (window.innerWidth <= 768) {
                safeToggle(sidebar, 'show');
                safeToggle(sidebarOverlay, 'show');
            } else {
                safeToggle(sidebar, 'collapsed');
                safeToggle(header, 'expanded');
                safeToggle(mainContent, 'expanded');
            }
        });
    }

    // Close Sidebar On Overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            safeRemove(sidebar, 'show');
            safeRemove(sidebarOverlay, 'show');
        });
    }

    // Submenu Toggle
    document.querySelectorAll('.menu-toggle-item').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();

            const targetId = this.getAttribute('data-target');
            const submenu = targetId ? document.getElementById(targetId) : null;
            const arrow = this.querySelector('.menu-arrow');

            // Close others
            document.querySelectorAll('.submenu').forEach(menu => {
                if (menu !== submenu) safeRemove(menu, 'show');
            });

            // Reset arrows
            document.querySelectorAll('.menu-arrow').forEach(a => {
                if (a !== arrow) a.classList.remove('rotated');
            });

            // Toggle current
            if (submenu) safeToggle(submenu, 'show');
            if (arrow) arrow.classList.toggle('rotated');
        });
    });

    // Auto Select Active Menu Item
    try {
        let currentPage = window.location.pathname.split("/").pop().toLowerCase();

        document.querySelectorAll(".sidebar-menu a").forEach(link => {
            let page = link.getAttribute("href")?.split("/").pop().toLowerCase();
            if (page && page === currentPage) {
                document.querySelectorAll(".sidebar-menu a")
                      .forEach(l => l.classList.remove("active"));
                link.classList.add("active");

                let submenu = link.closest(".submenu");
                if (submenu) safeAdd(submenu, "show");

                let arrow = submenu?.previousElementSibling?.querySelector(".menu-arrow");
                if (arrow) arrow.classList.add("rotated");
            }
        });

    } catch (e) {
        console.warn("Sidebar active menu error:", e);
    }

});
</script>
