/**
 * Mobile Optimization JavaScript for ConnectHub
 * Enhances mobile user experience with touch gestures and responsive features
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== MOBILE DETECTION =====
    const isMobile = window.innerWidth <= 768;
    const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    
    // Only apply mobile optimizations on mobile devices
    if (!isMobile) {
        console.log('Desktop detected - skipping mobile optimizations');
        return;
    }
    
    // Add mobile class to body
    if (isMobile) {
        document.body.classList.add('mobile-device');
    }
    
    if (isTouch) {
        document.body.classList.add('touch-device');
    }
    
    // ===== TOUCH GESTURE ENHANCEMENTS =====
    
    // Add ripple effect to buttons on touch
    function addRippleEffect(button) {
        button.addEventListener('touchstart', function(e) {
            if (!button.classList.contains('btn')) return;
            
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            this.appendChild(ripple);
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.touches[0].clientX - rect.left - size / 2;
            const y = e.touches[0].clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    }
    
    // Apply ripple effect to all buttons
    document.querySelectorAll('.btn').forEach(addRippleEffect);
    
    // ===== SWIPE GESTURES =====
    
    let startX, startY, startTime;
    
    document.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        startTime = Date.now();
    });
    
    document.addEventListener('touchend', function(e) {
        if (!startX || !startY) return;
        
        const endX = e.changedTouches[0].clientX;
        const endY = e.changedTouches[0].clientY;
        const endTime = Date.now();
        
        const deltaX = endX - startX;
        const deltaY = endY - startY;
        const deltaTime = endTime - startTime;
        
        // Only trigger swipe if it's fast enough and long enough
        if (deltaTime < 300 && Math.abs(deltaX) > 50 && Math.abs(deltaY) < 100) {
            if (deltaX > 0) {
                // Swipe right - could trigger back navigation
                triggerSwipeRight();
            } else {
                // Swipe left - could trigger forward navigation
                triggerSwipeLeft();
            }
        }
        
        startX = startY = null;
    });
    
    function triggerSwipeRight() {
        // Add custom swipe right logic here
        console.log('Swiped right');
    }
    
    function triggerSwipeLeft() {
        // Add custom swipe left logic here
        console.log('Swiped left');
    }
    
    // ===== VIEWPORT HEIGHT FIX FOR MOBILE BROWSERS =====
    
    function setViewportHeight() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    
    setViewportHeight();
    window.addEventListener('resize', setViewportHeight);
    window.addEventListener('orientationchange', setViewportHeight);
    
    // ===== SCROLL ENHANCEMENTS =====
    
    // Hide/show navbar on scroll ONLY for mobile
    if (isMobile) {
        let lastScrollTop = 0;
        const navbar = document.querySelector('.navbar');
        
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                // Scrolling down
                navbar.style.transform = 'translateY(-100%)';
            } else {
                // Scrolling up
                navbar.style.transform = 'translateY(0)';
            }
            
            lastScrollTop = scrollTop;
        });
        
        // Add transition to navbar
        navbar.style.transition = 'transform 0.3s ease-in-out';
    }
    
    // ===== FORM ENHANCEMENTS =====
    
    // Auto-resize textareas
    document.querySelectorAll('textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
    
    // Improve form validation display
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const invalids = form.querySelectorAll(':invalid');
            if (invalids.length > 0) {
                e.preventDefault();
                
                // Focus first invalid field
                invalids[0].focus();
                
                // Add visual feedback
                invalids[0].classList.add('is-invalid');
                
                // Scroll to first invalid field
                invalids[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });
    });
    
    // ===== LOADING STATES =====
    
    // Add loading state to buttons when clicked
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function() {
            if (this.type === 'submit' || this.classList.contains('loading-on-click')) {
                this.classList.add('loading');
                this.disabled = true;
                
                // Remove loading state after 3 seconds (fallback)
                setTimeout(() => {
                    this.classList.remove('loading');
                    this.disabled = false;
                }, 3000);
            }
        });
    });
    
    // ===== MODAL ENHANCEMENTS =====
    
    // Prevent body scroll when modal is open
    document.addEventListener('show.bs.modal', function() {
        document.body.style.overflow = 'hidden';
    });
    
    document.addEventListener('hidden.bs.modal', function() {
        document.body.style.overflow = '';
    });
    
    // ===== ACCESSIBILITY IMPROVEMENTS =====
    
    // Add focus indicators for keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            document.body.classList.add('keyboard-navigation');
        }
    });
    
    document.addEventListener('mousedown', function() {
        document.body.classList.remove('keyboard-navigation');
    });
    
    // ===== PERFORMANCE OPTIMIZATIONS =====
    
    // Lazy load images (if any)
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // ===== STRIPE PAYMENT FORM MOBILE OPTIMIZATION =====
    
    // Enhance Stripe Elements for mobile
    if (typeof stripe !== 'undefined' && document.getElementById('card-element')) {
        const cardElementOptions = {
            style: {
                base: {
                    fontSize: isMobile ? '16px' : '14px', // Prevent zoom on iOS
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
                invalid: {
                    color: '#dc3545',
                    iconColor: '#dc3545',
                },
            },
        };
        
        // Apply mobile-optimized styles to Stripe Elements
        if (window.cardElement) {
            window.cardElement.update(cardElementOptions);
        }
    }
    
    // ===== OFFLINE SUPPORT =====
    
    // Show offline indicator
    function updateOnlineStatus() {
        const indicator = document.querySelector('.offline-indicator') || createOfflineIndicator();
        
        if (navigator.onLine) {
            indicator.style.display = 'none';
        } else {
            indicator.style.display = 'block';
        }
    }
    
    function createOfflineIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'offline-indicator';
        indicator.innerHTML = '<i class="fas fa-wifi"></i> You are offline';
        indicator.style.cssText = `
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            z-index: 1050;
            display: none;
        `;
        document.body.appendChild(indicator);
        return indicator;
    }
    
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();
    
    // ===== HAPTIC FEEDBACK (if supported) =====
    
    function vibrate(pattern = [10]) {
        if ('vibrate' in navigator) {
            navigator.vibrate(pattern);
        }
    }
    
    // Add haptic feedback to important buttons
    document.querySelectorAll('.btn-primary, .btn-danger').forEach(button => {
        button.addEventListener('click', () => vibrate([10]));
    });
    
    console.log('ConnectHub mobile optimizations loaded');
});

// ===== CSS FOR MOBILE FEATURES =====

// Add CSS for mobile features
const mobileCSS = `
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.4);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .keyboard-navigation *:focus {
        outline: 2px solid #007bff !important;
        outline-offset: 2px;
    }
    
    .mobile-device .btn {
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
    
    .touch-device .card:hover {
        transform: none;
    }
    
    .touch-device .card:active {
        transform: scale(0.98);
    }
    
    @media (max-width: 767px) {
        .table-responsive {
            font-size: 0.9rem;
        }
        
        .modal-dialog {
            margin: 0.5rem;
        }
        
        .navbar-collapse {
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }
    }
`;

const style = document.createElement('style');
style.textContent = mobileCSS;
document.head.appendChild(style);