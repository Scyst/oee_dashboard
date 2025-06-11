 // Export current data
    function exportToExcel() {
      if (!allData || allData.length === 0) {
        alert("No data to export.");
        return;
      }
      
      const headers = ["Line", "Model", "Part No", "Planned Output", "Updated At"];
      const rows = allData.map(row => [
        row.line, row.model, row.part_no, row.planned_output, row.updated_at
      ]);
      
      const worksheet = XLSX.utils.aoa_to_sheet([headers, ...rows]);
      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, "Parameters");
      
      XLSX.writeFile(workbook, "parameter_data.xlsx");
    }
    
    // Trigger file input when "Import" is clicked
    function triggerImport() {
      document.getElementById('importFile').click();
    }

    // Handle file selection
    async function handleImport(event) {
      const file = event.target.files[0];
      if (!file) return;

      const reader = new FileReader();

      reader.onload = async (e) => {
        let rows = [];

        if (file.name.endsWith('.csv')) {
          // Parse CSV manually
          const text = e.target.result;
          const lines = text.trim().split('\n');
          const headers = lines[0].split(',').map(h => h.trim().toLowerCase());

          for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split(',').map(v => v.trim());
            const row = {};
            headers.forEach((h, j) => {
              const key = h.toLowerCase().replace(/ /g, '_'); // Normalize keys
              row[key] = values[j];
            });

            // Normalize expected keys
            rows.push({
              line: row.line || '',
              model: row.model || '',
              part_no: row.part_no || '',
              planned_output: parseInt(row.planned_output) || 0
            });
          }

        } else {
          // Excel handling via SheetJS
          const workbook = XLSX.read(e.target.result, { type: "binary" });
          const sheetName = workbook.SheetNames[0];
          const sheet = workbook.Sheets[sheetName];
          const rawRows = XLSX.utils.sheet_to_json(sheet, { defval: "" });

          rows = rawRows.map(row => ({
            line: row["Line"] || row["line"] || '',
            model: row["Model"] || row["model"] || '',
            part_no: row["Part No"] || row["part_no"] || '',
            planned_output: parseInt(row["Planned Output"] || row["planned_output"] || 0)
          }));
        }

        if (rows.length && confirm(`Import ${rows.length} row(s)?`)) {
          const res = await fetch('../api/paraManage/paraManage.php?action=bulk_import', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(rows)
          });

          const result = await res.json();
          alert(result.message || "Import completed.");

          if (result.errors && result.errors.length > 0) {
            console.warn("Import warnings:", result.errors);
          }

          console.log("Sending to backend:", rows);
          if (typeof loadParameters === 'function') loadParameters();
        }
      };

      // Choose reader type
      if (file.name.endsWith('.csv')) {
        reader.readAsText(file);
      } else {
        reader.readAsBinaryString(file);
      }
    }