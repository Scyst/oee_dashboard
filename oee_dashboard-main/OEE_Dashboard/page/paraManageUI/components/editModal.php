<div id="editParamModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editParamModal')">&times;</span>
        <h2>Edit Parameter</h2>
        <form id="editParamForm">
            <input type="hidden" name="id" id="edit_id">
            <label>Line:</label>
            <input type="text" name="line" id="edit_line" required>
            <label>Model:</label>
            <input type="text" name="model" id="edit_model" required>
            <label>Part No.:</label>
            <input type="text" name="part_no" id="edit_part_no" required>
            <label>SAP No.:</label>
            <input type="text" name="sap_no" id="edit_sap_no">
            <label>Planned Output:</label>
            <input type="number" name="planned_output" id="edit_planned_output" required>
            <label>Updated At:</label>
            <input type="text" name="updated_at" id="edit_updated_at" readonly>
            <button type="submit">Update Parameter</button>
        </form>
    </div>
</div>