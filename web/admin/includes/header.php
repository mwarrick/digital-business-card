<header class="admin-header">
    <div class="header-content">
        <a href="/admin/dashboard.php" class="header-logo">📱 ShareMyCard Admin</a>
        
        <button class="hamburger" id="admin-hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <nav class="header-nav" id="admin-nav">
            <a href="/admin/dashboard.php">Dashboard</a>
            <a href="/admin/analytics.php">Analytics</a>
            <a href="/admin/users.php">Users</a>
            <a href="/admin/cards.php">Business Cards</a>
            <a href="/admin/debug-log.php">Debug Log</a>
            <div class="header-user">
                <span class="user-email"><?php echo htmlspecialchars(AdminAuth::getEmail()); ?></span>
                <a href="#" onclick="openAccountSecurity()">Security</a>
                <a href="/admin/logout.php">Logout</a>
            </div>
        </nav>
    </div>
</header>

<script>
// Admin navigation toggle functionality
document.addEventListener("DOMContentLoaded", function() {
    const navToggle = document.getElementById("admin-hamburger");
    const navMenu = document.getElementById("admin-nav");
    
    if (navToggle && navMenu) {
        navToggle.addEventListener("click", function() {
            navMenu.classList.toggle("active");
            navToggle.classList.toggle("active");
        });
    }
});
</script>

