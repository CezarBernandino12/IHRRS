document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.querySelector('#visitTable tbody');
    let allData = [];
    let currentPage = 1;
    const recordsPerPage = 15;

    fetch('php/fetch_records_history.php')
        .then(response => response.json())
        .then(data => {
            allData = data;
            displayTable(data);
            setupPagination(data); 
        })
        .catch(error => {
            console.error('Error fetching visit data:', error);
        });

    function displayTable(data) {
        tableBody.innerHTML = ''; 
        const startIndex = (currentPage - 1) * recordsPerPage;
        const endIndex = startIndex + recordsPerPage;
        const paginatedData = data.slice(startIndex, endIndex); 

        if (paginatedData.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 3; 
            td.textContent = 'No record found.';
            td.style.textAlign = 'center';
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
                    window.location.href = `visitInfo?visit_id=${row.visit_id}`;
                });
                tableBody.appendChild(tr);
            });
        }
    }

    function setupPagination(data) {
        const totalPages = Math.ceil(data.length / recordsPerPage);
        const paginationContainer = document.getElementById('pagination');
        paginationContainer.innerHTML = ''; 

        const prevButton = document.createElement('a');
        prevButton.href='#';
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
            pageButton.href='#';
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
        nextButton.href='#';
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

    document.getElementById('applyFilter').addEventListener('click', () => {
        const selectedDate = document.getElementById('date').value;
        if (!selectedDate) return;

        const filtered = allData.filter(row => row.visit_date.split(' ')[0] === selectedDate);
        
        displayTable(filtered);
        setupPagination(filtered);
    });
});
