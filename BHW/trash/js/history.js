document.addEventListener('DOMContentLoaded', function () {
    const rowsPerPage = 9; 
    const table = document.querySelector('#history-table tbody');
    const rows = Array.from(table.rows);  
    const paginationContainer = document.querySelector('.pagination');

    const numPages = Math.ceil(rows.length / rowsPerPage);

    function showPage(pageNum) {
        rows.forEach(row => {
            row.style.display = 'none';
        });

        const startIndex = (pageNum - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        const currentRows = rows.slice(startIndex, endIndex);

        // Show the rows for this page
        currentRows.forEach(row => {
            row.style.display = ''; 
        });

        updatePagination(pageNum); 
    }

    function updatePagination(currentPage) {
        paginationContainer.innerHTML = '';

        const prevLink = document.createElement('a');
        prevLink.href = '#';
        prevLink.classList.add('prev');
        prevLink.innerText = 'Previous';
        prevLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage > 1) {
                showPage(currentPage - 1);
            }
        });
        paginationContainer.appendChild(prevLink);

        for (let i = 1; i <= numPages; i++) {
            const pageLink = document.createElement('a');
            pageLink.href = '#';
            pageLink.classList.add('page-number');
            pageLink.innerText = i;
            if (i === currentPage) {
                pageLink.classList.add('active'); 
            }

            pageLink.addEventListener('click', (e) => {
                e.preventDefault();
                showPage(i); 
            });

            paginationContainer.appendChild(pageLink);
        }

        const nextLink = document.createElement('a');
        nextLink.href = '#';
        nextLink.classList.add('next');
        nextLink.innerText = 'Next';
        nextLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage < numPages) {
                showPage(currentPage + 1); 
            }
        });
        paginationContainer.appendChild(nextLink);
    }

    showPage(1);
});
