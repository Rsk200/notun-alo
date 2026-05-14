// ============================================
// animations.js - Global Animation System
// THE INFINITY AI BUILDFEST 2026
// ============================================


document.addEventListener('DOMContentLoaded', () => {


    // 1. Preloader Hide Logic
    const preloader = document.getElementById('preloader');
    if (preloader) {
        setTimeout(() => {
            preloader.classList.add('preloader--done');
        }, 1500);
    }


    // 2. Scroll Reveal Observer (data-reveal)
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((e, i) => {
            if (e.isIntersecting) {
                // Stagger delay for multiple items entering at once
                e.target.style.transitionDelay = (i * 0.08) + 's';
                e.target.classList.add('reveal--visible');
                // Only reveal once
                revealObserver.unobserve(e.target);
            }
        });
    }, { threshold: 0.12 });


    document.querySelectorAll('[data-reveal]').forEach(el => {
        // Initially hide for reveal effect
        el.classList.add('reveal-hidden');
        revealObserver.observe(el);
    });


    // 3. Counter Animation (Stats)
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const targetVal = parseInt(counter.getAttribute('data-target') || counter.innerText.replace(/,/g, ''), 10);
                if (isNaN(targetVal)) return;


                let startVal = 0;
                const duration = 1200; // 1.2s
                const startTime = performance.now();


                function updateCounter(currentTime) {
                    const elapsedTime = currentTime - startTime;
                    const progress = Math.min(elapsedTime / duration, 1);

                    // easeOutCubic
                    const easeOut = 1 - Math.pow(1 - progress, 3);

                    const currentVal = Math.floor(easeOut * targetVal);
                    counter.innerText = currentVal.toLocaleString();


                    if (progress < 1) {
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.innerText = targetVal.toLocaleString();
                    }
                }

                requestAnimationFrame(updateCounter);
                counterObserver.unobserve(counter);
            }
        });
    }, { threshold: 0.5 });


    document.querySelectorAll('.stat-counter').forEach(el => {
        counterObserver.observe(el);
    });


    // 4. Navbar Scroll Behavior
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('navbar--scrolled');
            } else {
                navbar.classList.remove('navbar--scrolled');
            }
        });
    }


    // 5. Page Fade Transitions
    const body = document.body;
    // Fade in on load
    body.classList.add('page-transition-enter-active');
    setTimeout(() => body.classList.remove('page-transition-enter-active', 'page-transition-enter'), 300);


    // Intercept link clicks
    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', (e) => {
            // Ignore if external, hash, or target blank
            const href = link.getAttribute('href');
            if (!href || href.startsWith('http') || href.startsWith('#') || link.getAttribute('target') === '_blank') return;
            // Ignore if same page query string like ?lang=bn
            if (href.startsWith('?')) return;


            e.preventDefault();
            body.classList.add('page-transition-exit');
            setTimeout(() => {
                window.location.href = href;
            }, 250); // wait for 0.25s fade out
        });
    });


});


// 6. Global Toast System
window.showToast = function (message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();

    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;

    const icon = type === 'success' ? '✅' : (type === 'error' ? '❌' : 'ℹ️');

    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-icon">${icon}</span>
            <span class="toast-message">${message}</span>
        </div>
        <div class="toast-progress"></div>
    `;

    toastContainer.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        toast.classList.add('toast--show');
    });

    // Auto dismiss after 4s
    setTimeout(() => {
        toast.classList.remove('toast--show');
        setTimeout(() => toast.remove(), 400); // wait for exit animation
    }, 4000);
};


function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}





