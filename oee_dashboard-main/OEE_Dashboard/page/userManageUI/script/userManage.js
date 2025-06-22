let allUsers = [];
const API_URL = '../../api/userManage/userManage.php';

function showToast(message, color = '#28a745') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.style.backgroundColor = color;
    toast.style.opacity = 1;
    toast.style.transform = 'translateY(0)';
    setTimeout(() => {
        toast.style.opacity = 0;
        toast.style.transform = 'translateY(20px)';
    }, 3000);
}

async function sendRequest(action, method, body = null, urlParams = {}) {
    try {
        urlParams.action = action;
        const queryString = new URLSearchParams(urlParams).toString();
        const url = `${API_URL}?${queryString}`;
        const options = {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: body ? JSON.stringify(body) : null
        };
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        showToast('An unexpected error occurred.', '#dc3545');
        return { success: false };
    }
}

async function loadUsers() {
    const result = await sendRequest('read', 'GET');
    if (result.success) {
        allUsers = result.data;
        renderTable();
    } else {
        showToast(result.message || 'Failed to load users.', '#dc3545');
    }
}

function renderTable() {
    const tbody = document.getElementById('userTable');
    tbody.innerHTML = '';
    if (!allUsers || allUsers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No users found.</td></tr>';
        return;
    }
    allUsers.forEach(user => {
        const actionsHTML = (isAdmin && user.id !== currentUserId) ? `<td><button class="btn btn-sm btn-warning" onclick='editUser(${JSON.stringify(user)})'>Edit</button> <button class="btn btn-sm btn-danger" onclick='deleteUser(${user.id})'>Delete</button></td>` : '<td></td>';
        tbody.innerHTML += `<tr data-id="${user.id}"><td>${user.id}</td><td>${user.username}</td><td>${user.role}</td><td>${user.created_at || 'N/A'}</td>${actionsHTML}</tr>`;
    });
}

async function deleteUser(id) {
    if (!confirm(`Are you sure you want to delete user ID ${id}?`)) return;
    const result = await sendRequest('delete', 'GET', null, { id });
    showToast(result.message, result.success ? '#28a745' : '#dc3545');
    if (result.success) loadUsers();
}

async function loadLogs() {
    const tbody = document.getElementById('logTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';
    try {
        const result = await sendRequest('logs', 'GET');
        if (result.success) {
            tbody.innerHTML = '';
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No logs found.</td></tr>';
                return;
            }
            result.data.forEach(log => {
                tbody.innerHTML += `<tr><td>${log.id}</td><td>${log.action_by}</td><td>${log.action_type}</td><td>${log.target_user || '-'}</td><td>${log.detail || '-'}</td><td>${log.created_at}</td></tr>`;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${result.message}</td></tr>`;
        }
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Failed to load logs.</td></tr>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
});