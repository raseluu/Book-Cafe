// app.js

// Auth State Management
const Auth = {
    isLoggedIn: () => {
        return !!localStorage.getItem('user');
    },
    getUser: () => {
        try {
            return JSON.parse(localStorage.getItem('user'));
        } catch (e) {
            return null;
        }
    },
    login: (user) => {
        localStorage.setItem('user', JSON.stringify(user));
        Auth.updateUI();
        Toast.show('Welcome back, ' + user.name + '!', 'success');
    },
    logout: () => {
        localStorage.removeItem('user');
        Auth.updateUI();
        Toast.show('Logged out successfully', 'success');
        setTimeout(() => window.location.href = '/', 1000);
    },
    updateUI: () => {
        const authBtns = document.getElementById('auth-buttons');
        const userMenu = document.getElementById('user-menu');
        const dashboardLink = document.getElementById('dashboard-link');

        // Some pages might not have these elements (e.g. clean login page)
        if (authBtns && userMenu) {
            if (Auth.isLoggedIn()) {
                authBtns.style.display = 'none';
                userMenu.style.display = 'flex'; // Flex for gap

                // Dynamic Dashboard Link
                const user = Auth.getUser();
                if (dashboardLink && user) {
                    if (user.role === 'admin') {
                        dashboardLink.href = '/admin/dashboard.html';
                    } else {
                        dashboardLink.href = '/dashboard.html';
                    }
                }
            } else {
                authBtns.style.display = 'flex';
                userMenu.style.display = 'none';
            }
        }
    }
};

window.Auth = Auth;

// Toast Notifications
const Toast = {
    show: (message, type = 'default') => {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;

        document.body.appendChild(toast);

        // Trigger reflow
        toast.offsetHeight;

        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 400); // Wait for transition
        }, 3000);
    }
};

// Global Helpers
function handleImageError(img) {
    // High quality themed fallback
    img.src = 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&q=80&w=800';
    img.onerror = null; // Prevent infinite loop
}

document.addEventListener('DOMContentLoaded', () => {
    Auth.updateUI();

    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            Auth.logout();
        });
    }
});

// Auth Handlers (Global)
async function handleLogin(e) {
    if (e) e.preventDefault();
    const email = document.getElementById('l-email').value;
    const password = document.getElementById('l-pass').value;
    const btn = e.target.querySelector('button');

    try {
        btn.textContent = 'Logging in...';
        btn.disabled = true;

        const res = await fetch('/api/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();

        if (res.ok) {
            Auth.login(data.user);
            if (typeof closeModal === 'function') closeModal('auth-modal');
            // Refresh logic if on event page to update button state
            if (typeof loadEvent === 'function' && currentEvent) {
                // Re-evaluate button state
                const bBtn = document.querySelector('.booking-card .btn');
                if (bBtn) {
                    bBtn.click(); // Re-trigger initiate to open booking modal now that we are logged in
                }
            } else {
                location.reload(); // Simple reload to reflect state if not single-page aware
            }
        } else {
            alert(data.message || 'Login failed');
        }
    } catch (err) {
        console.error(err);
        alert('An error occurred');
    } finally {
        btn.textContent = 'Login to Continue';
        btn.disabled = false;
    }
}

async function handleSignup(e) {
    if (e) e.preventDefault();
    const name = document.getElementById('s-name').value;
    const email = document.getElementById('s-email').value;
    const password = document.getElementById('s-pass').value;
    const btn = e.target.querySelector('button');

    try {
        btn.textContent = 'Creating Account...';
        btn.disabled = true;

        const res = await fetch('/api/register', {
            method: 'POST',
            body: JSON.stringify({ name, email, password })
        });
        const data = await res.json();

        if (res.ok) {
            // alert('Registration successful! Please check your email for the verification code (Printed in server logs/debug_log.txt for local dev).');
            // Switch to Verify
            if (document.getElementById('verify-form-pop')) {
                document.getElementById('signup-form-pop').style.display = 'none';
                document.getElementById('verify-form-pop').style.display = 'block';
                document.getElementById('v-email').value = email;
                alert('Registration successful! Please enter the code sent to your email.');
            } else {
                if (typeof switchAuth === 'function') switchAuth('login');
                alert('Registration successful! Please login.');
            }
        } else {
            alert(data.message || 'Registration failed');
        }
    } catch (err) {
        console.error(err);
        alert('An error occurred');
    } finally {
        btn.textContent = 'Create Account';
        btn.disabled = false;
    }
}

async function handleVerify(e) {
    if (e) e.preventDefault();
    const email = document.getElementById('v-email').value;
    const code = document.getElementById('v-code').value;
    const btn = e.target.querySelector('button');

    try {
        btn.textContent = 'Verifying...';
        btn.disabled = true;

        const res = await fetch('/api/verify', {
            method: 'POST',
            body: JSON.stringify({ email, code })
        });
        const data = await res.json();

        if (res.ok) {
            alert('Account Verified! Please login.');
            document.getElementById('verify-form-pop').style.display = 'none';
            document.getElementById('login-form-pop').style.display = 'block';
            document.getElementById('l-email').value = email;
            // Focus on password
            document.getElementById('l-pass').focus();

            // Sync tabs if function exists
            if (typeof switchAuth === 'function') switchAuth('login');

        } else {
            alert(data.message || 'Verification failed');
        }
    } catch (err) {
        console.error(err);
        alert('An error occurred');
    } finally {
        btn.textContent = 'Verify Account';
        btn.disabled = false;
    }
}

