<?php
/**
 * Shift Management Page for Coles Prefer        .coles-header {
            background: linear-gradient(135deg, var(--coles-red) 0%, var(--coles-dark-red) 100%);
            box-shadow: 0 4px 12px rgba(227, 6, 19, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            overflow: hidden;
            transition: all 0.3s ease;
            padding: 1rem 0;
        }
        
        .coles-header.scrolled {
            box-shadow: 0 8px 25px rgba(227, 6, 19, 0.4);
            backdrop-filter: blur(10px);
            padding: 0.5rem 0;
        }
        
        .coles-header.scrolled .coles-brand {
            font-size: 1.2rem;
        }
        
        .coles-header.scrolled .coles-subtitle {
            font-size: 0.75rem;
        }
        
        .coles-header.scrolled .eastern-creek-badge {
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
        }System
 */

require_once 'includes/auth.php';

// Require login
Auth::requireLogin();
$user = Auth::getCurrentUser();

// Get shift ID from URL or user's ass                <a class="nav-link text-white me-2" href="dashboard.php" title="Back to Dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                </a>
                <a class="nav-link text-white me-2" href="index.php" title="Main Dashboard">
                    <i class="fas fa-table"></i>
                </a>ed shift
$shiftId = $_GET['id'] ?? $user['shift_id'];

if (!$shiftId) {
    header("Location: index.php");
    exit;
}

