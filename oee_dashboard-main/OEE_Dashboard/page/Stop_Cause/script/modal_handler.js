function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.style.display = "block";

    if (modalId === 'addStopModal') {
        const now = new Date();
        const dateStr = now.toISOString().split('T')[0];
        const timeStr = now.toTimeString().split(' ')[0].substring(0, 8);
        
        modal.querySelector('input[name="log_date"]').value = dateStr;
        modal.querySelector('input[name="stop_begin"]').value = timeStr;
        
        const endTime = new Date(now.getTime() + 5 * 60 * 1000);
        modal.querySelector('input[name="stop_end"]').value = endTime.toTimeString().split(' ')[0].substring(0, 8);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = "none";
}

async function openEditModal(id) {
    try {
        const response = await fetch(`${API_URL}?action=get_stop_by_id&id=${id}`);
        const result = await response.json();
        if (result.success) {
            const data = result.data;
            const modal = document.getElementById('editStopModal');
            
            for (const key in data) {
                const input = modal.querySelector(`#edit_${key}`);
                if (input) input.value = data[key];
            }
            openModal('editStopModal');
        } else {
            alert(result.message);
        }
    } catch (error) {
        alert('Failed to fetch details for editing.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const addForm = document.getElementById('addStopForm');
    if(addForm) {
        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = Object.fromEntries(new FormData(addForm).entries());
            try {
                const response = await fetch(`${API_URL}?action=add_stop`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                alert(result.message);
                if(result.success) {
                    closeModal('addStopModal');
                    addForm.reset();
                    fetchStopData(1);
                }
            } catch(error) {
                alert('An error occurred while adding data.');
            }
        });
    }

    const editForm = document.getElementById('editStopForm');
    if(editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = Object.fromEntries(new FormData(editForm).entries());
            try {
                const response = await fetch(`${API_URL}?action=update_stop`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                alert(result.message);
                if(result.success) {
                    closeModal('editStopModal');
                    fetchStopData(currentPage);
                }
            } catch(error) {
                alert('An error occurred while updating data.');
            }
        });
    }

    window.addEventListener('click', function (event) {
        document.querySelectorAll('.modal').forEach(modal => {
            if (event.target === modal) {
                closeModal(modal.id);
            }
        });
    });
});