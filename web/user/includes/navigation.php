<?php
/**
 * User Navigation Component
 * Complete navigation with HTML, CSS, and JavaScript
 */
?>
<nav class="navbar">
    <div class="nav-brand">
        <a href="/user/dashboard.php">📱 ShareMyCard</a>
    </div>
    <button class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <div class="nav-links mobile">
        <a href="/user/dashboard.php" class="nav-link">🏠 Dashboard</a>
        <a href="/user/leads/index.php" class="nav-link">📋 Leads</a>
        <a href="/user/contacts/index.php" class="nav-link">👥 Contacts</a>
        <a href="/user/cards/create.php" class="nav-link">➕ New Card</a>
        <a href="/user/cards/analytics.php" class="nav-link">📊 Analytics</a>
        <a href="/user/cards/invitation-analytics.php" class="nav-link">📧 Invitations</a>
        <a href="/user/qr/" class="nav-link">🔳 Custom QR Codes</a>
        <a href="https://github.com/mwarrick/digital-business-card/issues" class="nav-link" target="_blank">🐛 Report Issues</a>
        <a href="#" onclick="openAccountSecurity()" class="nav-link">🔒 Security</a>
        <a href="/user/logout.php" class="nav-link">🚪 Logout</a>
    </div>
</nav>

<script>
// Mobile navigation toggle - Complete functionality
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const mobileNavLinks = document.querySelector('.nav-links.mobile');
    
    console.log('Hamburger element:', hamburger);
    console.log('Mobile nav links element:', mobileNavLinks);
    
    if (hamburger && mobileNavLinks) {
        hamburger.addEventListener('click', function() {
            console.log('Hamburger clicked');
            // Toggle hamburger animation
            hamburger.classList.toggle('active');
            
            // Toggle mobile menu visibility
            mobileNavLinks.classList.toggle('active');
        });
        
        // Close mobile menu when clicking on a link
        const mobileLinks = mobileNavLinks.querySelectorAll('.nav-link');
        mobileLinks.forEach(link => {
            link.addEventListener('click', function() {
                hamburger.classList.remove('active');
                mobileNavLinks.classList.remove('active');
            });
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!hamburger.contains(event.target) && !mobileNavLinks.contains(event.target)) {
                hamburger.classList.remove('active');
                mobileNavLinks.classList.remove('active');
            }
        });
    } else {
        console.error('Hamburger menu elements not found:', {
            hamburger: hamburger,
            mobileNavLinks: mobileNavLinks
        });
    }
});
</script>