// Verify user has permission for this shift
if (!Auth::hasPermission('view', $shiftId)) {
    http_response_code(403);
    die('Access denied');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Management - Coles Preferences</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --coles-red: #E30613;
            --coles-dark-red: #C20510;
            --coles-light-red: #F5E6E7;
            --coles-orange: #FF6B35;
            --coles-green: #008751;
            --coles-dark-green: #006B3F;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .coles-header {
            background: linear-gradient(135deg, var(--coles-green) 0%, var(--coles-dark-green) 100%);
            box-shadow: 0 4px 12px rgba(0, 135, 81, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .coles-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.05) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .coles-brand {
            font-weight: 700;
            font-size: 1.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            letter-spacing: -0.5px;
            transition: font-size 0.3s ease;
            color: white;
        }
        
        .coles-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 300;
            transition: font-size 0.3s ease;
            color: white;
        }
        
        .eastern-creek-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.3);
            font-size: 0.8rem;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .table th { 
            background: linear-gradient(135deg, #E8F5E8 0%, #ffffff 100%);
            color: var(--coles-dark-green);
            font-weight: 600;
            border: none;
        }
        
        .preference-cell { font-size: 0.9em; }
        
        .employee-row:hover { 
            background: linear-gradient(135deg, #E8F5E8 0%, #ffffff 100%);
            transform: translateY(-1px);
            transition: all 0.3s ease;
        }
        
        .manager-header {
            background: linear-gradient(135deg, var(--coles-green) 0%, var(--coles-dark-green) 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 135, 81, 0.2);
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--coles-green) 0%, var(--coles-dark-green) 100%);
            border: none;
            box-shadow: 0 2px 6px rgba(0, 135, 81, 0.3);
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, var(--coles-dark-green) 0%, #004D2F 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 135, 81, 0.4);
        }
        
        /* Sticky Table Headers */
        .table-container {
            max-height: 65vh;
            overflow-y: auto;
            border-radius: 12px;
        }
        
        .table-sticky {
            position: relative;
        }
        
        .table-sticky thead th {
            position: sticky;
            top: 0;
            background: linear-gradient(135deg, var(--coles-green) 0%, var(--coles-dark-green) 100%) !important;
            color: white !important;
            z-index: 10;
            border-bottom: 2px solid var(--coles-dark-green) !important;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 14px 12px !important;
            box-shadow: 0 2px 8px rgba(0, 135, 81, 0.3);
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        
        .table-sticky thead th:first-child {
            border-top-left-radius: 12px;
        }
        
        .table-sticky thead th:last-child {
            border-top-right-radius: 12px;
        }
        
        .table-sticky tbody tr:hover {
            background: linear-gradient(135deg, #E8F5E8 0%, #ffffff 100%);
            transform: translateY(-1px);
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 135, 81, 0.1);
        }
        
        .table-sticky td {
            padding: 12px;
            vertical-align: middle;
            border-color: #e9ecef;
        }
        
        /* Scrollbar styling for shift manager */
        .table-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: var(--coles-green);
            border-radius: 4px;
            opacity: 0.7;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: var(--coles-dark-green);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark coles-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="me-4">
                    <div class="coles-brand">
                        <i class="fas fa-store me-2"></i>
                        COLES
                    </div>
                    <div class="coles-subtitle">Shift Management Portal</div>
                </div>
                <span class="eastern-creek-badge">
                    <i class="fas fa-users me-1"></i>
                    Team Manager Access
                </span>
            </div>
            <div class="navbar-nav ms-auto d-flex align-items-center">
                <div class="me-4 text-end">
                    <div class="text-white">
                        <i class="fas fa-user me-1"></i>
                        <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                    </div>
                    <small class="text-white-50">
                        <span class="badge bg-light text-dark"><?= ucwords(str_replace('_', ' ', $user['role'])) ?></span>
                    </small>
                </div>
                <a class="nav-link text-white me-2" href="index.php" title="Main Dashboard">
                    <i class="fas fa-home"></i>
                </a>
                <a class="nav-link text-white" href="logout.php" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card manager-header mb-4">
                    <div class="card-body text-center">
                        <h2><i class="fas fa-clock me-2"></i>Shift <span id="shiftName">Loading...</span></h2>
                        <p class="mb-0">Manage your team's holiday preferences</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h4 id="teamCount">0</h4>
                        <p class="mb-0">Team Members</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4 id="completePrefs">0</h4>
                        <p class="mb-0">Complete Preferences</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <h4 id="pendingCount">0</h4>
                        <p class="mb-0">Pending Approvals</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Your Team Members
                </h5>
            </div>
            <div class="card-body p-0">
                <div id="loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-3">Loading team data...</p>
                </div>
                <div class="table-container" id="teamTable" style="display: none;">
                    <table class="table table-hover table-sticky mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>SAPID</th>
                                <th>1st Preference</th>
                                <th>2nd Preference</th>
                                <th>3rd Preference</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="teamTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Employee Preferences Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Employee Preferences</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="editLoading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-3">Loading employee data...</p>
                    </div>
                    <div id="editForm" style="display: none;">
                        <div class="mb-3">
                            <h6 id="employeeName" class="fw-bold"></h6>
                            <small class="text-muted">SAPID: <span id="employeeSapid"></span></small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">1st Preference</label>
                                <select class="form-select" id="pref1">
                                    <option value="">Select holiday...</option>
                                </select>
                                <button class="btn btn-sm btn-outline-danger mt-1" onclick="removePreference(1)" id="removePref1" style="display: none;">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">2nd Preference</label>
                                <select class="form-select" id="pref2">
                                    <option value="">Select holiday...</option>
                                </select>
                                <button class="btn btn-sm btn-outline-danger mt-1" onclick="removePreference(2)" id="removePref2" style="display: none;">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">3rd Preference</label>
                                <select class="form-select" id="pref3">
                                    <option value="">Select holiday...</option>
                                </select>
                                <button class="btn btn-sm btn-outline-danger mt-1" onclick="removePreference(3)" id="removePref3" style="display: none;">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Select different holidays for each preference. Duplicates will be automatically handled.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="savePreferences()" id="saveBtn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const shiftId = <?= $shiftId ?>;
        let teamMembers = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadTeamData();
        });

        async function loadTeamData() {
            try {
                const response = await fetch(`api/shift_employees.php?shift_id=${shiftId}`);
                const data = await response.json();
                
                if (data.success) {
                    teamMembers = data.employees;
                    displayTeam();
                    updateStats();
                } else {
                    showError('Failed to load team data: ' + data.error);
                }
            } catch (error) {
                showError('Error loading data: ' + error.message);
            }
        }

        function displayTeam() {
            const tbody = document.getElementById('teamTableBody');
            const loading = document.getElementById('loading');
            const table = document.getElementById('teamTable');
            
            loading.style.display = 'none';
            table.style.display = 'block';
            
            tbody.innerHTML = '';
            
            if (teamMembers.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No team members found</td></tr>';
                return;
            }

            // Update shift name
            if (teamMembers.length > 0) {
                document.getElementById('shiftName').textContent = teamMembers[0].shift_name || 'Unknown';
            }
            
            teamMembers.forEach(member => {
                const row = document.createElement('tr');
                row.className = 'employee-row';
                
                const status = getPreferenceStatus(member);
                const statusBadge = getStatusBadge(status);
                
                row.innerHTML = `
                    <td>
                        <strong>${member.team_member}</strong>
                        ${member.preferred_name ? `<br><small class="text-muted">(${member.preferred_name})</small>` : ''}
                    </td>
                    <td><code>${member.sapid}</code></td>
                    <td class="preference-cell">${member.first_preference || '<span class="text-muted">-</span>'}</td>
                    <td class="preference-cell">${member.second_preference || '<span class="text-muted">-</span>'}</td>
                    <td class="preference-cell">${member.third_preference || '<span class="text-muted">-</span>'}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="openEditModal(${member.id})" title="Edit Preferences">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="approvePreferences(${member.id})" title="Approve Preferences">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="viewDetails(${member.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }

        function getPreferenceStatus(member) {
            const prefs = [member.first_preference, member.second_preference, member.third_preference];
            const filledPrefs = prefs.filter(p => p !== null && p !== '' && p !== undefined).length;
            
            if (filledPrefs === 3) return 'complete';
            if (filledPrefs > 0) return 'partial';
            return 'none';
        }

        function getStatusBadge(status) {
            switch (status) {
                case 'complete':
                    return '<span class="badge bg-success">Complete</span>';
                case 'partial':
                    return '<span class="badge bg-warning">Partial</span>';
                case 'none':
                    return '<span class="badge bg-danger">No preferences</span>';
                default:
                    return '<span class="badge bg-secondary">Unknown</span>';
            }
        }

        function updateStats() {
            document.getElementById('teamCount').textContent = teamMembers.length;
            
            const complete = teamMembers.filter(member => getPreferenceStatus(member) === 'complete').length;
            document.getElementById('completePrefs').textContent = complete;
            
            const pending = teamMembers.reduce((sum, member) => sum + (member.pending_approvals || 0), 0);
            document.getElementById('pendingCount').textContent = pending;
        }

        function approvePreferences(memberId) {
            alert(`Approve preferences for employee ${memberId} - Coming soon!`);
        }

        function viewDetails(memberId) {
            alert(`View details for employee ${memberId} - Coming soon!`);
        }

        function showError(message) {
            const loading = document.getElementById('loading');
            loading.innerHTML = `
                <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                <p class="mt-3 text-danger">${message}</p>
                <button class="btn btn-primary" onclick="loadTeamData()">Try Again</button>
            `;
        }

        // Edit Modal Functions (reuse from index.php)
        let currentEmployeeId = null;
        let availableHolidays = [];
        let currentEmployeeData = null;

        async function openEditModal(employeeId) {
            currentEmployeeId = employeeId;
            
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
            
            document.getElementById('editLoading').style.display = 'block';
            document.getElementById('editForm').style.display = 'none';
            
            try {
                const [employeeResponse, holidaysResponse] = await Promise.all([
                    fetch(`api/employee.php?id=${employeeId}`),
                    fetch('api/holidays.php')
                ]);
                
                const employeeData = await employeeResponse.json();
                const holidaysData = await holidaysResponse.json();
                
                if (employeeData.success && holidaysData.success) {
                    currentEmployeeData = employeeData.employee;
                    availableHolidays = holidaysData.holidays;
                    
                    populateEditForm();
                } else {
                    throw new Error(employeeData.error || holidaysData.error);
                }
                
            } catch (error) {
                showEditError('Error loading employee data: ' + error.message);
            }
        }

        function populateEditForm() {
            document.getElementById('employeeName').textContent = currentEmployeeData.team_member;
            document.getElementById('employeeSapid').textContent = currentEmployeeData.sapid;
            
            const selects = ['pref1', 'pref2', 'pref3'];
            selects.forEach(selectId => {
                const select = document.getElementById(selectId);
                select.innerHTML = '<option value="">Select holiday...</option>';
                
                availableHolidays.forEach(holiday => {
                    const option = document.createElement('option');
                    option.value = holiday.id;
                    option.textContent = holiday.holiday_name;
                    select.appendChild(option);
                });
            });
            
            if (currentEmployeeData.preferences) {
                currentEmployeeData.preferences.forEach(pref => {
                    const selectId = `pref${pref.rank}`;
                    const select = document.getElementById(selectId);
                    const removeBtn = document.getElementById(`removePref${pref.rank}`);
                    
                    if (select && pref.holiday_id) {
                        select.value = pref.holiday_id;
                        if (removeBtn) removeBtn.style.display = 'inline-block';
                    }
                });
            }
            
            selects.forEach((selectId, index) => {
                const select = document.getElementById(selectId);
                const removeBtn = document.getElementById(`removePref${index + 1}`);
                
                select.addEventListener('change', function() {
                    if (removeBtn) removeBtn.style.display = this.value ? 'inline-block' : 'none';
                });
            });
            
            document.getElementById('editLoading').style.display = 'none';
            document.getElementById('editForm').style.display = 'block';
        }

        function removePreference(rank) {
            const select = document.getElementById(`pref${rank}`);
            const removeBtn = document.getElementById(`removePref${rank}`);
            
            select.value = '';
            if (removeBtn) removeBtn.style.display = 'none';
        }

        async function savePreferences() {
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            try {
                const preferences = [];
                for (let rank = 1; rank <= 3; rank++) {
                    const select = document.getElementById(`pref${rank}`);
                    if (select.value) {
                        preferences.push({
                            rank: rank,
                            holiday_id: parseInt(select.value)
                        });
                    }
                }
                
                const holidayIds = preferences.map(p => p.holiday_id);
                const uniqueIds = [...new Set(holidayIds)];
                if (holidayIds.length !== uniqueIds.length) {
                    throw new Error('Cannot select the same holiday for multiple preferences');
                }
                
                const response = await fetch('api/save_preferences.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        employee_id: currentEmployeeId,
                        preferences: preferences
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                    loadTeamData();
                    showSuccessMessage('Preferences updated successfully!');
                } else {
                    throw new Error(result.error);
                }
                
            } catch (error) {
                showEditError('Error saving preferences: ' + error.message);
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
            }
        }

        function showEditError(message) {
            const editLoading = document.getElementById('editLoading');
            editLoading.innerHTML = `
                <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                <p class="mt-3 text-danger">${message}</p>
                <button class="btn btn-primary" onclick="openEditModal(${currentEmployeeId})">Try Again</button>
            `;
        }

        function showSuccessMessage(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <i class="fas fa-check-circle"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
        }

        // Fancy header scroll effects
        let lastScrollY = window.scrollY;
        const header = document.querySelector('.coles-header');
        
        function updateScrollEffects() {
            const currentScrollY = window.scrollY;
            
            if (currentScrollY > 50) {
                // Scrolled down - compact header
                header.classList.add('scrolled');
            } else {
                // At top - normal header
                header.classList.remove('scrolled');
            }
            
            lastScrollY = currentScrollY;
        }
        
        // Throttled scroll listener for better performance
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    updateScrollEffects();
                    ticking = false;
                });
                ticking = true;
            }
        });
        
        // Initial check
        updateScrollEffects();
    </script>
</body>
</html>
