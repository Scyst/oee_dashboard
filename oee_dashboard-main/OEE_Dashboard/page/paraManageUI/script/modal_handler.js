function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) modal.style.display = 'block';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) modal.style.display = 'none';
}

async function openEditModal(id) {
    try {
        const result = await sendRequest(`get_param_by_id&id=${id}`, 'GET');
        if (result.success) {
            const data = result.data;
            const modal = document.getElementById('editParamModal');
            for (const key in data) {
                const input = modal.querySelector(`#edit_${key}`);
                if (input) input.value = data[key];
            }
            openModal('editParamModal');
        } else {
            showToast(result.message, '#dc3545');
        }
    } catch (error) {
        showToast('Failed to fetch parameter details.', '#dc3545');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (!isAdmin) return;

    document.getElementById('addParamForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(e.target).entries());
        const result = await sendRequest('create', 'POST', payload);
        if (result.success) {
            showToast('Parameter added successfully!');
            closeModal('addParamModal');
            e.target.reset();
            loadParameters();
        } else {
            showToast(result.message || 'Failed to add parameter.', '#dc3545');
        }
    });

    document.getElementById('editParamForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(e.target).entries());
        const result = await sendRequest('update', 'POST', payload);
        if (result.success) {
            showToast('Parameter updated successfully!');
            closeModal('editParamModal');
            loadParameters();
        } else {
            showToast(result.message || 'Failed to update parameter.', '#dc3545');
        }
    });

    window.addEventListener('click', e => {
        document.querySelectorAll('.modal').forEach(modal => {
            if (e.target === modal) closeModal(modal.id);
        });
    });
});