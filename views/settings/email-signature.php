<?php
$pageTitle = 'Email Signature Builder';
$activeNav = 'settings';
include APP_PATH . '/../views/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1>Email Signature Builder</h1>
        <p class="text-muted">Create a professional email signature for your outgoing emails</p>
    </div>
</div>

<div class="grid grid-cols-2 gap-6">
    <!-- Builder Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Signature Details</h3>
        </div>
        <form id="signature-form">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-input" id="sig-name" placeholder="John Doe"
                        onchange="updatePreview()">
                </div>

                <div class="form-group">
                    <label class="form-label">Job Title</label>
                    <input type="text" class="form-input" id="sig-title" placeholder="Project Manager"
                        onchange="updatePreview()">
                </div>

                <div class="form-group">
                    <label class="form-label">Company</label>
                    <input type="text" class="form-input" id="sig-company" placeholder="Your Company"
                        onchange="updatePreview()">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-input" id="sig-phone" placeholder="(555) 123-4567"
                            onchange="updatePreview()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-input" id="sig-email" placeholder="you@company.com"
                            onchange="updatePreview()">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="url" class="form-input" id="sig-website" placeholder="https://company.com"
                        onchange="updatePreview()">
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <input type="text" class="form-input" id="sig-address" placeholder="123 Main St, City, State"
                        onchange="updatePreview()">
                </div>

                <hr class="my-4">
                <h4 class="mb-3">Social Links</h4>

                <div class="grid grid-cols-3 gap-4">
                    <div class="form-group">
                        <label class="form-label">LinkedIn</label>
                        <input type="url" class="form-input" id="sig-linkedin" placeholder="LinkedIn URL"
                            onchange="updatePreview()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Twitter</label>
                        <input type="url" class="form-input" id="sig-twitter" placeholder="Twitter URL"
                            onchange="updatePreview()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Facebook</label>
                        <input type="url" class="form-input" id="sig-facebook" placeholder="Facebook URL"
                            onchange="updatePreview()">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Custom Message</label>
                    <input type="text" class="form-input" id="sig-message"
                        placeholder="Please consider the environment before printing this email"
                        onchange="updatePreview()">
                </div>
            </div>
            <div class="card-footer flex gap-2">
                <button type="button" class="btn btn-secondary" onclick="generateFromForm()">Generate Preview</button>
                <button type="submit" class="btn btn-primary">Save Signature</button>
            </div>
        </form>
    </div>

    <!-- Preview -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Preview</h3>
            </div>
            <div class="card-body" id="signature-preview" style="min-height: 200px; background: #fff;">
                <p class="text-muted text-center">Fill in the form to see your signature preview</p>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Custom HTML</h3>
            </div>
            <div class="card-body">
                <p class="text-sm text-muted mb-2">Advanced: Paste your own HTML signature</p>
                <textarea class="form-textarea" id="custom-html" rows="6"
                    placeholder="Paste custom HTML here..."></textarea>
                <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="useCustomHtml()">Use Custom
                    HTML</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentSignatureHtml = '';

    document.addEventListener('DOMContentLoaded', function () {
        loadCurrentSignature();
    });

    async function loadCurrentSignature() {
        try {
            const response = await ERP.api.get('/email/signature');
            if (response.success && response.data && response.data.signature_html) {
                currentSignatureHtml = response.data.signature_html;
                document.getElementById('signature-preview').innerHTML = currentSignatureHtml;
                document.getElementById('custom-html').value = currentSignatureHtml;
            }
        } catch (error) {
            console.error('Failed to load signature:', error);
        }
    }

    function updatePreview() {
        generateFromForm();
    }

    async function generateFromForm() {
        const data = {
            name: document.getElementById('sig-name').value,
            title: document.getElementById('sig-title').value,
            company: document.getElementById('sig-company').value,
            phone: document.getElementById('sig-phone').value,
            email: document.getElementById('sig-email').value,
            website: document.getElementById('sig-website').value,
            address: document.getElementById('sig-address').value,
            linkedin: document.getElementById('sig-linkedin').value,
            twitter: document.getElementById('sig-twitter').value,
            facebook: document.getElementById('sig-facebook').value,
            custom_message: document.getElementById('sig-message').value,
        };

        try {
            const response = await ERP.api.post('/email/signature/generate', data);
            if (response.success) {
                currentSignatureHtml = response.data.html;
                document.getElementById('signature-preview').innerHTML = currentSignatureHtml;
                document.getElementById('custom-html').value = currentSignatureHtml;
            }
        } catch (error) {
            console.error('Failed to generate signature:', error);
        }
    }

    function useCustomHtml() {
        const html = document.getElementById('custom-html').value;
        if (html) {
            currentSignatureHtml = html;
            document.getElementById('signature-preview').innerHTML = html;
        }
    }

    document.getElementById('signature-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        if (!currentSignatureHtml) {
            ERP.toast.error('Please generate a signature first');
            return;
        }

        try {
            const response = await ERP.api.post('/email/signature', {
                signature_html: currentSignatureHtml
            });

            if (response.success) {
                ERP.toast.success('Signature saved! It will be added to all outgoing emails.');
            } else {
                ERP.toast.error(response.message || 'Failed to save');
            }
        } catch (error) {
            ERP.toast.error('Failed to save signature');
        }
    });
</script>

<?php include APP_PATH . '/../views/layouts/footer.php'; ?>