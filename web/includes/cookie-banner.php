<!-- Cookie Consent Banner -->
<div id="cookie-banner" style="display: none;">
    <div class="cookie-content">
        <p>
            <strong>🍪 Cookie Notice</strong><br>
            We use cookies to provide analytics about card engagement and improve your experience. 
            <a href="/privacy.php" target="_blank" style="color: #667eea; text-decoration: underline;">Learn more</a>
        </p>
        <div class="cookie-actions">
            <button onclick="acceptCookies()" class="cookie-btn accept">Accept</button>
            <button onclick="declineCookies()" class="cookie-btn decline">Decline</button>
        </div>
    </div>
</div>

<style>
    #cookie-banner {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        z-index: 10000;
        animation: slideUp 0.3s ease-out;
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(100%);
        }
        to {
            transform: translateY(0);
        }
    }
    
    .cookie-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .cookie-content p {
        margin: 0;
        flex: 1;
        min-width: 250px;
        color: #333;
        line-height: 1.6;
    }
    
    .cookie-actions {
        display: flex;
        gap: 10px;
    }
    
    .cookie-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .cookie-btn.accept {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .cookie-btn.accept:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .cookie-btn.decline {
        background: #f0f0f0;
        color: #666;
    }
    
    .cookie-btn.decline:hover {
        background: #e0e0e0;
    }
</style>

<script>
    // Check if user has already made a choice
    function checkCookieConsent() {
        const consent = localStorage.getItem('analytics_consent');
        if (consent === null) {
            // No choice made yet - show banner
            document.getElementById('cookie-banner').style.display = 'block';
        }
        return consent === 'accepted';
    }
    
    function acceptCookies() {
        localStorage.setItem('analytics_consent', 'accepted');
        document.getElementById('cookie-banner').style.display = 'none';
    }
    
    function declineCookies() {
        localStorage.setItem('analytics_consent', 'declined');
        document.getElementById('cookie-banner').style.display = 'none';
    }
    
    // Show banner on page load if needed
    document.addEventListener('DOMContentLoaded', checkCookieConsent);
</script>

