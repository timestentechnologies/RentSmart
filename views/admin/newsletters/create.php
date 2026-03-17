<?php
ob_start();
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Newsletter</h1>
        <a href="<?= BASE_URL ?>/admin/newsletters" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Newsletters
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/admin/newsletters/create" enctype="multipart/form-data">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Newsletter Content</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Newsletter Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Email Subject *</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Email Content *</label>
                            <textarea class="form-control" id="content" name="content" rows="15" required></textarea>
                            <small class="text-muted">You can use HTML formatting for rich content.</small>
                        </div>
                        
                        <!-- Rich Text Editor Toolbar -->
                        <div class="mb-3">
                            <label class="form-label">Quick HTML Templates</label>
                            <div class="row g-2 mb-2">
                                <div class="col-12">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="insertTemplate('logo')">Logo</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="insertTemplate('header')">Header</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="insertTemplate('subheader')">Subheader</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="insertTemplate('paragraph')">Paragraph</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="insertTemplate('button')">Button</button>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-12">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="insertTemplate('greeting')">Greeting</button>
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="insertTemplate('userName')">User Name</button>
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="insertTemplate('userEmail')">User Email</button>
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="insertTemplate('personalMessage')">Personal Message</button>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="insertTemplate('image')">Image</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="insertTemplate('list')">List</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="insertTemplate('quote')">Quote</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="insertTemplate('signature')">Signature</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="insertTemplate('divider')">Divider</button>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">
                                <strong>Dynamic Variables:</strong> {name} - User's full name | {email} - User's email address
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Attachments -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Attachments</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="attachments" class="form-label">Upload Files</label>
                            <input type="file" class="form-control" id="attachments" name="attachments[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                            <small class="text-muted">You can upload multiple files (PDF, DOC, images). Max 10MB per file.</small>
                        </div>
                    </div>
                </div>

                <!-- Survey Questions -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Survey Questions (Optional)</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSurveyQuestion()">
                            <i class="bi bi-plus-circle me-1"></i>Add Question
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="surveyQuestions">
                            <!-- Survey questions will be added here dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Campaign Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="type" class="form-label">Campaign Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="newsletter">Newsletter</option>
                                <option value="custom">Custom Campaign</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="schedule_later" name="schedule_later">
                                <label class="form-check-label" for="schedule_later">
                                    Schedule for later
                                </label>
                            </div>
                        </div>
                        
                        <div id="scheduleFields" style="display: none;">
                            <div class="mb-3">
                                <label for="schedule_date" class="form-label">Schedule Date</label>
                                <input type="date" class="form-control" id="schedule_date" name="schedule_date">
                            </div>
                            <div class="mb-3">
                                <label for="schedule_time" class="form-label">Schedule Time</label>
                                <input type="time" class="form-control" id="schedule_time" name="schedule_time">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary" onclick="sendTest()">
                                <i class="bi bi-send me-2"></i>Send Test Email
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save me-2"></i>Save Campaign
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Preview</h5>
                    </div>
                    <div class="card-body">
                        <div id="emailPreview" style="border: 1px solid #ddd; padding: 10px; min-height: 200px; background: white;">
                            <p class="text-muted">Preview will appear here as you type...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Send Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Test Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testEmailForm">
                    <div class="mb-3">
                        <label class="form-label">Test Email Addresses</label>
                        <div id="emailList">
                            <div class="email-input-group mb-2">
                                <div class="input-group">
                                    <input type="email" name="test_email[]" class="form-control test-email-input" required placeholder="Enter email address">
                                    <button type="button" class="btn btn-outline-danger" onclick="removeEmailField(this)" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="addEmailField()">
                            <i class="bi bi-plus-circle me-1"></i>Add Another Email
                        </button>
                        <small class="text-muted d-block mt-2">You can add multiple email addresses to send the test email to multiple recipients.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestEmailSubmit()">Send Test</button>
            </div>
        </div>
    </div>
</div>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalTitle">Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="messageModalContent"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let surveyQuestionCount = 0;

// Schedule checkbox toggle
document.getElementById('schedule_later').addEventListener('change', function() {
    document.getElementById('scheduleFields').style.display = this.checked ? 'block' : 'none';
});

