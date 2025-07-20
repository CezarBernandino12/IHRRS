document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.querySelector('#visitTable tbody');
    let allData = [];
    let currentPage = 1;
    const recordsPerPage = 15;

    // Fetch all records and store in memory
    fetch('php/fetch_records_history.php')
        .then(response => response.json())
        .then(data => {
            allData = data;
            displayTable(data); // Initial display
            setupPagination(data); // Setup pagination
        })
        .catch(error => {
            console.error('Error fetching visit data:', error);
        });

    // Display records in table
    function displayTable(data) {
        tableBody.innerHTML = ''; // Clear old data
        const startIndex = (currentPage - 1) * recordsPerPage;
        const endIndex = startIndex + recordsPerPage;
        const paginatedData = data.slice(startIndex, endIndex); // Slice the data based on the current page

        if (paginatedData.length === 0) {
            // Show "No record found." message if no records
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 3;  // Span across all columns
            td.textContent = 'No record found.';
            td.style.textAlign = 'center';  // Center the message
            tableBody.appendChild(tr);
            tr.appendChild(td);
        } else {
            paginatedData.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${row.visit_date}</td>
                    <td>${row.patient_name}</td>
                    <td>${row.recorded_by}</td>
                `;
                tr.style.cursor = 'pointer';
                tr.addEventListener('click', () => {
                    window.location.href = `visitInfo.html?visit_id=${row.visit_id}`;
                });
                tableBody.appendChild(tr);
            });
        }
    }

    // Set up pagination buttons
    function setupPagination(data) {
        const totalPages = Math.ceil(data.length / recordsPerPage);
        const paginationContainer = document.getElementById('pagination');
        paginationContainer.innerHTML = ''; // Clear existing pagination

        const prevButton = document.createElement('a');
        prevButton.href = '#';
        prevButton.classList.add('prev');
        prevButton.textContent = 'Previous';
        prevButton.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                displayTable(data);
                updatePagination();
            }
        });
        paginationContainer.appendChild(prevButton);

        for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('a');
            pageButton.href = '#';
            pageButton.classList.add('page-number');
            if (i === currentPage) pageButton.classList.add('active');
            pageButton.textContent = i;
            pageButton.addEventListener('click', () => {
                currentPage = i;
                displayTable(data);
                updatePagination();
            });
            paginationContainer.appendChild(pageButton);
        }

        const nextButton = document.createElement('a');
        nextButton.href = '#';
        nextButton.classList.add('next');
        nextButton.textContent = 'Next';
        nextButton.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                displayTable(data);
                updatePagination();
            }
        });
        paginationContainer.appendChild(nextButton);
    }

    // Update the active page in the pagination
    function updatePagination() {
        const pageButtons = document.querySelectorAll('.page-number');
        pageButtons.forEach(button => {
            if (parseInt(button.textContent) === currentPage) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }

    // Filter button logic
    document.getElementById('applyFilter').addEventListener('click', () => {
        const selectedDate = document.getElementById('date').value;
        if (!selectedDate) return;

        // Remove the time part from the stored visit_date (e.g., '2025-04-19 14:25:31' -> '2025-04-19')
        const filtered = allData.filter(row => row.visit_date.split(' ')[0] === selectedDate);
        
        displayTable(filtered);
        setupPagination(filtered);
    });
});
