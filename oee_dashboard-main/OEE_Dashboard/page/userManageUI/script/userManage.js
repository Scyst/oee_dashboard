let allUsers = [];
const API_URL = '../../api/userManage/userManage.php';

async function sendRequest(action, method, body = null, urlParams = {}) {
    try {
        urlParams.action = action;
        const queryString = new URLSearchParams(urlParams).toString();
        const url = `${API_URL}?${queryString}`;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const options = {
            method,
            headers: { 
            },
            body: body ? JSON.stringify(body) : null
        };

        if (body) {
            options.headers['Content-Type'] = 'application/json';
        }

        if (method.toUpperCase() !== 'GET' && csrfToken) {
            options.headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        showToast('An unexpected error occurred.', '#dc3545');
        return { success: false };
    }
}

async function loadUsers() {
    const result = await sendRequest('read', 'GET');
    if (result && result.success) {
        allUsers = result.data;
        renderTable();
    } else {
        showToast(result?.message || 'Failed to load users.', '#dc3545');
    }
}

function renderTable() {
    const tbody = document.getElementById('userTable');
    tbody.innerHTML = '';
    if (!allUsers || allUsers.length === 0) {
        const colSpan = canManage ? 5 : 4;
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center">No users found.</td></tr>`;
        return;
    }

    allUsers.forEach(user => {
        const tr = document.createElement('tr');
        tr.dataset.id = user.id;

        const createCell = (text) => {
            const td = document.createElement('td');
            td.textContent = text;
            return td;
        };

        tr.appendChild(createCell(user.id));
        tr.appendChild(createCell(user.username));
        tr.appendChild(createCell(user.role));
        tr.appendChild(createCell(user.created_at || 'N/A'));

        if (canManage) {
            const actionsTd = document.createElement('td');
            const buttonWrapper = document.createElement('div');
            buttonWrapper.className = 'd-flex gap-1 btn-group-equal'; // ใช้คลาสที่เราสร้างไว้

            const isSelf = (user.id === currentUserId);

            if (isSelf || (currentUserRole === 'creator') || (currentUserRole === 'admin' && user.role !== 'admin')) {
                const editButton = document.createElement('button');
                editButton.className = 'btn btn-sm btn-warning flex-fill'; // ขนาดปกติ
                editButton.textContent = 'Edit';
                editButton.addEventListener('click', () => editUser(user));
                buttonWrapper.appendChild(editButton);
            }

            if (!isSelf && ((currentUserRole === 'creator') || (currentUserRole === 'admin' && user.role !== 'admin'))) {
                const deleteButton = document.createElement('button');
                deleteButton.className = 'btn btn-sm btn-danger flex-fill'; // ขนาดปกติ
                deleteButton.textContent = 'Delete';
                deleteButton.addEventListener('click', () => deleteUser(user.id));
                buttonWrapper.appendChild(deleteButton);
            }
            
            actionsTd.appendChild(buttonWrapper);
            tr.appendChild(actionsTd);
        }

        tbody.appendChild(tr);
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
    
    const renderError = (message) => {
        tbody.innerHTML = '';
        const tr = tbody.insertRow();
        const td = tr.insertCell();
        td.colSpan = 6;
        td.className = 'text-center text-danger';
        td.textContent = message;
    };

    try {
        const result = await sendRequest('logs', 'GET');
        tbody.innerHTML = '';

        if (result.success) {
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No logs found.</td></tr>';
                return;
            }
            result.data.forEach(log => {
                const tr = tbody.insertRow();
                const createCell = (text) => {
                    const td = tr.insertCell();
                    td.textContent = text;
                };

                createCell(log.id);
                createCell(log.action_by);
                createCell(log.action_type);
                createCell(log.target_user || '-');
                createCell(log.detail || '-');
                createCell(log.created_at);
            });
        } else {
            renderError(result.message || 'An error occurred.');
        }
    } catch (error) {
        renderError('Failed to load logs.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
});