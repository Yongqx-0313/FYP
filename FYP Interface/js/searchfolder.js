function filterTable() {
    // Get the search input value
    const searchInput = document.getElementById("tablesearch").value.toLowerCase();
    const table = document.getElementById("foldertable");
    const rows = table.getElementsByTagName("tr");
    let noMatchFound = true; // Tracks if there are any matches

    // Remove any existing "no match" message row
    const existingMessageRow = document.getElementById("noMatchMessageRow");
    if (existingMessageRow) {
        existingMessageRow.remove();
    }

    // Loop through all table rows (excluding the header)
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName("td");
        let rowMatches = false;

        // Check if any cell in the row matches the search input
        for (let j = 0; j < cells.length; j++) {
            const cellText = cells[j].innerText.toLowerCase();
            if (cellText.includes(searchInput)) {
                rowMatches = true;
                break;
            }
        }

        // Show or hide the row based on the match
        row.style.display = rowMatches ? "" : "none";
        if (rowMatches) {
            noMatchFound = false; // At least one row matches
        }
    }

    // If no rows match, display a "No matching records found" message
    if (noMatchFound) {
        const noMatchRow = document.createElement("tr");
        noMatchRow.id = "noMatchMessageRow"; // Add an ID for easy removal
        noMatchRow.innerHTML = `
            <td colspan="${rows[0].getElementsByTagName("th").length}" style="text-align: center; font-weight: bold; color: black;">
                No matching records found.
            </td>
        `;
        table.appendChild(noMatchRow);
    }

}

document.getElementById('tablesearch').addEventListener('keypress', function (event) {
    if (event.key === 'Enter') {
        event.preventDefault();
    }
});

