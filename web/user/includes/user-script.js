// User Navigation Hamburger Menu
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
