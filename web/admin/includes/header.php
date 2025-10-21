<nav class="navbar">
    <div class="nav-brand">
        <a href="/admin/dashboard.php" class="brand-link">
            <span class="brand-icon">📇</span>
            <span class="brand-text">ShareMyCard Admin</span>
        </a>
    </div>
    
    <button class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <div class="nav-links mobile">
        <a href="/admin/dashboard.php" class="nav-link">🏠 Dashboard</a>
        <a href="/admin/analytics.php" class="nav-link">📊 Analytics</a>
        <a href="/admin/users.php" class="nav-link">👥 Users</a>
        <a href="/admin/cards.php" class="nav-link">📇 Business Cards</a>
        <a href="/admin/debug-log.php" class="nav-link">🔍 Debug Log</a>
        <a href="#" onclick="openAccountSecurity()" class="nav-link">🔒 Security</a>
        <a href="/admin/logout.php" class="nav-link">🚪 Logout</a>
    </div>
</nav>

<script>
// Navigation toggle functionality
document.addEventListener("DOMContentLoaded", function() {
    const navToggle = document.querySelector(".hamburger");
    const navMenu = document.querySelector(".nav-links.mobile");
    
    if (navToggle && navMenu) {
        navToggle.addEventListener("click", function() {
            navMenu.classList.toggle("active");
            navToggle.classList.toggle("active");
        });
    }
});
</script>

