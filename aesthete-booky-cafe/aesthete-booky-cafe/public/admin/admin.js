document.addEventListener('DOMContentLoaded', () => {
    // Auth Check
    const user = Auth.getUser();
    if (!user || user.role !== 'admin') {
        window.location.href = '../login.html';
        return;
    }

    document.getElementById('admin-logout').addEventListener('click', () => Auth.logout());
    document.getElementById('crud-form').addEventListener('submit', handleCrudSubmit);

    document.getElementById('admin-logout').addEventListener('click', () => Auth.logout());
    document.getElementById('crud-form').addEventListener('submit', handleCrudSubmit);

    (async () => {
        try {
            console.log("Loading Admin Data...");
            await Promise.all([
                loadUsers(),
                loadBooks(),
                loadMenu(),
                loadEvents(),
                loadRequests(),
                loadSettings()
            ]);
            console.log("Admin Data Loaded Successfully");
        } catch (e) {
            console.error("Critical Error Loading Admin Data:", e);
            alert("Error loading dashboard data. Check console for details.");
        }
    })();

    document.getElementById('admin-settings-form').addEventListener('submit', handleSettingsSubmit);
});

// --- SETTINGS ---
function loadSettings() {
    const user = Auth.getUser();
    if (user) {
        document.getElementById('set-name').value = user.name || '';
        document.getElementById('set-email').value = user.email || '';
        document.getElementById('set-phone').value = user.phone || '';
    }
}

async function handleSettingsSubmit(e) {
    e.preventDefault();
    const user = Auth.getUser();
    const name = document.getElementById('set-name').value;
    const phone = document.getElementById('set-phone').value;
    const newPass = document.getElementById('set-new-pass').value;

    const payload = {
        id: user.id,
        name: name,
        phone: phone,
        email: user.email // Required for validation in backend logic if strict
    };

    if (newPass) {
        payload.new_password = newPass;
    }

    try {
        const res = await fetch('/api/update-profile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (res.ok && data.user) {
            Auth.login(data.user); // Update local storage
            Toast.show('Settings updated successfully', 'success');
            document.getElementById('set-new-pass').value = ''; // Clear password field
            loadSettings(); // Refresh UI
        } else {
            Toast.show(data.message || 'Update failed', 'error');
        }
    } catch (err) {
        console.error(err);
        Toast.show('Network error', 'error');
    }
}

// --- REQUESTS ---
async function loadRequests() {
    const res = await fetch('/api/admin_cancellations');
    const requests = await res.json();
    const tbody = document.getElementById('requests-table-body');
    const badge = document.getElementById('req-badge');
    tbody.innerHTML = '';

    if (requests.length > 0) {
        badge.style.display = 'inline-block';
        badge.innerText = requests.length;
    } else {
        badge.style.display = 'none';
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#999;">No pending requests</td></tr>';
    }

    requests.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <b>${r.user_name}</b><br>
                <small>${r.user_email}</small>
            </td>
            <td>${r.event_title}</td>
            <td style="max-width:250px; color:#555;"><i>"${r.cancellation_reason}"</i></td>
            <td>${r.guests}</td>
            <td>
                <button class="btn btn-primary btn-sm" onclick="handleRequest(${r.registration_id}, 'approve')">Approve</button>
                <button class="btn btn-outline btn-sm" style="color:red; border-color:red;" onclick="handleRequest(${r.registration_id}, 'reject')">Reject</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

async function handleRequest(id, action) {
    if (!confirm(action === 'approve' ? 'Approve cancellation and free up seats?' : 'Reject cancellation request?')) return;

    await fetch('/api/admin_cancellations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ registration_id: id, action: action })
    });

    Toast.show('Processed', 'success');
    loadRequests();
    loadEvents(); // Update capacity counts
}

function showTab(tab) {
    document.querySelectorAll('.tab-section').forEach(el => el.style.display = 'none');
    document.getElementById('tab-' + tab).style.display = 'block';

    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    event.currentTarget.classList.add('active');
}

