<div id="editPartModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editPartModal')">&times;</span>
        <h2>Edit Part</h2>
        <form id="editPartForm">
            <input type="hidden" name="id" id="edit_id">

            <input type="date" name="log_date" id="edit_date" required><br>
            <input type="time" name="log_time" id="edit_time" step="1" required><br>

            <!-- Line -->
            <input list="editLineList" name="line" id="edit_line" placeholder="Line" required>
            <datalist id="editLineList">
                <?php include '../api/pdTable/get_lines.php'; ?>
            </datalist><br>

            <!-- Model -->
            <input list="editModelList" name="model" id="edit_model" placeholder="Model" required>
            <datalist id="editModelList">
                <?php include '../api/pdTable/get_models.php'; ?>
            </datalist><br>

            <!-- Part No -->
            <input list="editPartList" name="part_no" id="edit_part_no" placeholder="Part No." required>
            <datalist id="editPartList">
                <?php include '../api/pdTable/get_part_nos.php'; ?>
            </datalist><br>

            <!-- Lot No -->
            <input type="text" name="lot_no" id="edit_lot_no" placeholder="Auto-generated" readonly>

            <!-- Count Value -->
            <input type="number" name="count_value" id="edit_value" placeholder="Quantity" required><br>
            
            <!-- Count Type -->
            <select name="count_type" id="edit_type" required>
                <option value="">-- Select Type --</option>
                <option value="FG">FG</option>
                <option value="NG">NG</option>
                <option value="HOLD">HOLD</option>
                <option value="REWORK">REWORK</option>
                <option value="SCRAP">SCRAP</option>
                <option value="ETC.">ETC.</option>
            </select><br>

            <input type="text" placeholder="Note" name="note" id="edit_note"><br>

            <button type="submit">Update Part</button>
        </form>
    </div>
</div>
