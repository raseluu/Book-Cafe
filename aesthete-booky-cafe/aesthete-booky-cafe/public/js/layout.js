// layout.js - Global Header & Footer Injection

document.addEventListener('DOMContentLoaded', () => {
    injectHeader();
    injectFooter();
    highlightActiveLink();
});

function injectHeader() {
    if (document.querySelector('header')) return; // Don't duplicate if already present
    const headerHTML = `
        <div class="container">
            <nav>
                <a href="/index.html" class="logo">AESTHETE.</a>
                <div class="nav-links">
                    <a href="/index.html">Home</a>
                    <a href="/books.html">Books</a>
                    <a href="/menu.html">Menu</a>
                    <a href="/events.html">Events</a>
                    <a href="/contact.html">Contact</a>
                </div>
                <!-- Dynamic Auth Buttons or User Menu -->
                <div id="auth-container">
                    <!-- Injected by app.js based on auth state -->
                    <div class="auth-buttons" id="auth-buttons">
                        <a href="/login.html" class="btn btn-outline" style="padding: 0.5rem 1.2rem; font-size: 0.9rem;">Login</a>
                        <a href="/signup.html" class="btn btn-primary" style="padding: 0.5rem 1.2rem; font-size: 0.9rem;">Sign Up</a>
                    </div>
                     <div class="user-menu" id="user-menu" style="display: none; align-items: center; gap: 1rem;">
                        <a href="/dashboard.html" id="dashboard-link" style="font-weight: 500;">My Dashboard</a>
                        <button id="logout-btn" class="btn btn-outline" style="padding: 0.4rem 1rem; font-size: 0.85rem;">Logout</button>
                    </div>
                </div>
            </nav>
        </div>
    `;

    const header = document.createElement('header');
    header.innerHTML = headerHTML;
    document.body.prepend(header);
}

function injectFooter() {
    // Only inject if not already present (some pages might want custom)
    if (document.querySelector('footer')) return;

    const footerHTML = `
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col branding-col">
                    <h3 class="footer-logo">Aesthete.</h3>
                    <p>Where stories meet flavor. A sanctuary for the modern intellectual.</p>
                    <div class="social-links">
                        <a href="#" class="social-icon" aria-label="Facebook"><i class="fab fa-facebook-f">F</i></a>
                        <a href="#" class="social-icon" aria-label="Instagram"><i class="fab fa-instagram">I</i></a>
                        <a href="#" class="social-icon" aria-label="Twitter"><i class="fab fa-twitter">T</i></a>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Discover</h4>
                    <ul class="footer-links">
                        <li><a href="/books.html">Curated Books</a></li>
                        <li><a href="/menu.html">Artisan Menu</a></li>
                        <li><a href="/events.html">Cultural Events</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Support</h4>
                    <ul class="footer-links">
                        <li><a href="/contact.html">Contact Us</a></li>
                        <li><a href="/faq.html">FAQs</a></li>
                        <li><a href="/terms.html">Terms of Service</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Visit Us</h4>
                    <p class="contact-info">
                        House 42, Road 13/A<br>Banani, Dhaka 1213
                    </p>
                    <p class="contact-info">
                        <span style="display:block; margin-top:0.5rem; color:var(--color-primary);">Open Daily</span>
                        10:00 AM - 10:00 PM
                    </p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2024 Aesthete Book Cafe. All rights reserved.</p>
            </div>
        </div>
    `;

    const footer = document.createElement('footer');
    footer.innerHTML = footerHTML;
    document.body.appendChild(footer);
}

function highlightActiveLink() {
    const path = window.location.pathname;
    const page = path.split('/').pop() || 'index.html';

    // Tiny delay to ensure header is injected
    setTimeout(() => {
        const links = document.querySelectorAll('.nav-links a');
        links.forEach(link => {
            // Check based on end of href string to handle both absolute and relative matching logic conceptually
            if (link.getAttribute('href').endsWith(page)) {
                link.classList.add('active');
            }
        });

        // Re-run Auth check from app.js if it exists, because we just injected the container
        if (window.Auth && typeof window.Auth.updateUI === 'function') {
            window.Auth.updateUI();

            // Re-attach logout listener since we just created the button
            const logoutBtn = document.getElementById('logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.Auth.logout();
                });
            }
        }
    }, 0);
}