// --- USERS ---
async function loadUsers() {
    const res = await fetch('/api/admin_users');
    const users = await res.json();
    const tbody = document.getElementById('users-table-body');
    tbody.innerHTML = '';

    users.forEach(u => {
        const isBanned = u.is_banned === '1';
        const displayId = u.member_id || ('Aesthetic' + String(u.id).padStart(5, '0'));
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span style="font-family:monospace; font-weight:bold; color:#555;">${displayId}</span></td>
            <td>${u.name}</td>
            <td>${u.email}</td>
            <td>${u.phone || '<span style="color:#ccc;">-</span>'}</td>
            <td><span style="font-size:0.8rem; font-weight:bold; color:var(--color-primary);">${u.role}</span></td>
            <td>
                <span class="status-badge ${isBanned ? 'banned' : 'active'}">
                    ${isBanned ? 'Banned' : 'Active'}
                </span>
            </td>
            <td>
            <td>
                ${u.role !== 'admin' ? `
                    <button class="btn-icon" title="Edit" onclick='openUserModal(${JSON.stringify(u)})'>✏️</button>
                    <button class="btn-icon" title="${isBanned ? 'Unban' : 'Ban'}" onclick="toggleBan(${u.id}, ${isBanned})">
                        ${isBanned ? '🔓' : '🚫'}
                    </button>
                    <button class="btn-icon delete" title="Delete" onclick="deleteUser(${u.id})">🗑️</button>
                ` : '<span style="color:#999; font-size:0.8em;">(Superuser)</span>'}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

async function toggleBan(id, currentStatus) {
    if (!confirm(currentStatus ? 'Unban this user?' : 'Ban this user?')) return;

    await fetch('/api/admin_ban_user', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, is_banned: !currentStatus })
    });
    loadUsers();
}

async function deleteUser(id) {
    if (!confirm('Permanently delete this user?')) return;
    await fetch(`/api/admin_users?id=${id}`, { method: 'DELETE' });
    loadUsers();
}

