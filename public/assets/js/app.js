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
        
        new DataTable(table, options);
    });
});

// Flash Message Auto-hide
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
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
function showAlert(type, message, autoHide = true) {
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
        timer: autoHide ? 5000 : undefined,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
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

        // Fallback: if not secure (so native prompt won't fire) show banner after a short delay
        if (!isSecureContextLike) {
            setTimeout(function(){
                const pwaBanner = document.getElementById('pwaInstallBanner');
                if (pwaBanner && !hasShownBanner) {
                    pwaBanner.classList.remove('d-none');
                    hasShownBanner = true;
                }
            }, 1200);
        }
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

// Handle install button click
function handlePwaInstallClick() {
    if (!deferredInstallPrompt) return;
    deferredInstallPrompt.prompt();
    deferredInstallPrompt.userChoice.then((choiceResult) => {
        const pwaBanner = document.getElementById('pwaInstallBanner');
        if (pwaBanner) {
            pwaBanner.classList.add('d-none');
        }
        deferredInstallPrompt = null;
    });
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
        showAlert('success', 'Unit updated successfully');
        const modal = bootstrap.Modal.getInstance(document.getElementById('editUnitModal'));
        if (modal) {
            modal.hide();
        }
        
        // Reload the page after a short delay
        setTimeout(() => window.location.reload(), 1000);
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
            document.getElementById('viewPropertyName').textContent = property.name;
            document.getElementById('viewPropertyAddress').textContent = property.address;
            document.getElementById('viewPropertyCity').textContent = property.city;
            document.getElementById('viewPropertyState').textContent = property.state;
            document.getElementById('viewPropertyZip').textContent = property.zip_code;
            document.getElementById('viewPropertyType').textContent = property.property_type;
            document.getElementById('viewPropertyYear').textContent = property.year_built || 'N/A';
            document.getElementById('viewPropertyArea').textContent = property.total_area ? `${property.total_area} sq ft` : 'N/A';
            document.getElementById('viewPropertyDescription').textContent = property.description || 'No description available';
            
            // Update statistics
            document.getElementById('viewPropertyTotalUnits').textContent = property.total_units || '0';
            document.getElementById('viewPropertyOccupiedUnits').textContent = property.occupied_units || '0';
            document.getElementById('viewPropertyVacantUnits').textContent = 
                (property.total_units - property.occupied_units) || '0';
            document.getElementById('viewPropertyMonthlyRevenue').textContent = 
                formatCurrency(property.monthly_revenue || 0);
            
            // Calculate and update occupancy rate
            const occupancyRate = property.total_units > 0 
                ? ((property.occupied_units / property.total_units) * 100).toFixed(1)
                : '0.0';
            document.getElementById('viewPropertyOccupancyRate').textContent = `${occupancyRate}%`;
            
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

            showAlert('success', 'Property deleted successfully');
            setTimeout(() => window.location.reload(), 1000);

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
            modal.hide();
            
            // Show success message and reload page
            showAlert('success', data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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

                showAlert('success', 'Property updated successfully');
                const modal = bootstrap.Modal.getInstance(document.getElementById('editPropertyModal'));
                modal.hide();
                setTimeout(() => window.location.reload(), 1000);

            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while updating the property');
            }
        });
    }
}); 