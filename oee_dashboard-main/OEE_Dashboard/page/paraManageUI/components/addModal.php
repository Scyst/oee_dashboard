<div id="addParamModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addParamModal')">&times;</span>
        <h2>Add Parameter</h2>
        <form id="addParamForm">
            <input type="text" name="line" placeholder="Line" required>
            <input type="text" name="model" placeholder="Model" required>
            <input type="text" name="part_no" placeholder="Part No." required>
            <input type="text" name="sap_no" placeholder="SAP No.">
            <input type="number" name="planned_output" placeholder="Planned Output" required>
            <button type="submit">Add Parameter</button>
        </form>
    </div>
</div>