// --- BOOKS ---
async function loadBooks() {
    const res = await fetch('/api/admin_books');
    const books = await res.json();
    const tbody = document.getElementById('books-table-body');
    tbody.innerHTML = '';

    books.forEach(b => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><img src="${b.image && b.image.startsWith('http') ? b.image : '../' + (b.image || 'images/placeholder.jpg')}" alt=""></td>
            <td><b>${b.title}</b></td>
            <td>${b.author}</td>
            <td>৳${b.price}</td>
            <td>
                <button class="btn-icon" onclick='openBookModal(${JSON.stringify(b)})'>✏️</button>
                <button class="btn-icon delete" onclick="deleteBook(${b.id})">🗑️</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

async function deleteBook(id) {
    if (!confirm('Delete book?')) return;
    await fetch(`/api/admin_books?id=${id}`, { method: 'DELETE' });
    loadBooks();
}

// --- MENU ---
async function loadMenu() {
    const res = await fetch('/api/admin_menu');
    const items = await res.json();
    const tbody = document.getElementById('menu-table-body');
    tbody.innerHTML = '';

    items.forEach(m => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><img src="${m.image && m.image.startsWith('http') ? m.image : '../' + (m.image || 'images/placeholder.jpg')}" alt=""></td>
            <td><b>${m.name}</b></td>
            <td>${m.category}</td>
            <td>৳${m.price}</td>
            <td>
                <button class="btn-icon" onclick='openMenuModal(${JSON.stringify(m)})'>✏️</button>
                <button class="btn-icon delete" onclick="deleteMenu(${m.id})">🗑️</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

async function deleteMenu(id) {
    if (!confirm('Delete menu item?')) return;
    await fetch(`/api/admin_menu?id=${id}`, { method: 'DELETE' });
    loadMenu();
}

// --- EVENTS ---
async function loadEvents() {
    const res = await fetch('/api/admin_events');
    const events = await res.json();
    const tbody = document.getElementById('events-table-body');
    tbody.innerHTML = '';

    events.forEach(e => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><img src="${e.image && e.image.startsWith('http') ? e.image : '../' + (e.image || 'images/placeholder.jpg')}" alt=""></td>
            <td><b>${e.title}</b></td>
            <td>${new Date(e.date).toLocaleDateString()}</td>
            <td><span class="status-badge active">${e.booked || 0} / ${e.capacity || 50}</span></td>
            <td>
                <a href="../event-details.html?id=${e.id}" target="_blank" class="btn-icon" title="View Public Page">👁️</a>
                <button class="btn-icon" title="View Attendees" onclick="openAttendees(${e.id})">👥</button>
                <button class="btn-icon" onclick='openEventModal(${JSON.stringify(e)})'>✏️</button>
                <button class="btn-icon delete" onclick="deleteEvent(${e.id})">🗑️</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

let currentEventIdForAttendees = null;

async function openAttendees(eventId) {
    currentEventIdForAttendees = eventId;
    const modal = document.getElementById('attendees-modal');
    modal.style.display = 'flex';
    document.getElementById('add-attendee-form').style.display = 'none'; // Hide form on open

    await loadAttendeesList(eventId);
}

async function loadAttendeesList(eventId) {
    try {
        const tbody = document.getElementById('attendees-table-body');
        const res = await fetch(`/api/admin_event_registrations?id=${eventId}`);
        const data = await res.json();
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4">No attendees yet.</td></tr>';
            return;
        }

        data.forEach(at => {
            const tr = document.createElement('tr');
            let actionBtn = '';

            if (at.status === 'waitlist') {
                actionBtn = `
                    <button class="btn btn-primary btn-sm" onclick="adminApproveBooking(${at.id}, ${eventId})">Approve</button>
                    <button class="btn btn-outline btn-sm" style="color:red; border-color:red;" onclick="adminCancelBooking(${at.id}, ${eventId})">Cancel</button>
                `;
            } else if (at.status !== 'cancelled') {
                actionBtn = `<button class="btn btn-outline btn-sm" style="color:red; border-color:red;" onclick="adminCancelBooking(${at.id}, ${eventId})">Cancel</button>`;
            } else {
                actionBtn = `<span style="color:#999;">Cancelled</span>`;
            }

            const phoneDisplay = at.phone ? `<div style="color:#666; font-size:0.9em; margin-top:2px;">📞 ${at.phone}</div>` : '';

            tr.innerHTML = `
                <td>
                    <b>${at.name}</b><br>
                    <small style="color:#888;">${at.email}</small>
                    ${phoneDisplay}
                </td>
                <td>${at.guests}</td>
                <td>
                    <span class="status-badge ${at.status === 'confirmed' ? 'active' : (at.status === 'waitlist' ? 'orange' : 'banned')}" 
                          style="${at.status === 'waitlist' ? 'background:#fff3cd; color:#856404;' : ''}">
                        ${at.status}
                    </span>
                    ${at.cancelled_by === 'admin' ? '<br><small>(By Admin)</small>' : ''}
                </td>
                <td>${actionBtn}</td>
            `;
            tbody.appendChild(tr);
        });
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="4" style="color:red;">Error loading data</td></tr>';
    }
}

async function adminApproveBooking(regId, eventId) {
    if (!confirm('Approve this waitlisted booking and confirm seats?')) return;
    try {
        const res = await fetch('/api/admin_approve_booking', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ registration_id: regId })
        });
        if (res.ok) {
            Toast.show('Booking approved', 'success');
            loadAttendeesList(eventId);
            loadEvents();
        } else {
            const data = await res.json();
            alert(data.message || 'Failed');
        }
    } catch (e) { console.error(e); }
}

function showAddAttendeeForm() {
    const form = document.getElementById('add-attendee-form');
    // Toggle
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

async function submitManualAttendee() {
    const name = document.getElementById('manual-name').value;
    const email = document.getElementById('manual-email').value;
    const phone = document.getElementById('manual-phone').value;
    const guests = document.getElementById('manual-guests').value;

    if (!name || !email) {
        alert('Name and Email are required');
        return;
    }

    try {
        const res = await fetch('/api/admin_add_booking', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                event_id: currentEventIdForAttendees,
                name, email, phone, guests
            })
        });
        const data = await res.json();
        if (res.ok) {
            Toast.show('Attendee added', 'success');
            // Clear inputs
            document.getElementById('manual-name').value = '';
            document.getElementById('manual-email').value = '';
            document.getElementById('manual-phone').value = '';
            document.getElementById('add-attendee-form').style.display = 'none';
            loadAttendeesList(currentEventIdForAttendees);
            loadEvents(); // Update capacity
        } else {
            alert(data.message || 'Failed');
        }
    } catch (e) { console.error(e); }
}

async function cancelAllAttendees() {
    if (!confirm('Are you sure you want to CANCEL ALL bookings for this event? This cannot be undone.')) return;

    const reason = prompt("Enter cancellation reason for all users:");
    if (!reason) return;

    try {
        const res = await fetch('/api/admin_cancel_all_bookings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                event_id: currentEventIdForAttendees,
                reason: reason
            })
        });

        if (res.ok) {
            Toast.show('All bookings cancelled', 'success');
            loadAttendeesList(currentEventIdForAttendees);
            loadEvents();
        } else {
            alert('Failed to process batch cancellation');
        }
    } catch (e) { console.error(e); }
}

