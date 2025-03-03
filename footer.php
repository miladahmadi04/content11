            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script>
// Toggle submenu functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add click event to all submenu toggles
    const submenuToggles = document.querySelectorAll('.submenu-toggle, .has-submenu > a');
    submenuToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.closest('.has-submenu') || this.parentElement;
            parent.classList.toggle('open');
        });
    });
    
    // If a submenu has an active item inside, open it by default
    const activeSubmenuItems = document.querySelectorAll('.submenu .active');
    activeSubmenuItems.forEach(function(item) {
        const parentMenu = item.closest('.has-submenu');
        if (parentMenu) {
            parentMenu.classList.add('open');
        }
    });
    
    // Allow submenu links to work (not just toggle)
    const submenuLinks = document.querySelectorAll('.submenu a');
    submenuLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Don't prevent default here - allow navigation
            e.stopPropagation(); // Stop the event from bubbling up to parent toggles
        });
    });
});
</script>
</body>
</html>