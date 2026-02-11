// Ensure BASE_URL is available globally
if (typeof window.BASE_URL === 'undefined' || window.BASE_URL === null) {
    // Fallback: detect base URL from current location
    const pathArray = window.location.pathname.split('/');
    const basePath = pathArray.length > 1 ? '/' + pathArray[1] : '';
    window.BASE_URL = basePath;
}

// Create a global BASE_URL variable for easier access.
// Use 'var' so re-loading this script won't throw redeclaration errors.
if (typeof window.BASE_URL_CONSTANT === 'undefined') {
    window.BASE_URL_CONSTANT = window.BASE_URL || '';
}
// Expose as global variable (idempotent across multiple loads)
// eslint-disable-next-line no-var
var BASE_URL = window.BASE_URL_CONSTANT || window.BASE_URL || '';

// Debug BASE_URL in development
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    try { console.log('BASE_URL detected:', BASE_URL); } catch (e) {}
}

// DataTables Default Configuration
$.extend(true, $.fn.dataTable.defaults, {
    responsive: true,
    language: {
        search: "_INPUT_",
        searchPlaceholder: "Search...",
        lengthMenu: "_MENU_ records per page",
        info: "Showing _START_ to _END_ of _TOTAL_ records",
        infoEmpty: "Showing 0 to 0 of 0 records",
        emptyTable: "No data available",
        infoFiltered: "(filtered from _MAX_ total records)"
    },
    dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
         "<'row'<'col-sm-12'tr>>" +
         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
    order: [[0, "desc"]],
    responsive: {
        details: {
            type: 'column',
            target: 'tr'
        }
    }
});

// Initialize all DataTables
document.addEventListener('DOMContentLoaded', function() {
    const tables = document.querySelectorAll('.datatable');
    tables.forEach(table => {
        const options = {
            // Get custom options from data attributes
            pageLength: table.dataset.pageLength || 10,
            order: table.dataset.order ? JSON.parse(table.dataset.order) : [[0, "desc"]]
        };
        // If jQuery DataTables is present, skip vanilla init to avoid double initialization
        if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.DataTable) {
            return;
        }
        if (typeof window.DataTable === 'function') {
            new DataTable(table, options);
        }
    });
});

// Flash Message Auto-hide
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert[data-auto-hide="1"]');
    alerts.forEach(alert => {
        setTimeout(() => {
            try {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } catch (e) {}
        }, 5000);
    });
});

// Form Validation
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// AJAX Form Submission
function submitFormAjax(form, successCallback = null) {
    if (!validateForm(form)) {
        return false;
    }

    const formData = new FormData(form);
    const submitButton = form.querySelector('[type="submit"]');
    
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
    }

    fetch(form.action, {
        method: form.method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (successCallback) {
                successCallback(data);
            } else {
                window.location.reload();
            }
        } else {
            showAlert('error', data.message || 'An error occurred');
        }
    })
    .catch(error => {
        showAlert('error', 'An error occurred while processing your request');
        console.error('Error:', error);
    })
    .finally(() => {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = submitButton.dataset.originalText || 'Submit';
        }
    });

    return false;
}

// Alert Helper with SweetAlert2
function showAlert(type, message, autoHide = false, callback = null) {
    // Map type to SweetAlert2 icon
    const iconMap = {
        'success': 'success',
        'error': 'error',
        'danger': 'error',
        'warning': 'warning',
        'info': 'info'
    };
    
    const icon = iconMap[type] || 'info';
    
    // Show SweetAlert2 toast
    Swal.fire({
        icon: icon,
        title: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        showCloseButton: true,
        timer: autoHide ? 5000 : undefined,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        },
        willClose: () => {
            // Execute callback when toast is closing
            if (callback) {
                console.log('Executing SweetAlert callback');
                callback();
            }
        }
    });
}

// Print Helper
function printElement(elementId) {
    const element = document.getElementById(elementId);
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = element.innerHTML;
    window.print();
    document.body.innerHTML = originalContents;
    
    // Reinitialize any necessary JavaScript
    location.reload();
}