async function adminCancelBooking(regId, eventId) {
    const reason = prompt("Enter cancellation reason (visible to user):");
    if (!reason) return;

    try {
        const res = await fetch('/api/admin_cancel_booking', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ registration_id: regId, reason: reason })
        });
        if (res.ok) {
            Toast.show('Booking cancelled', 'success');
            openAttendees(eventId); // Refresh list
        } else {
            Toast.show('Failed', 'error');
        }
    } catch (e) { console.error(e); }
}

async function deleteEvent(id) {
    if (!confirm('Delete event?')) return;
    await fetch(`/api/admin_events?id=${id}`, { method: 'DELETE' });
    loadEvents();
}

// --- MODAL & FORMS ---
function closeModal() {
    document.getElementById('crud-modal').style.display = 'none';
}

function resetForm() {
    document.getElementById('crud-form').reset();
    document.getElementById('crud-id').value = '';
    document.getElementById('crud-image-url').value = '';
    document.getElementById('crud-preview').innerHTML = '<span>No image selected</span>';

    // Reset specific fields visibility just in case
    document.querySelector('.form-group:has(#crud-image-url)').style.display = 'block';
    document.querySelector('.form-group:has(#crud-desc)').style.display = 'block';
    const userEls = ['grp-email', 'grp-password', 'grp-role'];
    userEls.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}

function openBookModal(book = null) {
    resetForm();
    document.getElementById('crud-type').value = 'book';
    document.getElementById('modal-title').innerText = book ? 'Edit Book' : 'Add New Book';
    document.getElementById('crud-modal').style.display = 'flex';

    // Visibility
    document.getElementById('grp-author').style.display = 'block';
    document.getElementById('grp-price').style.display = 'block';
    document.getElementById('grp-date').style.display = 'none';
    document.getElementById('grp-location').style.display = 'none';

    if (book) {
        document.getElementById('crud-id').value = book.id;
        document.getElementById('crud-title').value = book.title;
        document.getElementById('crud-author').value = book.author;
        document.getElementById('crud-price').value = book.price;
        document.getElementById('crud-desc').value = book.description;
        document.getElementById('crud-image-url').value = book.image;
        if (book.image) showPreview(book.image);
    }
}

function openEventModal(event = null) {
    resetForm();
    document.getElementById('crud-type').value = 'event';
    document.getElementById('modal-title').innerText = event ? 'Edit Event' : 'Add New Event';
    document.getElementById('crud-modal').style.display = 'flex';

    // Visibility
    document.getElementById('grp-author').style.display = 'none';
    document.getElementById('grp-price').style.display = 'none';
    document.getElementById('grp-date').style.display = 'block';
    document.getElementById('grp-location').style.display = 'block';
    document.getElementById('grp-capacity').style.display = 'block';

    if (event) {
        document.getElementById('crud-id').value = event.id;
        document.getElementById('crud-title').value = event.title;
        document.getElementById('crud-date').value = event.date;
        document.getElementById('crud-location').value = event.location;
        document.getElementById('crud-capacity').value = event.capacity || 50;
        document.getElementById('crud-desc').value = event.description;
        document.getElementById('crud-image-url').value = event.image;
        if (event.image) showPreview(event.image);
    }
}

function openUserModal(user = null) {
    resetForm();
    document.getElementById('crud-type').value = 'user';
    document.getElementById('modal-title').innerText = user ? 'Edit User' : 'Add New User';
    document.getElementById('crud-modal').style.display = 'flex';

    // Visibility
    const els = ['grp-author', 'grp-category', 'grp-price', 'grp-date', 'grp-location', 'grp-capacity', 'grp-email', 'grp-phone', 'grp-password', 'grp-role'];
    els.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    document.getElementById('grp-email').style.display = 'block';
    document.getElementById('grp-phone').style.display = 'block';

    // For edit, password is optional (change only)
    document.querySelector('#grp-password label').innerText = user ? 'New Password (Optional)' : 'Password';
    document.getElementById('crud-password').placeholder = user ? 'Leave blank to keep' : '';
    document.getElementById('grp-password').style.display = 'block';

    document.getElementById('grp-role').style.display = 'block';

    // Hide Image Upload for User
    document.querySelector('.form-group:has(#crud-image-url)').style.display = 'none';
    document.querySelector('.form-group:has(#crud-desc)').style.display = 'none';

    if (user) {
        document.getElementById('crud-id').value = user.id;
        document.getElementById('crud-title').value = user.name; // 'crud-title' maps to 'name' in handleCrudSubmit
        document.getElementById('crud-email').value = user.email;
        document.getElementById('crud-phone').value = user.phone || '';
        document.getElementById('crud-role').value = user.role;
    }
}