// Live preview
document.getElementById('content').addEventListener('input', function() {
    document.getElementById('emailPreview').innerHTML = this.value || '<p class="text-muted">Preview will appear here as you type...</p>';
});

// Template insertion
function insertTemplate(type) {
    const textarea = document.getElementById('content');
    const templates = {
        logo: '<div style="text-align:center;margin-bottom:30px;"><img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($siteName) ?> Logo" style="max-width:200px;max-height:80px;"></div>',
        header: '<h1 style="color: #2c3e50; margin-bottom: 20px; text-align: center;">Your Header Text</h1>',
        subheader: '<h2 style="color: #34495e; margin-bottom: 15px;">Your Subheader Text</h2>',
        paragraph: '<p style="margin-bottom: 15px; line-height: 1.6;">Your paragraph text goes here. This is a well-formatted paragraph that will look great in the email.</p>',
        button: '<div style="text-align: center; margin: 25px 0;"><a href="#" style="background: #3498db; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Click Here</a></div>',
        image: '<div style="text-align: center; margin: 25px 0;"><img src="https://via.placeholder.com/600x300/3498db/ffffff?text=Your+Image+Here" alt="Image" style="max-width: 100%; height: auto; border-radius: 5px;"></div>',
        divider: '<hr style="border: none; border-top: 2px solid #ecf0f1; margin: 30px 0;">',
        list: '<ul style="margin-bottom: 20px; padding-left: 20px;"><li style="margin-bottom: 8px;">List item 1</li><li style="margin-bottom: 8px;">List item 2</li><li style="margin-bottom: 8px;">List item 3</li></ul>',
        quote: '<blockquote style="border-left: 4px solid #3498db; margin: 20px 0; padding-left: 20px; font-style: italic; color: #555;">"Your quote text here - this will stand out nicely in the email."</blockquote>',
        signature: '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ecf0f1;"><p style="margin: 0; color: #7f8c8d;">Best regards,<br><strong>Your Name</strong><br>Your Position<br><?= htmlspecialchars($siteName) ?> Team</p></div>',
        greeting: '<h2 style="color: #2c3e50; margin-bottom: 20px;">Hello {name},</h2>',
        userName: '<strong>{name}</strong>',
        userEmail: '<span style="color: #7f8c8d;">{email}</span>',
        personalMessage: '<p style="margin-bottom: 15px; line-height: 1.6;">Hi {name},</p><p style="margin-bottom: 15px; line-height: 1.6;">We hope you\'re enjoying your experience with <?= htmlspecialchars($siteName) ?>!</p>'
    };
    
    const cursorPos = textarea.selectionStart;
    const textBefore = textarea.value.substring(0, cursorPos);
    const textAfter = textarea.value.substring(cursorPos);
    textarea.value = textBefore + templates[type] + textAfter;
    textarea.focus();
    textarea.setSelectionRange(cursorPos + templates[type].length, cursorPos + templates[type].length);
    
    // Update preview
    document.getElementById('emailPreview').innerHTML = textarea.value || '<p class="text-muted">Preview will appear here as you type...</p>';
}
    
    // Update preview
    document.getElementById('emailPreview').innerHTML = textarea.value;
}

// Add survey question
function addSurveyQuestion() {
    surveyQuestionCount++;
    const questionHtml = `
        <div class="survey-question mb-3 p-3 border rounded" data-question="${surveyQuestionCount}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Question ${surveyQuestionCount}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSurveyQuestion(${surveyQuestionCount})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="mb-2">
                <input type="text" class="form-control" name="survey_questions[]" placeholder="Enter your question..." required>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <select class="form-select" name="survey_types[]" onchange="toggleQuestionOptions(${surveyQuestionCount})">
                        <option value="text">Text</option>
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="rating">Rating (1-5)</option>
                        <option value="yes_no">Yes/No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="survey_required[]" value="${surveyQuestionCount}">
                        <label class="form-check-label">Required</label>
                    </div>
                </div>
            </div>
            <div class="mt-2" id="options_${surveyQuestionCount}" style="display: none;">
                <textarea class="form-control" name="survey_options[]" placeholder="Enter options (one per line)" rows="3"></textarea>
            </div>
        </div>
    `;
    
    document.getElementById('surveyQuestions').insertAdjacentHTML('beforeend', questionHtml);
}