// Format Currency
function formatCurrency(amount, currency = 'KES') {
    // Format for Kenyan Shillings
    const formatted = new Intl.NumberFormat('en-KE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
    return 'KSh ' + formatted;
}

// Format Date
function formatDate(date, format = 'long') {
    const options = format === 'long' 
        ? { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
        : { year: 'numeric', month: 'short', day: 'numeric' };
    
    return new Date(date).toLocaleDateString('en-US', options);
}

// Confirm Action
function confirmAction(message = 'Are you sure you want to proceed?') {
    return new Promise((resolve) => {
        const modal = new bootstrap.Modal(document.createElement('div'));
        modal.element.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Action</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary confirm-btn">Confirm</button>
                    </div>
                </div>
            </div>
        `;
        
        modal.element.querySelector('.confirm-btn').addEventListener('click', () => {
            modal.hide();
            resolve(true);
        });
        
        modal.element.addEventListener('hidden.bs.modal', () => {
            resolve(false);
        });
        
        modal.show();
    });
}

// Initialize Tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
});

// PWA: Service worker registration and custom install prompt
let deferredInstallPrompt = null;
let hasShownBanner = false;

// Register service worker
const isSecureContextLike = (location.protocol === 'https:' || location.hostname === 'localhost');

if ('serviceWorker' in navigator && isSecureContextLike) {
    window.addEventListener('load', function() {
        const swUrl = (window.BASE_URL || '') + '/sw.js';
        navigator.serviceWorker.register(swUrl, { scope: (window.BASE_URL || '') + '/' })
          .then(function(reg){
            console.log('ServiceWorker registered with scope:', reg.scope);
          })
          .catch(function(err) {
            console.error('ServiceWorker registration failed: ', err);
          });
    });
}

// Listen for beforeinstallprompt to show custom UI
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    console.log('beforeinstallprompt fired');

    const pwaBanner = document.getElementById('pwaInstallBanner');
    if (pwaBanner) {
        pwaBanner.classList.remove('d-none');
        hasShownBanner = true;
    }
});

// Installed handler for diagnostics
window.addEventListener('appinstalled', () => {
    console.log('PWA was installed');
});

// Quick eligibility diagnostics
document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('PWA diag', {
            location: window.location.href,
            baseUrl: window.BASE_URL,
            manifest: (function(){
                const l = document.querySelector('link[rel="manifest"]');
                return l ? l.href : null;
            })(),
            isSecure: isSecureContextLike,
            swSupported: 'serviceWorker' in navigator
        });

        // Fallback: show banner even if beforeinstallprompt doesn't fire.
        // Clicking "Install App" will either prompt (if available) or show manual instructions.
        setTimeout(function(){
            try {
                const isStandalone = (
                    (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
                    (window.navigator && window.navigator.standalone)
                );
                const pwaBanner = document.getElementById('pwaInstallBanner');
                if (pwaBanner && !hasShownBanner && !isStandalone) {
                    pwaBanner.classList.remove('d-none');
                    hasShownBanner = true;
                }
            } catch (e) {}
        }, 1200);
    } catch (e) {}
});

// Manual instructions fallback
function showPwaInstallHelp() {
    const el = document.getElementById('pwaInstallHelpModal');
    if (!el) return;
    const modal = new bootstrap.Modal(el);
    modal.show();
}

// Enhance banner button behavior: if no deferred prompt, show help
function handlePwaInstallClick() {
    if (deferredInstallPrompt) {
        deferredInstallPrompt.prompt();
        deferredInstallPrompt.userChoice.then(() => {
            const pwaBanner = document.getElementById('pwaInstallBanner');
            if (pwaBanner) pwaBanner.classList.add('d-none');
            deferredInstallPrompt = null;
        });
    } else {
        showPwaInstallHelp();
    }
}

// Hide banner when dismissed
function dismissPwaBanner() {
    const pwaBanner = document.getElementById('pwaInstallBanner');
    if (pwaBanner) {
        pwaBanner.classList.add('d-none');
    }
}

// Edit Unit
function editUnit(unitId) {
    // Show loading state
    const loadingAlert = showAlert('info', 'Loading unit details...', false);
    
    // Make sure we have the unit ID
    if (!unitId) {
        showAlert('error', 'Invalid unit ID');
        return;
    }

    // Use the correct URL path
    fetch(`${BASE_URL}/units/get/${unitId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        // Remove loading alert
        if (loadingAlert && loadingAlert.parentNode) {
            loadingAlert.remove();
        }

        if (!response.ok) {
            if (response.status === 404) {
                throw new Error('Unit not found');
            }
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Failed to load unit details');
        }

        const unit = data.unit;
        
        // Make sure we have the unit data
        if (!unit) {
            throw new Error('No unit data received');
        }

        // Make sure all required elements exist before trying to populate them
        const elements = {
            'edit_unit_id': unit.id,
            'edit_unit_number': unit.unit_number,
            'edit_type': unit.type,
            'edit_size': unit.size || '',
            'edit_rent_amount': unit.rent_amount,
            'edit_status': unit.status
        };

        // Check if all elements exist before proceeding
        for (const [elementId, value] of Object.entries(elements)) {
            const element = document.getElementById(elementId);
            if (!element) {
                throw new Error(`Required element #${elementId} not found`);
            }
            element.value = value;
        }

        // Show the modal
        const editModal = document.getElementById('editUnitModal');
        if (!editModal) {
            throw new Error('Edit modal not found');
        }
        const bsModal = new bootstrap.Modal(editModal);
        bsModal.show();
        
        // Load existing files for editing if the function exists
        if (typeof loadUnitFilesForEdit === 'function') {
            loadUnitFilesForEdit(unit.id);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', error.message || 'An error occurred while loading unit details');
    });
}

// Handle Unit Edit Form Submission
function handleUnitEdit(event) {
    event.preventDefault();
    const form = event.target;
    const unitId = document.getElementById('edit_unit_id').value;
    const submitBtn = form.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    
    // Disable submit button and show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
    
    fetch(`${BASE_URL}/units/update/${unitId}`, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            // Try to get error details from response
            return response.text().then(text => {
                let errorMessage = `Network response was not ok: ${response.status}`;
                try {
                    const errorData = JSON.parse(text);
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                } catch (e) {
                    // If response is not JSON, use the text or status
                    if (text) {
                        errorMessage = text;
                    }
                }
                throw new Error(errorMessage);
            });
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Error updating unit');
        }
        
        // Show success message and close modal
        showAlert('success', 'Unit updated successfully', true, () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUnitModal'));
            if (modal) {
                modal.hide();
            }
            window.location.reload();
        });
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', error.message || 'An error occurred while updating the unit');
    })
    .finally(() => {
        // Re-enable submit button and restore original text
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });

    return false;
}

// Global variable to store current property ID (guard against multiple script loads)
if (typeof window.currentPropertyId === 'undefined') {
    window.currentPropertyId = null;
}

// View Property - Only define if it doesn't already exist (prevent duplicate declaration)
if (typeof window.viewProperty === 'undefined') {
    window.viewProperty = async function(id) {
        try {
            const response = await fetch(`${BASE_URL}/properties/get/${id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const payload = await response.json();
            const property = payload && payload.property ? payload.property : payload;
            
            // Update modal fields
            document.getElementById('view_name').textContent = property.name;
            document.getElementById('view_address').textContent = property.address;
            document.getElementById('view_city').textContent = property.city;
            document.getElementById('view_state').textContent = property.state;
            document.getElementById('view_zip_code').textContent = property.zip_code;
            document.getElementById('view_property_type').textContent = property.property_type;
            document.getElementById('view_year_built').textContent = property.year_built || 'N/A';
            document.getElementById('view_total_area').textContent = property.total_area ? `${property.total_area} sq ft` : 'N/A';
            
            // Update statistics
            document.getElementById('view_total_units').textContent = property.units_count || '0';
            document.getElementById('view_occupancy_rate').textContent = `${Math.round(property.occupancy_rate || 0)}%`;
            document.getElementById('view_monthly_income').textContent = formatCurrency(property.monthly_income || 0);
            document.getElementById('view_vacant_units').textContent = 
                property.units_count ? (property.units_count - Math.round(property.units_count * ((property.occupancy_rate || 0) / 100))) : 0;
            
            // Show the modal
            const viewModal = new bootstrap.Modal(document.getElementById('viewPropertyModal'));
            viewModal.show();
        } catch (error) {
            console.error('Error fetching property details:', error);
            showAlert('error', 'Error fetching property details. Please try again.');
        }
    };
}

// Load Units for Property
function loadUnits(propertyId) {
    const unitSelect = document.getElementById('unit_id');
    const rentInput = document.getElementById('rent_amount');
    
    // Reset and disable unit select
    unitSelect.innerHTML = '<option value="">Select Unit</option>';
    unitSelect.disabled = true;
    rentInput.value = '';
    
    if (!propertyId) {
        return;
    }
    
    // Show loading state
    unitSelect.innerHTML = '<option value="">Loading units...</option>';
    
    fetch(`${BASE_URL}/properties/${propertyId}/units`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.units) {
                unitSelect.innerHTML = '<option value="">Select Unit</option>';
                data.units.forEach(unit => {
                    if (unit.status !== 'occupied') {
                        const option = document.createElement('option');
                        option.value = unit.id;
                        option.textContent = `Unit ${unit.unit_number} - Ksh${parseFloat(unit.rent_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}/month`;
                        option.dataset.rent = unit.rent_amount;
                        unitSelect.appendChild(option);
                    }
                });
                // If no vacant units found
                if (unitSelect.options.length === 1) {
                    unitSelect.innerHTML = '<option value="">No vacant units available</option>';
                }
                unitSelect.disabled = false;
            } else {
                unitSelect.innerHTML = '<option value="">No units available</option>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            unitSelect.innerHTML = '<option value="">Error loading units</option>';
        });
}

// Handle Unit Selection
function handleUnitSelection(event) {
    const selectedOption = event.target.options[event.target.selectedIndex];
    const rentInput = document.getElementById('rent_amount');
    
    if (selectedOption && selectedOption.dataset.rent) {
        rentInput.value = selectedOption.dataset.rent;
    } else {
        rentInput.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // ... existing DOMContentLoaded code ...
    
    // Property and Unit Selection
    const propertySelect = document.getElementById('property_id');
    const unitSelect = document.getElementById('unit_id');
    
    if (propertySelect) {
        propertySelect.addEventListener('change', (e) => loadUnits(e.target.value));
    }
    
    if (unitSelect) {
        unitSelect.addEventListener('change', handleUnitSelection);
    }
});

// Property Management Functions - editProperty is defined in properties/index.php
// Only define deleteProperty if it doesn't already exist (prevent duplicate declaration)
if (typeof window.deleteProperty === 'undefined') {
    window.deleteProperty = async (id) => {
        if (!confirm('Are you sure you want to delete this property? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(`${BASE_URL}/properties/delete/${id}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to delete property');
            }

            showAlert('success', 'Property deleted successfully', true, () => {
                window.location.reload();
            });

        } catch (error) {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while deleting the property');
        }
    };
}

// Show Add Unit Modal
function showAddUnitModal() {
    const modal = new bootstrap.Modal(document.getElementById('addUnitModal'));
    modal.show();
}

// Handle Unit Submit
function handleUnitSubmit(event) {
    event.preventDefault();
    const form = event.target;
    
    // Validate form
    if (!validateForm(form)) {
        return false;
    }

    // Submit form via AJAX
    const formData = new FormData(form);
    const submitButton = form.querySelector('[type="submit"]');
    
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
    }

    fetch(`${BASE_URL}/units/store`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addUnitModal'));
            if (modal) {
                modal.hide();
            }
            
            // Show success message and reload after toast
            showAlert('success', data.message, true, () => {
                window.location.reload();
            });
        } else if (data && data.over_limit) {
            showUpgradeLimitModal({
                type: data.type || 'unit',
                limit: data.limit,
                current: data.current,
                plan: data.plan,
                upgrade_url: data.upgrade_url,
                message: data.message || 'You have reached your plan limit. Please upgrade to continue.'
            });
        } else {
            showAlert('error', data.message || 'An error occurred');
        }
    })
    .catch(error => {
        showAlert('error', 'An error occurred while processing your request');
        console.error('Error:', error);
    })
    .finally(() => {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Add Unit';
        }
    });

    return false;
}

