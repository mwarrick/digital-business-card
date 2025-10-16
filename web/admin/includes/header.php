<header class="admin-header">
    <div class="header-content">
        <a href="/admin/dashboard.php" class="header-logo">📱 ShareMyCard Admin</a>
        
        <nav class="header-nav">
            <a href="/admin/dashboard.php" title="Dashboard">🏠</a>
            <a href="/admin/analytics.php" title="Global Analytics">📊</a>
            <a href="/admin/users.php" title="Users">👥</a>
            <a href="/admin/cards.php" title="All Business Cards">📇</a>
            <a href="/admin/my-cards.php" title="My Cards">📱</a>
            <a href="/admin/debug-log.php" title="Debug Log">🔍</a>
            <div class="header-user">
                <span class="user-email"><?php echo htmlspecialchars(AdminAuth::getEmail()); ?></span>
                <a href="#" onclick="openAccountSecurity()" title="Account Security">🔒</a>
                <a href="/admin/logout.php" title="Logout">🚪</a>
            </div>
        </nav>
    </div>
</header>

