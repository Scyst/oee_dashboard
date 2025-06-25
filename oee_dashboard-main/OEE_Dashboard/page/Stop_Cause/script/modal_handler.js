let modalTriggerElement = null;

/**
 * 
 * @param {string} modalId
 */
function showBootstrapModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (modalElement) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
    }
}

function openAddStopModal(triggerEl) {
    modalTriggerElement = triggerEl;
    const modal = document.getElementById('addStopModal');
    if (!modal) return;

    const now = new Date();
    const tzOffset = 7 * 60 * 60 * 1000;
    const localNow = new Date(now.getTime() + tzOffset);
    
    const dateStr = localNow.toISOString().split('T')[0];
    const timeStr = localNow.toISOString().split('T')[1].substring(0, 8);
    
    modal.querySelector('input[name="log_date"]').value = dateStr;
    modal.querySelector('input[name="stop_begin"]').value = timeStr;
    
    const endTime = new Date(localNow.getTime() + (5 * 60 * 1000));
    modal.querySelector('input[name="stop_end"]').value = endTime.toISOString().split('T')[1].substring(0, 8);

    showBootstrapModal('addStopModal');
}

/**
 * 
 * @param {number} id
 */
async function openEditModal(id, triggerEl) {
    modalTriggerElement = triggerEl;
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
            showBootstrapModal('editStopModal');
        } else {
            showToast(result.message, '#dc3545');
        }
    } catch (error) {
        showToast('Failed to fetch details for editing.', '#dc3545');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    
    const addForm = document.getElementById('addStopForm');
    if (addForm) {
        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = Object.fromEntries(new FormData(addForm).entries());
            
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const response = await fetch(`${API_URL}?action=add_stop`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken 
                    },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                showToast(result.message, result.success ? '#28a745' : '#dc3545');

                if (result.success) {
                    const modalElement = document.getElementById('addStopModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (modalInstance) {
                        modalElement.addEventListener('hidden.bs.modal', () => {
                            addForm.reset();
                            fetchStopData(1);
                            if (modalTriggerElement) {
                                modalTriggerElement.focus();
                            }
                        }, { once: true });
                        modalInstance.hide();
                    }
                }
            } catch(error) {
                showToast('An error occurred while adding data.', '#dc3545');
            }
        });
    }

    const editForm = document.getElementById('editStopForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = Object.fromEntries(new FormData(editForm).entries());

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const response = await fetch(`${API_URL}?action=update_stop`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                showToast(result.message, result.success ? '#28a745' : '#dc3545');

                if (result.success) {
                    const modalElement = document.getElementById('editStopModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (modalInstance) {
                        modalElement.addEventListener('hidden.bs.modal', () => {
                            fetchStopData(currentPage);
                            if (modalTriggerElement) {
                                modalTriggerElement.focus();
                            }
                        }, { once: true });
                        modalInstance.hide();
                    }
                }
            } catch(error) {
                showToast('An error occurred while updating data.', '#dc3545');
            }
        });
    }
    
});