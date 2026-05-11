<?php
require 'php/config.php';
require_once __DIR__ . '/php/session_config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header("Location: ../auth/role");
    exit();
}

$adminName = htmlspecialchars($_SESSION['full_name'] ?? 'Admin User', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<link rel="icon" href="../img/logo.png">
	<link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">

	<link rel="stylesheet" href="css/sidebar.css">
	<link rel="stylesheet" href="css/logout.css">
	<link rel="stylesheet" href="css/reports_selection.css">

	<title>Reports</title>
</head>

<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<section id="sidebar">
	<a href="#" class="sidebar-brand">
		<img src="../img/logo.png" alt="Admin Logo" class="brand-logo">

		<div class="brand-text">
			<span class="brand-name">Hello Admin</span>
		</div>
	</a>

	<div class="sidebar-scroll">
		<div class="sidebar-section-label">Main Menu</div>

		<ul class="side-menu top">
			<li>
				<a href="php/admin_dashboard2" data-tooltip="Dashboard">
					<i class="bx bxs-dashboard nav-icon"></i>
					<span class="nav-label">Dashboard</span>
				</a>
			</li>

			<li>
				<a href="php/activity_logs" data-tooltip="Activity Logs">
					<i class="bx bxs-user nav-icon"></i>
					<span class="nav-label">Activity Logs</span>
				</a>
			</li>

			<li>
				<a href="php/admin_user" data-tooltip="User Management">
					<i class="bx bxs-notepad nav-icon"></i>
					<span class="nav-label">User Management</span>
				</a>
			</li>

			<li class="active">
				<a href="reports" data-tooltip="Reports">
					<i class="bx bxs-report nav-icon"></i>
					<span class="nav-label">Reports</span>
				</a>
			</li>
		</ul>

		<div class="sidebar-divider"></div>

		<ul class="side-menu">
			<li>
				<a href="#" class="logout" data-tooltip="Logout" onclick="return confirmLogout()">
					<i class="bx bxs-log-out-circle nav-icon"></i>
					<span class="nav-label">Logout</span>
				</a>
			</li>
		</ul>
	</div>

	<div class="sidebar-footer">
		<div class="sidebar-user">
			<img src="../img/admin.png" alt="Admin User">

			<div class="sidebar-user-info">
				<div class="user-name" id="sidebarUserName"><?php echo $adminName; ?></div>
				<div class="user-role">Administrator</div>
			</div>
		</div>
	</div>
</section>

<section id="content">
	<nav>
		<button type="button" class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
			<i class="bx bx-menu"></i>
		</button>

		<div class="nav-search">
			<input 
				type="search" 
				id="patientSearch" 
				placeholder="Search reports..." 
				name="search" 
				autocomplete="off"
			>

			<button type="button" id="searchButton" aria-label="Search">
				<i class="bx bx-search"></i>
			</button>

			<div id="resultDropdown" class="dropdown-content"></div>
		</div>
	</nav>

	<main>
		<div class="head-title">
			<div class="left">
				<h1>Reports</h1>
			</div>
		</div>

		<ul class="box-info" id="reportsList">
			<a href="reportsPages/admin_reports" class="report-link">
				<li class="mrr">
					<div class="icon-container">
						<i class="bx bxs-report report-card-icon" aria-hidden="true"></i>
					</div>

					<div class="text">
						<h3>Audit Log Reports</h3>
					</div>
				</li>
			</a>
		</ul>

		<div class="empty-state" id="emptyState" hidden>
			<i class="bx bx-search-alt"></i>
			<h3>No reports found</h3>
			<p>Try searching for another report keyword.</p>
		</div>
	</main>
</section>

<div id="logoutModal" class="logout-modal">
	<div class="logout-modal-content">
		<div class="logout-modal-header">
			<h3>Confirm Logout</h3>
		</div>

		<div class="logout-modal-body">
			<p>Are you sure you want to logout?</p>
		</div>

		<div class="logout-modal-footer">
			<button type="button" id="logoutCancelBtn" class="logout-cancel-btn">
				Cancel
			</button>

			<button type="button" id="logoutConfirmBtn" class="logout-confirm-btn">
				Yes, Logout
			</button>
		</div>
	</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
	setupSidebar();
	setupReportSearch();
	setupLogoutModal();
});

function setupSidebar() {
	const sidebar = document.getElementById("sidebar");
	const toggle = document.getElementById("sidebarToggle");
	const overlay = document.getElementById("sidebarOverlay");
	const MOBILE_BP = 768;

	if (!sidebar || !toggle || !overlay) {
		return;
	}

	function isMobile() {
		return window.innerWidth <= MOBILE_BP;
	}

	function closeMobileSidebar() {
		sidebar.classList.remove("mobile-open");
		overlay.classList.remove("active");
		document.body.style.overflow = "";
	}

	toggle.addEventListener("click", function () {
		if (isMobile()) {
			const open = sidebar.classList.toggle("mobile-open");

			overlay.classList.toggle("active", open);
			document.body.style.overflow = open ? "hidden" : "";
		} else {
			sidebar.classList.toggle("collapsed");
		}
	});

	overlay.addEventListener("click", closeMobileSidebar);

	window.addEventListener("resize", function () {
		if (!isMobile()) {
			closeMobileSidebar();
		}
	});
}

function setupReportSearch() {
	const searchInput = document.getElementById("patientSearch");
	const searchButton = document.getElementById("searchButton");
	const emptyState = document.getElementById("emptyState");

	function filterReports() {
		const searchTerm = (searchInput?.value || "").toLowerCase().trim();
		const reportCards = document.querySelectorAll(".box-info .report-link");

		let visibleCount = 0;

		reportCards.forEach(card => {
			const isMatch = card.textContent.toLowerCase().includes(searchTerm);

			card.style.display = isMatch ? "" : "none";

			if (isMatch) {
				visibleCount++;
			}
		});

		if (emptyState) {
			emptyState.hidden = visibleCount !== 0;
		}
	}

	if (searchInput) {
		searchInput.addEventListener("input", filterReports);

		searchInput.addEventListener("keypress", function (event) {
			if (event.key === "Enter") {
				event.preventDefault();
				filterReports();
			}
		});
	}

	if (searchButton) {
		searchButton.addEventListener("click", filterReports);
	}
}

function confirmLogout() {
	const modal = document.getElementById("logoutModal");

	if (modal) {
		modal.style.display = "grid";
	}

	return false;
}

function closeModal() {
	const modal = document.getElementById("logoutModal");

	if (modal) {
		modal.style.display = "none";
	}
}

function proceedLogout() {
	window.location.href = "php/logout";
}

function setupLogoutModal() {
	const modal = document.getElementById("logoutModal");
	const cancelBtn = document.getElementById("logoutCancelBtn");
	const confirmBtn = document.getElementById("logoutConfirmBtn");

	if (cancelBtn) {
		cancelBtn.addEventListener("click", closeModal);
	}

	if (confirmBtn) {
		confirmBtn.addEventListener("click", proceedLogout);
	}

	if (modal) {
		modal.addEventListener("click", function (event) {
			if (event.target === modal) {
				closeModal();
			}
		});
	}
}
</script>

</body>
</html>