function removeSurveyQuestion(id) {
    document.querySelector(`[data-question="${id}"]`).remove();
}

function toggleQuestionOptions(id) {
    const select = document.querySelector(`[data-question="${id}"] select`);
    const optionsDiv = document.getElementById(`options_${id}`);
    optionsDiv.style.display = select.value === 'multiple_choice' ? 'block' : 'none';
}

// Send test email
function sendTest() {
    new bootstrap.Modal(document.getElementById('testEmailModal')).show();
}

function sendTestEmailSubmit() {
    const emailInputs = document.querySelectorAll('.test-email-input');
    const emails = [];
    
    // Collect all email addresses
    emailInputs.forEach(input => {
        const email = input.value.trim();
        if (email && validateEmail(email)) {
            emails.push(email);
        }
    });
    
    if (emails.length === 0) {
        showMessage('Error', 'Please enter at least one valid email address', 'danger');
        return;
    }
    
    // Get form data
    const formData = new FormData(document.getElementById('createNewsletterForm'));
    const campaignData = Object.fromEntries(formData.entries());
    
    // Show loading spinner
    const submitBtn = document.querySelector('#testEmailModal .btn-primary');
    const originalText = submitBtn.textContent;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
    submitBtn.disabled = true;
    
    // Send to each email
    const promises = emails.map(email => {
        return fetch('<?= BASE_URL ?>/admin/newsletters/send-test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                title: campaignData.title,
                subject: campaignData.subject,
                content: campaignData.content,
                test_email: email
            })
        })
        .then(response => response.json());
    });
    
    Promise.all(promises)
    .then(results => {
        // Reset button
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        
        const successCount = results.filter(r => r.success).length;
        const failureCount = results.length - successCount;
        
        if (successCount === results.length) {
            bootstrap.Modal.getInstance(document.getElementById('testEmailModal')).hide();
            showMessage('Success', `Test email sent successfully to ${successCount} recipient${successCount > 1 ? 's' : ''}!`, 'success');
            // Clear all email fields
            document.getElementById('emailList').innerHTML = `
                <div class="email-input-group mb-2">
                    <div class="input-group">
                        <input type="email" name="test_email[]" class="form-control test-email-input" required placeholder="Enter email address">
                        <button type="button" class="btn btn-outline-danger" onclick="removeEmailField(this)" title="Remove">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        } else if (successCount > 0) {
            showMessage('Warning', `Test email sent to ${successCount} recipient${successCount > 1 ? 's' : ''}, but failed to send to ${failureCount} recipient${failureCount > 1 ? 's' : ''}.`, 'warning');
        } else {
            showMessage('Error', 'Failed to send test email to all recipients', 'danger');
        }
    })
    .catch(error => {
        // Reset button
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        showMessage('Error', 'Error sending test email', 'danger');
    });
}

function addEmailField() {
    const emailList = document.getElementById('emailList');
    const newField = document.createElement('div');
    newField.className = 'email-input-group mb-2';
    newField.innerHTML = `
        <div class="input-group">
            <input type="email" name="test_email[]" class="form-control test-email-input" required placeholder="Enter email address">
            <button type="button" class="btn btn-outline-danger" onclick="removeEmailField(this)" title="Remove">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    emailList.appendChild(newField);
}

function removeEmailField(button) {
    const emailGroups = document.querySelectorAll('.email-input-group');
    if (emailGroups.length > 1) {
        button.closest('.email-input-group').remove();
    } else {
        showMessage('Warning', 'You must have at least one email address', 'warning');
    }
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Show message modal
function showMessage(title, message, type) {
    document.getElementById('messageModalTitle').textContent = title;
    document.getElementById('messageModalContent').textContent = message;
    
    const modal = new bootstrap.Modal(document.getElementById('messageModal'));
    modal.show();
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date for scheduling to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('schedule_date').setAttribute('min', today);
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../layouts/main.php';
?>