// Handle Property Submit
function handlePropertySubmit(event) {
    event.preventDefault();
    const form = event.target;
    
    // Validate form
    if (!validateForm(form)) {
        return false;
    }

    // Submit form via AJAX
    const formData = new FormData(form);
    const submitButton = form.querySelector('[type="submit"]');
    
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding Property...';
    }

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addPropertyModal'));
            if (modal) {
                modal.hide();
            }
            
            // Show success message and reload after toast
            showAlert('success', data.message, true, () => {
                window.location.reload();
            });
        } else if (data && data.over_limit) {
            showUpgradeLimitModal({
                type: data.type || 'property',
                limit: data.limit,
                current: data.current,
                plan: data.plan,
                upgrade_url: data.upgrade_url,
                message: data.message || 'You have reached your plan limit. Please upgrade to continue.'
            });
        } else {
            showAlert('error', data.message || 'An error occurred while adding the property');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while processing your request');
    })
    .finally(() => {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Add Property';
        }
    });

    return false;
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', () => {
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

    // Initialize DataTable - only if not already initialized
    const datatables = document.querySelectorAll('.datatable');
    datatables.forEach(table => {
        // Check if DataTable is already initialized on this table
        if (!$.fn.DataTable.isDataTable(table)) {
            $(table).DataTable({
                order: [[0, 'asc']],
                columnDefs: [
                    { targets: 'no-sort', orderable: false }
                ]
            });
        }
    });

    // Handle edit form submission
    const editForm = document.getElementById('editPropertyForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(editForm);
            const propertyId = formData.get('id');

            try {
                const response = await fetch(`${BASE_URL}/properties/update/${propertyId}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    // Try to get error details from response
                    const text = await response.text();
                    let errorMessage = `Network response was not ok: ${response.status}`;
                    try {
                        const errorData = JSON.parse(text);
                        if (errorData.message) {
                            errorMessage = errorData.message;
                        }
                    } catch (e) {
                        // If response is not JSON, use the text or status
                        if (text) {
                            errorMessage = text;
                        }
                    }
                    throw new Error(errorMessage);
                }

                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Failed to update property');
                }

                showAlert('success', 'Property updated successfully', true, () => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editPropertyModal'));
                    if (modal) {
                        modal.hide();
                    }
                    window.location.reload();
                });

            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while updating the property');
            }
        });
    }
}); 

// Show Upgrade Limit Modal helper
function showUpgradeLimitModal(opts) {
    try {
        const modalEl = document.getElementById('upgradeLimitModal');
        if (!modalEl) {
            // Fallback toast if modal markup not present on this page
            const msg = opts && opts.message ? opts.message : 'Plan limit reached. Please upgrade to continue.';
            showAlert('warning', msg, false);
            return;
        }
        // If another Bootstrap modal is open (e.g., Add Property), hide it first to avoid z-index/backdrop capture
        try {
            document.querySelectorAll('.modal.show').forEach(el => {
                const inst = bootstrap.Modal.getInstance(el);
                if (inst) inst.hide();
            });
        } catch (e) {}

        // Ensure the upgrade modal is attached to <body> for proper stacking
        try {
            if (modalEl.parentNode && modalEl.parentNode !== document.body) {
                document.body.appendChild(modalEl);
            }
        } catch (e) {}
        const titleEl = modalEl.querySelector('[data-upgrade-title]');
        const msgEl = modalEl.querySelector('[data-upgrade-message]');
        const countsEl = modalEl.querySelector('[data-upgrade-counts]');
        const planEl = modalEl.querySelector('[data-upgrade-plan]');
        const ctaEl = modalEl.querySelector('[data-upgrade-cta]');
        if (titleEl) titleEl.textContent = 'Upgrade Required';
        if (msgEl) msgEl.textContent = opts.message || 'You have reached your plan limit. Please upgrade to continue.';
        if (countsEl) countsEl.textContent = (opts && opts.limit != null && opts.current != null)
            ? `${String(opts.current)} of ${String(opts.limit)} ${opts.type === 'unit' ? 'units' : 'properties'} used`
            : '';
        if (planEl) planEl.textContent = opts && opts.plan ? String(opts.plan) : '';
        if (ctaEl) {
            const targetHref = (opts && opts.upgrade_url) ? opts.upgrade_url : (typeof BASE_URL !== 'undefined' ? `${BASE_URL}/subscription/renew` : '/subscription/renew');
            ctaEl.setAttribute('href', targetHref);
            ctaEl.classList.remove('disabled');
            ctaEl.removeAttribute('disabled');
            try { ctaEl.style.pointerEvents = 'auto'; } catch(e){}
            ctaEl.addEventListener('click', function(e){ e.preventDefault(); window.location.href = targetHref; }, { once: true });
        }
        // Show after a short delay to allow previous backdrop to close
        setTimeout(function(){
            const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: true });
            modal.show();
        }, 120);
    } catch (e) { try { console.error(e); } catch(_){} }
}

// =========================
// Generic dynamic filters for DataTables across pages
// Usage examples in HTML:
// 1) Global search: <input type="text" data-dt-filter="global" data-dt-target="#tableId" placeholder="Search...">
// 2) Column search: <input type="text" data-dt-filter="column" data-dt-target="#tableId" data-dt-column-index="2" data-dt-match="contains|exact">
// 3) Attribute filter (row attribute): <select data-dt-filter="attr" data-dt-target="#tableId" data-dt-attr-name="prop-ids" data-dt-attr-mode="contains|equals">...</select>
//    Rows must have data attributes like: <tr data-prop-ids="1,2,3"> ...
// =========================
(function(){
  // Ensure only registered once
  if (window.__dtGenericFiltersInitialized) return;
  window.__dtGenericFiltersInitialized = true;

  // Storage for attribute filters per table
  const tableAttrFilters = {};

  function getTableEl(target){
    if (!target) return null;
    try {
      return document.querySelector(target);
    } catch (e) { return null; }
  }

  function getDtInstance(tableEl){
    try {
      if (window.jQuery && jQuery.fn && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(tableEl)) {
        return jQuery(tableEl).DataTable();
      }
      // v2 API may expose DataTable() instance retrieval via tableEl.DataTable
      if (tableEl && tableEl.DataTable && typeof tableEl.DataTable === 'function') {
        return tableEl.DataTable();
      }
    } catch (e) {}
    return null;
  }

  // Attribute-level filter hook (applies to any table that defines filters)
  function ensureAttrFilterHook(){
    if (window.__dtAttrFilterHookAdded) return;
    window.__dtAttrFilterHookAdded = true;
    try {
      const hook = function(settings, data, dataIndex){
        const table = settings.nTable;
        if (!table || !table.id) return true;
        const filters = tableAttrFilters[table.id];
        if (!filters || !filters.length) return true;
        const row = settings.aoData[dataIndex].nTr;
        // All filters must match
        for (let f of filters){
          const attrVal = (row.getAttribute('data-' + f.name) || '').trim();
          if (!attrVal && f.value && f.value !== 'all') return false;
          if (f.mode === 'equals') {
            if (attrVal !== String(f.value)) return false;
          } else { // contains
            const arr = attrVal.split(',').map(s => s.trim()).filter(Boolean);
            if (f.value && f.value !== 'all' && arr.indexOf(String(f.value)) === -1) return false;
          }
        }
        return true;
      };
      if (window.jQuery && jQuery.fn && jQuery.fn.dataTable && jQuery.fn.dataTable.ext && jQuery.fn.dataTable.ext.search) {
        jQuery.fn.dataTable.ext.search.push(hook);
      }
      if (window.DataTable && DataTable.ext && DataTable.ext.search) {
        DataTable.ext.search.push(hook);
      }
    } catch (e) {}
  }

  document.addEventListener('DOMContentLoaded', function(){
    ensureAttrFilterHook();

    // Wire up global/column/attr filters
    const controls = document.querySelectorAll('[data-dt-filter][data-dt-target]');
    controls.forEach(ctrl => {
      const target = ctrl.getAttribute('data-dt-target');
      const tableEl = getTableEl(target);
      if (!tableEl || !tableEl.id) return;
      const filterType = ctrl.getAttribute('data-dt-filter');

      const apply = () => {
        const dt = getDtInstance(tableEl);
        if (!dt) return;
        if (filterType === 'global') {
          dt.search(ctrl.value || '').draw();
        } else if (filterType === 'column') {
          const colIdx = parseInt(ctrl.getAttribute('data-dt-column-index') || '0', 10);
          const match = (ctrl.getAttribute('data-dt-match') || 'contains').toLowerCase();
          let val = ctrl.value || '';
          if (match === 'exact' && val) {
            val = '^' + val.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '$';
            dt.column(colIdx).search(val, true, false).draw();
          } else {
            dt.column(colIdx).search(val).draw();
          }
        } else if (filterType === 'attr') {
          const name = (ctrl.getAttribute('data-dt-attr-name') || '').trim();
          const mode = (ctrl.getAttribute('data-dt-attr-mode') || 'contains').toLowerCase();
          if (!name) return;
          if (!tableAttrFilters[tableEl.id]) tableAttrFilters[tableEl.id] = [];
          // Replace or add this filter
          const arr = tableAttrFilters[tableEl.id];
          const existingIdx = arr.findIndex(f => f.name === name);
          const value = (ctrl.value || 'all');
          const filterObj = { name, mode, value };
          if (existingIdx >= 0) arr[existingIdx] = filterObj; else arr.push(filterObj);
          dt.draw();
        }
      };

      const evt = ctrl.tagName === 'SELECT' ? 'change' : 'input';
      ctrl.addEventListener(evt, apply);
    });
  });
})();
