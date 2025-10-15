<header class="admin-header">
    <div class="header-content">
        <a href="/admin/dashboard.php" class="header-logo">📱 ShareMyCard Admin</a>
        
        <nav class="header-nav">
            <a href="/admin/dashboard.php">Dashboard</a>
            <a href="/admin/users.php">Users</a>
            <a href="/admin/cards.php">All Business Cards</a>
            <a href="/admin/my-cards.php">My Cards</a>
            <a href="/admin/debug-log.php" title="Debug Log">🔍</a>
            <div class="header-user">
                <span><?php echo htmlspecialchars(AdminAuth::getEmail()); ?></span>
                <a href="/admin/logout.php">Logout</a>
            </div>
        </nav>
    </div>
</header>

