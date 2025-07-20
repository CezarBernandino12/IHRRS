document.addEventListener("DOMContentLoaded", function () {
    let currentPage = 1;
    const limit = 9;
    let totalPages = 0;

    function fetchReferrals(page) {
        fetch(`php/fetch_referrals.php?page=${page}&limit=${limit}`)
            .then(response => response.json())
            .then(data => {
                const tableBody = document.querySelector("#history-table tbody");
                tableBody.innerHTML = "";

                if (data.error) {
                    console.error(data.error);
                    tableBody.innerHTML = `<tr><td colspan="4">Error fetching data</td></tr>`;
                    return;
                }

                if (data.referrals.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="4">No referrals found</td></tr>`;
                    return;
                }

                data.referrals.forEach(referral => {
                    let status = referral.referral_status || 'N/A';
                    let statusClass = '';

                    if (status.toLowerCase() === 'pending') {
                        statusClass = 'pending';
                    } else if (status.toLowerCase() === 'missed') {
                        statusClass = 'missed';
                    } else if (status.toLowerCase() === 'completed') {
                        statusClass = 'completed';
                    }

                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td>${formatDate(referral.referral_date)}</td>
                        <td>${referral.patient_name}</td>
                        <td>${referral.referred_by}</td>
                        <td class="${statusClass}">${status}</td>
                    `;
                    tableBody.appendChild(row);
                });

                totalPages = data.totalPages;
                updatePagination(page, totalPages);
            })
            .catch(error => {
                console.error("Error fetching referrals:", error);
            });
    }

    function formatDate(dateString) {
        if (!dateString) return "N/A";
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return "Invalid Date";
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return `${date.toLocaleDateString('en-US', options)} <br><span class="time">${date.toLocaleTimeString()}</span>`;
    }

    function updatePagination(page, totalPages) {
        const pagination = document.querySelector("#pagination");
        pagination.innerHTML = `<a href="#" class="prev">Previous</a>`;
        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === page ? "active" : "";
            pagination.innerHTML += `<a href="#" class="page-number ${activeClass}" data-page="${i}">${i}</a>`;
        }
        pagination.innerHTML += `<a href="#" class="next">Next</a>`;
    }

    document.querySelector("#pagination").addEventListener("click", function (e) {
        e.preventDefault();
        if (e.target.classList.contains("prev") && currentPage > 1) {
            currentPage--;
            fetchReferrals(currentPage);
        } else if (e.target.classList.contains("next") && currentPage < totalPages) {
            currentPage++;
            fetchReferrals(currentPage);
        } else if (e.target.classList.contains("page-number")) {
            currentPage = parseInt(e.target.getAttribute("data-page"));
            fetchReferrals(currentPage);
        }
    });

    fetchReferrals(currentPage);
});