function openMenuModal(item = null) {
    resetForm();
    document.getElementById('crud-type').value = 'menu';
    document.getElementById('modal-title').innerText = item ? 'Edit Menu Item' : 'Add New Menu Item';
    document.getElementById('crud-modal').style.display = 'flex';

    // Reset standard fields visibility
    document.querySelector('.form-group:has(#crud-image-url)').style.display = 'block';
    document.querySelector('.form-group:has(#crud-desc)').style.display = 'block';

    // Visibility
    document.getElementById('grp-author').style.display = 'none';
    document.getElementById('grp-category').style.display = 'block';
    document.getElementById('grp-price').style.display = 'block';
    document.getElementById('grp-date').style.display = 'none';
    document.getElementById('grp-location').style.display = 'none';
    document.getElementById('grp-capacity').style.display = 'none';

    // Hide user fields
    document.getElementById('grp-email').style.display = 'none';
    document.getElementById('grp-password').style.display = 'none';
    document.getElementById('grp-role').style.display = 'none';

    if (item) {
        document.getElementById('crud-id').value = item.id;
        document.getElementById('crud-title').value = item.name;
        document.getElementById('crud-category').value = item.category;
        document.getElementById('crud-price').value = item.price;
        document.getElementById('crud-desc').value = item.description;
        document.getElementById('crud-image-url').value = item.image;
        if (item.image) showPreview(item.image);
    }
}

function showPreview(url) {
    const finalUrl = url.startsWith('http') ? url : '../' + url;
    document.getElementById('crud-preview').innerHTML = `<img src="${finalUrl}">`;
}

async function previewImage(input) {
    if (input.files && input.files[0]) {
        const formData = new FormData();
        formData.append('image', input.files[0]);

        Toast.show('Uploading image...', 'default');

        try {
            const res = await fetch('/api/admin_upload', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (res.ok && data.url) {
                document.getElementById('crud-image-url').value = data.url;
                showPreview(data.url);
                Toast.show('Image uploaded!', 'success');
            } else {
                Toast.show('Upload failed', 'error');
            }
        } catch (e) {
            console.error(e);
            Toast.show('Upload error', 'error');
        }
    }
}

async function handleCrudSubmit(e) {
    e.preventDefault();
    const type = document.getElementById('crud-type').value;
    const id = document.getElementById('crud-id').value;
    const isEdit = !!id;

    const payload = {
        title: document.getElementById('crud-title').value,
        description: document.getElementById('crud-desc').value,
        image: document.getElementById('crud-image-url').value
    };

    if (type === 'book') {
        payload.author = document.getElementById('crud-author').value;
        payload.price = document.getElementById('crud-price').value;
    } else if (type === 'menu') {
        payload.name = payload.title; // Map title input to name
        delete payload.title;
        payload.category = document.getElementById('crud-category').value;
        payload.price = document.getElementById('crud-price').value;
    } else if (type === 'user') {
        payload.name = payload.title; // Map title input to name
        delete payload.title;
        payload.email = document.getElementById('crud-email').value;
        payload.phone = document.getElementById('crud-phone').value;
        payload.password = document.getElementById('crud-password').value;
        payload.role = document.getElementById('crud-role').value;
        // Clean up unused
        delete payload.description;
        delete payload.image;
    } else {
        payload.date = document.getElementById('crud-date').value;
        payload.location = document.getElementById('crud-location').value;
        payload.capacity = document.getElementById('crud-capacity').value;
    }

    if (isEdit) payload.id = id;

    let endpoint = '/api/admin_books';
    if (type === 'menu') endpoint = '/api/admin_menu';
    if (type === 'event') endpoint = '/api/admin_events';
    if (type === 'user') endpoint = '/api/admin_users';

    const method = isEdit ? 'PUT' : 'POST';

    try {
        const res = await fetch(endpoint, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (res.ok) {
            Toast.show('Saved successfully!', 'success');
            closeModal();
            if (type === 'book') loadBooks();
            if (type === 'menu') loadMenu();
            if (type === 'event') loadEvents();
            if (type === 'user') loadUsers();
        } else {
            const data = await res.json();
            Toast.show(data.message || 'Failed to save', 'error');
        }
    } catch (err) {
        console.error(err);
        Toast.show('Network error', 'error');
    }
}
