<?php
/**
 * Main Dashboard for Coles Preferences System
 */

require_once 'includes/auth.php';

// Require login
Auth::requireLogin();
$user = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coles Preferences Dashboard</title>
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
            background: linear-gradient(135deg, var(--coles-red) 0%, var(--coles-dark-red) 100%);
            box-shadow: 0 4px 12px rgba(227, 6, 19, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            overflow: hidden;
            padding: 1rem 0;
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
            color: white;
        }
        
        .coles-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 300;
            color: white;
        }
        
        .eastern-creek-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.3);
            font-size: 0.8rem;
            backdrop-filter: blur(10px);
        }
        
        .table th { 
            background: linear-gradient(135deg, var(--coles-light-red) 0%, #ffffff 100%);
            color: var(--coles-dark-red);
            font-weight: 600;
            border: none;
        }
        
        .preference-cell { 
            font-size: 0.9em; 
            max-width: 150px;
        }
        
        .employee-row:hover { 
            background: linear-gradient(135deg, var(--coles-light-red) 0%, #ffffff 100%);
            transform: translateY(-1px);
            transition: all 0.3s ease;
        }
        
        .status-badge { font-size: 0.8em; }
        .loading { text-align: center; padding: 2rem; }
        
        .stats-card { 
            background: linear-gradient(135deg, var(--coles-red) 0%, var(--coles-dark-red) 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(227, 6, 19, 0.2);
            transition: all 0.3s ease;
        }
        
        .stats-section {
            margin-bottom: 2rem;
            padding: 1rem 0;
        }
        
        .stats-card.bg-success {
            background: linear-gradient(135deg, var(--coles-green) 0%, var(--coles-dark-green) 100%) !important;
        }
        
        .stats-card.bg-warning {
            background: linear-gradient(135deg, var(--coles-orange) 0%, #E55A2B 100%) !important;
        }
        
        .stats-card.bg-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--coles-red) 0%, var(--coles-dark-red) 100%);
            border: none;
            box-shadow: 0 2px 6px rgba(227, 6, 19, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--coles-dark-red) 0%, #A5040D 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(227, 6, 19, 0.4);
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
            background: linear-gradient(135deg, var(--coles-red) 0%, var(--coles-dark-red) 100%) !important;
            color: white !important;
            z-index: 10;
            border-bottom: 2px solid var(--coles-dark-red) !important;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 14px 12px !important;
            box-shadow: 0 2px 8px rgba(227, 6, 19, 0.3);
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        
        .table-sticky thead th:first-child {
            border-top-left-radius: 12px;
        }
        
        .table-sticky thead th:last-child {
            border-top-right-radius: 12px;
        }
        
        .table-sticky tbody tr:hover {
            background: linear-gradient(135deg, var(--coles-light-red) 0%, #ffffff 100%);
            transform: translateY(-1px);
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(227, 6, 19, 0.1);
        }
        
        .table-sticky td {
            padding: 12px;
            vertical-align: middle;
            border-color: #e9ecef;
        }
        
        /* Scrollbar styling */
        .table-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: var(--coles-red);
            border-radius: 4px;
            opacity: 0.7;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: var(--coles-dark-red);
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
                    <div class="coles-subtitle">Holiday Preferences System</div>
                </div>
                <span class="eastern-creek-badge">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    Eastern Creek Headquarters
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
                <a class="nav-link text-white me-2" href="dashboard.php" title="Back to Dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                </a>
                <a class="nav-link text-white me-2" href="#" onclick="refreshData()" title="Refresh Data">
                    <i class="fas fa-sync-alt"></i>
                </a>
                <a class="nav-link text-white" href="logout.php" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Stats Cards -->
        <div class="row mb-4 stats-section" id="statsSection">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h4 id="totalEmployees">0</h4>
                        <p class="mb-0">Total Employees</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-success">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4 id="withPreferences">0</h4>
                        <p class="mb-0">With Preferences</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h4 id="pendingApprovals">0</h4>
                        <p class="mb-0">Pending Approvals</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-info">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-2x mb-2"></i>
                        <h4 id="totalShifts">0</h4>
                        <p class="mb-0">Active Shifts</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search employees...">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="shiftFilter">
                    <option value="">All Shifts</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="complete">Complete Preferences</option>
                    <option value="partial">Partial Preferences</option>
                    <option value="none">No Preferences</option>
                </select>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fas fa-filter text-muted me-2"></i>
                            <small class="text-muted fw-bold">PREFERENCE FILTERS</small>
                        </div>
                        <div class="row g-2">
                            <div class="col-4">
                                <select class="form-select form-select-sm" id="pref1Filter">
                                    <option value="">1st Preference</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <select class="form-select form-select-sm" id="pref2Filter">
                                    <option value="">2nd Preference</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <select class="form-select form-select-sm" id="pref3Filter">
                                    <option value="">3rd Preference</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Employee Preferences
                    <span class="badge bg-secondary ms-2" id="displayCount">0</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div id="loading" class="loading">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-3">Loading employee data...</p>
                </div>
                <div class="table-container" id="tableContainer" style="display: none;">
                    <table class="table table-hover table-sticky mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>SAPID</th>
                                <th>Shift</th>
                                <th>Manager Group</th>
                                <th>1st Preference</th>
                                <th>2nd Preference</th>
                                <th>3rd Preference</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
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
                    <button type="button" class="btn btn-primary" onclick="savePreferences()" id="saveBtn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let allEmployees = [];
        let filteredEmployees = [];
        let userRole = '<?= $user['role'] ?>';
        let userShiftId = <?= $user['shift_id'] ? $user['shift_id'] : 'null' ?>;

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadData();
            loadShifts();
            
            // Set up event listeners
            document.getElementById('searchInput').addEventListener('input', filterEmployees);
            document.getElementById('shiftFilter').addEventListener('change', filterEmployees);
            document.getElementById('statusFilter').addEventListener('change', filterEmployees);
            document.getElementById('pref1Filter').addEventListener('change', filterEmployees);
            document.getElementById('pref2Filter').addEventListener('change', filterEmployees);
            document.getElementById('pref3Filter').addEventListener('change', filterEmployees);
            
            // Check if we should auto-open edit modal from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const editEmployeeId = urlParams.get('edit');
            if (editEmployeeId) {
                // Wait a bit for data to load, then open edit modal
                setTimeout(() => {
                    editEmployee(parseInt(editEmployeeId));
                }, 1000);
            }
        });

        async function loadData() {
            try {
                const response = await fetch('api/employees.php');
                const data = await response.json();
                
                if (data.success) {
                    allEmployees = data.employees;
                    filteredEmployees = [...allEmployees];
                    
                    displayEmployees();
                    updateStats();
                } else {
                    if (data.error && data.error.includes('Access denied')) {
                        window.location.href = 'login.php';
                    } else {
                        showError('Failed to load employee data: ' + (data.error || 'Unknown error'));
                    }
                }
            } catch (error) {
                showError('Error loading data: ' + error.message);
            }
        }

        async function loadShifts() {
            try {
                const response = await fetch('api/shifts.php');
                const data = await response.json();
                
                if (data.success) {
                    const shiftFilter = document.getElementById('shiftFilter');
                    data.shifts.forEach(shift => {
                        const option = document.createElement('option');
                        option.value = shift.name;
                        option.textContent = shift.name;
                        shiftFilter.appendChild(option);
                    });
                    
                    document.getElementById('totalShifts').textContent = data.shifts.length;
                }
            } catch (error) {
                console.error('Error loading shifts:', error);
            }
        }

        function displayEmployees() {
            const tbody = document.getElementById('employeeTableBody');
            const loading = document.getElementById('loading');
            const tableContainer = document.getElementById('tableContainer');
            
            loading.style.display = 'none';
            tableContainer.style.display = 'block';
            
            tbody.innerHTML = '';
            
            if (filteredEmployees.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">No employees found</td></tr>';
                return;
            }
            
            filteredEmployees.forEach(employee => {
                const row = document.createElement('tr');
                row.className = 'employee-row';
                
                const status = getPreferenceStatus(employee);
                const statusBadge = getStatusBadge(status);
                
                row.innerHTML = `
                    <td>
                        <strong>${employee.team_member}</strong>
                        ${employee.preferred_name ? `<br><small class="text-muted">(${employee.preferred_name})</small>` : ''}
                    </td>
                    <td><code>${employee.sapid}</code></td>
                    <td>${employee.shift_name || '<span class="text-muted">Not assigned</span>'}</td>
                    <td><small>${employee.manager_group || '<span class="text-muted">None</span>'}</small></td>
                    <td class="preference-cell">${employee.first_preference || '<span class="text-muted">-</span>'}</td>
                    <td class="preference-cell">${employee.second_preference || '<span class="text-muted">-</span>'}</td>
                    <td class="preference-cell">${employee.third_preference || '<span class="text-muted">-</span>'}</td>
                    <td>${statusBadge}</td>
                    <td>
                        ${getActionButtons(employee)}
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            document.getElementById('displayCount').textContent = filteredEmployees.length;
        }

        function getPreferenceStatus(employee) {
            const prefs = [employee.first_preference, employee.second_preference, employee.third_preference];
            const filledPrefs = prefs.filter(p => p !== null && p !== '' && p !== undefined).length;
            
            if (filledPrefs === 3) return 'complete';
            if (filledPrefs > 0) return 'partial';
            return 'none';
        }

        function getStatusBadge(status) {
            switch (status) {
                case 'complete':
                    return '<span class="badge bg-success status-badge">Complete</span>';
                case 'partial':
                    return '<span class="badge bg-warning status-badge">Partial</span>';
                case 'none':
                    return '<span class="badge bg-danger status-badge">No preferences</span>';
                default:
                    return '<span class="badge bg-secondary status-badge">Unknown</span>';
            }
        }

        function filterEmployees() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const shiftFilter = document.getElementById('shiftFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const pref1Filter = document.getElementById('pref1Filter').value;
            const pref2Filter = document.getElementById('pref2Filter').value;
            const pref3Filter = document.getElementById('pref3Filter').value;
            
            filteredEmployees = allEmployees.filter(employee => {
                const matchesSearch = !searchTerm || 
                    employee.team_member.toLowerCase().includes(searchTerm) ||
                    employee.sapid.toLowerCase().includes(searchTerm) ||
                    (employee.preferred_name && employee.preferred_name.toLowerCase().includes(searchTerm));
                
                const matchesShift = !shiftFilter || employee.shift_name === shiftFilter;
                
                const matchesStatus = !statusFilter || getPreferenceStatus(employee) === statusFilter;
                
                const matchesPref1 = !pref1Filter || employee.first_preference === pref1Filter;
                const matchesPref2 = !pref2Filter || employee.second_preference === pref2Filter;
                const matchesPref3 = !pref3Filter || employee.third_preference === pref3Filter;
                
                return matchesSearch && matchesShift && matchesStatus && matchesPref1 && matchesPref2 && matchesPref3;
            });
            
            displayEmployees();
        }

        function getActionButtons(employee) {
            let buttons = '';
            
            // Edit button - only for admins or shift managers for their own shift
            if (userRole === 'admin' || (userRole === 'shift_manager' && employee.shift_id === userShiftId)) {
                buttons += `
                    <button class="btn btn-sm btn-outline-primary" onclick="editEmployee(${employee.id})" title="Edit Preferences">
                        <i class="fas fa-edit"></i>
                    </button>
                `;
            }
            
            // View button - always available
            buttons += `
                <button class="btn btn-sm btn-outline-secondary" onclick="viewEmployee(${employee.id})" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
            `;
            
            return buttons;
        }

        function updateStats() {
            document.getElementById('totalEmployees').textContent = allEmployees.length;
            
            const withPrefs = allEmployees.filter(emp => getPreferenceStatus(emp) !== 'none').length;
            document.getElementById('withPreferences').textContent = withPrefs;
            
            const pending = allEmployees.reduce((sum, emp) => sum + (emp.pending_approvals || 0), 0);
            document.getElementById('pendingApprovals').textContent = pending;
            
            // Populate holiday filters
            populateHolidayFilters();
        }
        
        function populateHolidayFilters() {
            const holidays1 = new Set();
            const holidays2 = new Set();
            const holidays3 = new Set();
            
            allEmployees.forEach(emp => {
                if (emp.first_preference) holidays1.add(emp.first_preference);
                if (emp.second_preference) holidays2.add(emp.second_preference);
                if (emp.third_preference) holidays3.add(emp.third_preference);
            });
            
            const pref1Filter = document.getElementById('pref1Filter');
            const pref2Filter = document.getElementById('pref2Filter');
            const pref3Filter = document.getElementById('pref3Filter');
            
            // Clear existing options except first
            [pref1Filter, pref2Filter, pref3Filter].forEach(filter => {
                while (filter.children.length > 1) {
                    filter.removeChild(filter.lastChild);
                }
            });
            
            // Add sorted holidays to each filter
            [...holidays1].sort().forEach(holiday => {
                const option = document.createElement('option');
                option.value = holiday;
                option.textContent = holiday;
                pref1Filter.appendChild(option);
            });
            
            [...holidays2].sort().forEach(holiday => {
                const option = document.createElement('option');
                option.value = holiday;
                option.textContent = holiday;
                pref2Filter.appendChild(option);
            });
            
            [...holidays3].sort().forEach(holiday => {
                const option = document.createElement('option');
                option.value = holiday;
                option.textContent = holiday;
                pref3Filter.appendChild(option);
            });
        }

        function refreshData() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('tableContainer').style.display = 'none';
            loadData();
        }

        // Edit modal functions (similar to Flask version)
        let currentEmployeeId = null;
        let availableHolidays = [];
        let currentEmployeeData = null;

        async function editEmployee(employeeId) {
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
                console.log('Starting save preferences...');
                
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
                
                console.log('Preferences to save:', preferences);
                console.log('Employee ID:', currentEmployeeId);
                
                const holidayIds = preferences.map(p => p.holiday_id);
                const uniqueIds = [...new Set(holidayIds)];
                if (holidayIds.length !== uniqueIds.length) {
                    throw new Error('Cannot select the same holiday for multiple preferences');
                }
                
                const requestData = {
                    employee_id: currentEmployeeId,
                    preferences: preferences
                };
                
                console.log('Sending request:', requestData);
                
                const response = await fetch('api/save_preferences.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });
                
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Response result:', result);
                
                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                    refreshData();
                    showSuccessMessage('Preferences updated successfully!');
                } else {
                    throw new Error(result.error);
                }
                
            } catch (error) {
                console.error('Save preferences error:', error);
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
                <button class="btn btn-primary" onclick="editEmployee(${currentEmployeeId})">Try Again</button>
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

        function viewEmployee(employeeId) {
            alert('Employee details view - coming soon!');
        }

        function showError(message) {
            const loading = document.getElementById('loading');
            loading.innerHTML = `
                <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                <p class="mt-3 text-danger">${message}</p>
                <button class="btn btn-primary" onclick="refreshData()">Try Again</button>
            `;
        }

    </script>
</body>
</html>
