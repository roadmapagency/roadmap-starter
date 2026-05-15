import "./sass/style.scss"
import "./js/navigation"
import "./js/skip-link-focus-fix"
import 'bootstrap';
// import $ from 'jquery';
// let imagesLoaded = require('imagesloaded');
// const images = require.context('./images', true, /\.(jpe?g|png|gif|svg)$/);

document.addEventListener('DOMContentLoaded', function() {
    // Sticky Header with IntersectionObserver
    const header = document.getElementById('masthead');
    
    // Create a sentinel element to detect when we scroll past it
    const sentinel = document.createElement('div');
    sentinel.className = 'header-sentinel';
    // Insert the sentinel before the header
    header.parentNode.insertBefore(sentinel, header);
    
    // Set up the IntersectionObserver
    const observerOptions = {
        rootMargin: document.getElementById('wpadminbar') ? `${document.getElementById('wpadminbar').offsetHeight}px` : '0px',
        threshold: 0 // Trigger as soon as the element is out of view
    };
    
    const observer = new IntersectionObserver((entries) => {
        // We're only observing one element, so we can just use the first entry
        const entry = entries[0];
        if (!entry.isIntersecting) {
            // Sentinel is out of view (scrolled past it), so the header is "stuck"
            header.classList.add('is-stuck');
        } else {
            // Sentinel is in view, header is not "stuck"
            header.classList.remove('is-stuck');
        }
    }, observerOptions);
    
    // Start observing the sentinel
    observer.observe(sentinel);

    // Create a placeholder div for the header
    const headerPlaceholder = document.createElement('div');
    const mainNav = document.getElementById('main-nav');
    headerPlaceholder.className = 'header-placeholder';
    headerPlaceholder.style.height = `${mainNav.offsetHeight}px`;
    mainNav.parentNode.insertBefore(headerPlaceholder, mainNav);

    // Update placeholder height on window resize
    window.addEventListener('resize', () => {
        headerPlaceholder.style.height = `${header.offsetHeight}px`;
    });

    
    // Handle all dropdown toggles
    const primaryMenu = document.getElementById('primary-menu');
    const dropdownToggles = primaryMenu.querySelectorAll('.dropdown-toggle');

    function closeAllDropdowns() {
        const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
        openDropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
            const toggle = dropdown.previousElementSibling;
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function syncDropdownHeights() {
        const primaryMenu = document.getElementById('primary-menu');

        const openDropdowns = primaryMenu.querySelectorAll('.dropdown-menu.show');
        console.log(openDropdowns)
        if (openDropdowns.length > 0) {
            // Reset heights first
            openDropdowns.forEach(dropdown => {
                dropdown.style.height = 'auto';
            });

            // Find the maximum height
            const maxHeight = Math.max(...Array.from(openDropdowns).map(el => el.scrollHeight));

            // Apply the maximum height to all open dropdowns
            openDropdowns.forEach(dropdown => {
                dropdown.style.height = `${maxHeight}px`;
            });
        }
    }

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            // Get all dropdowns at the same level
            const parent = this.closest('.dropdown-menu') || this.closest('.navbar-nav');
            if (parent) {
                const siblings = parent.querySelectorAll('.dropdown-menu.show');
                siblings.forEach(dropdown => {
                    if (dropdown !== this.nextElementSibling) {
                        dropdown.classList.remove('show');
                        const toggle = dropdown.previousElementSibling;
                        if (toggle) {
                            toggle.setAttribute('aria-expanded', 'false');
                        }
                    }
                });

                // If this is a top-level dropdown
                if (!this.closest('.dropdown-menu')) {
                    // Find and show the promo text
                    const promoText = document.querySelector('.promo_text');
                    if (promoText) {
                        promoText.classList.add('show');
                    }
                }
            }

            // Sync heights after a short delay to ensure dropdowns are fully rendered
            setTimeout(syncDropdownHeights, 0);
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown-menu') && !e.target.closest('.dropdown-toggle')) {
            closeAllDropdowns();
        }
    });

    // Close dropdowns when pressing escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllDropdowns();
        }
    });

    // Also sync heights on window resize
    window.addEventListener('resize', syncDropdownHeights);
});

// imagesLoaded.makeJQueryPlugin( $ );
