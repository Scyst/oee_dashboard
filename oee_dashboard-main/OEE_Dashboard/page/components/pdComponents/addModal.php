<div id="partModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('partModal')">&times;</span>
        <h2 style="font-size: 2.5rem;">Add Part</h2>
        <form id="addPartForm">
            <input type="date" name="log_date" required value="<?= date('Y-m-d') ?>"><br>
            <input type="time" name="log_time" step="1" required value="<?= date('H:i:s') ?>"><br>

            <!-- Line input with datalist -->
            <input list="lineList" name="line" placeholder="Line" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()" required>
            <datalist id="lineList">
                <?php include '../api/pdTable/get_lines.php'; ?>
            </datalist><br>

            <!-- Model input with datalist -->
            <input list="modelList" name="model" placeholder="Model" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()" required>
            <datalist id="modelList">
                <?php include '../api/pdTable/get_models.php'; ?>
            </datalist><br>

            <!-- Part No. input with datalist -->
            <input list="partList" name="part_no" placeholder="Part No." style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()" required>
            <datalist id="partList">
                <?php include '../api/pdTable/get_part_nos.php'; ?>
            </datalist><br>

            <input type="text" name="lot_no" id="add_lot_no" placeholder="Auto-generated" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()" readonly>

            <input type="number" name="count_value" placeholder="Enter value"  required><br>
            
            <select name="count_type" required>
                <option value="FG">FG</option>
                <option value="NG">NG</option>
                <option value="HOLD">HOLD</option>
                <option value="REWORK">REWORK</option>
                <option value="SCRAP">SCRAP</option>
                <option value="ETC.">ETC.</option>
            </select><br>

            <input type="text" placeholder="Note" name="note"><br>

            <button type="submit" style="padding-block: 0;">Submit Part</button>
        </form>

    </div>
</div>
