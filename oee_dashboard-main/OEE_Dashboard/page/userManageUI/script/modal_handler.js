// --- Generic Modal Functions ---
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'block';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

// --- Page-Specific Modal Logic ---
async function openLogsModal() {
    openModal('logsModal');
    loadLogs(); // Calls the function from userManage.js
}

function editUser(user) {
    const isSelf = (user.id === currentUserId);
    const usernameInput = document.getElementById('username');
    const roleInput = document.getElementById('role');

    // เปิดใช้งาน input ก่อนเสมอ เพื่อให้แน่ใจว่าค่าจะถูกตั้งได้
    usernameInput.disabled = false;
    roleInput.disabled = false;

    // ตั้งค่าฟอร์ม
    document.getElementById('userId').value = user.id;
    usernameInput.value = user.username;
    roleInput.value = user.role;
    document.getElementById('password').placeholder = 'Leave blank to keep unchanged';
    document.getElementById('password').value = '';

    // ตรวจสอบเงื่อนไข: ถ้าเป็น admin แก้ไขตัวเอง ให้ disable ช่องที่ไม่ต้องการให้แก้
    if (currentUserRole === 'admin' && isSelf) {
        usernameInput.disabled = true;
        roleInput.disabled = true;
    }

    // สลับปุ่ม
    document.getElementById('addBtn').classList.add('d-none');
    document.getElementById('updateBtn').classList.remove('d-none');
    document.getElementById('cancelBtn').classList.remove('d-none');
    window.scrollTo(0, 0);
}

function resetForm() {
    const form = document.getElementById('userForm');
    if (!form) return;
    form.reset();
    document.getElementById('userId').value = '';
    document.getElementById('password').placeholder = 'Password';
    document.getElementById('addBtn').classList.remove('d-none');
    document.getElementById('updateBtn').classList.add('d-none');
    document.getElementById('cancelBtn').classList.add('d-none');
}

// --- Event Listeners for Forms and Modals ---
document.addEventListener('DOMContentLoaded', () => {
    if (isAdmin) {
        const userForm = document.getElementById('userForm');
        if (userForm) {
            userForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const id = document.getElementById('userId').value;
                const action = id ? 'update' : 'create';
                const payload = Object.fromEntries(new FormData(userForm).entries());

                if (action === 'create') {
                    if (!payload.username || !payload.password || !payload.role) {
                        showToast("Please fill all required fields for new user.", "#ffc107");
                        return;
                    }
                }

                const result = await sendRequest(action, 'POST', payload);
                showToast(result.message, result.success ? '#28a745' : '#dc3545');
                
                if (result.success) {
                    resetForm();
                    loadUsers();
                }
            });
        }
        document.getElementById('cancelBtn')?.addEventListener('click', resetForm);
    }

    window.addEventListener('click', (event) => {
        document.querySelectorAll('.modal').forEach(modal => {
            if (event.target === modal) closeModal(modal.id);
        });
    });
});