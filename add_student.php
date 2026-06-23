<?php
ob_start();
session_start();
include('db_config.php');

if (!isset($_SESSION['clerk_user'])) {
    header("Location: login.php");
    ob_end_clean();
    exit;
}

// super_admin has no clerk_inst — that's expected
if ($_SESSION['role'] !== 'super_admin' && !isset($_SESSION['clerk_inst'])) {
    header("Location: login.php");
    ob_end_clean();
    exit;
}

$is_admin     = ($_SESSION['role'] == 'super_admin');
$default_inst = $is_admin ? "" : $_SESSION['clerk_inst'];
$back_url     = $is_admin ? 'admin_dashboard.php' : 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Student | CGEMS</title>
    <style>
        :root {
            --blue:        #1e3d8f;
            --dark:        #0f172a;
            --bg:          #f0f4f8;
            --white:       #ffffff;
            --border:      #e2e8f0;
            --text:        #1e293b;
            --muted:       #64748b;
            --success:     #10b981;
            --focus:       #3b82f6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            color: var(--text);
        }

        /* ── Top bar ── */
        .topbar {
            background: linear-gradient(90deg, var(--dark), var(--blue));
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .topbar-title {
            color: white;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }
        .topbar-title span { opacity: 0.6; font-weight: 400; margin: 0 8px; }
        .btn-back {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-back:hover { background: rgba(255,255,255,0.2); }

        /* ── Page layout ── */
        .page {
            max-width: 860px;
            margin: 40px auto;
            padding: 0 20px 60px;
        }

        /* ── Progress steps ── */
        .steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 32px;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            position: relative;
        }
        .step-circle {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: var(--white);
            border: 2px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700; color: var(--muted);
            transition: all 0.3s;
            z-index: 1;
        }
        .step.active .step-circle {
            background: var(--blue); border-color: var(--blue);
            color: white; box-shadow: 0 4px 12px rgba(30,61,143,0.3);
        }
        .step.done .step-circle {
            background: var(--success); border-color: var(--success); color: white;
        }
        .step-label {
            font-size: 11px; font-weight: 600; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .step.active .step-label { color: var(--blue); }
        .step-line {
            flex: 1; height: 2px;
            background: var(--border);
            margin: 0 8px;
            margin-bottom: 22px;
        }

        /* ── Card ── */
        .card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
            border: 1px solid var(--border);
            overflow: hidden;
            display: none;
        }
        .card.active { display: block; }

        .card-header {
            background: linear-gradient(135deg, var(--blue) 0%, #162e6f 100%);
            padding: 24px 32px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .card-header-icon {
            width: 48px; height: 48px;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }
        .card-header h2 {
            color: white; font-size: 18px; font-weight: 700; margin-bottom: 2px;
        }
        .card-header p { color: rgba(255,255,255,0.7); font-size: 13px; }

        .card-body { padding: 32px; }

        /* ── Form grid ── */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .full { grid-column: span 2; }

        .field { display: flex; flex-direction: column; gap: 7px; }

        label {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .optional { font-weight: 400; color: #94a3b8; text-transform: none; letter-spacing: 0; margin-left: 4px; }

        input[type="text"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            color: var(--text);
            background: #fafbfc;
            outline: none;
            transition: all 0.2s;
            font-family: inherit;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--focus);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }
        textarea { resize: vertical; min-height: 75px; }

        .readonly-id {
            background: #eff6ff !important;
            border-color: var(--focus) !important;
            font-weight: 700;
            color: var(--blue) !important;
            font-size: 15px !important;
            letter-spacing: 0.5px;
        }

        /* ── File upload ── */
        .file-upload-area {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fafbfc;
            position: relative;
        }
        .file-upload-area:hover {
            border-color: var(--focus);
            background: #eff6ff;
        }
        .file-upload-area.has-file {
            border-color: var(--success);
            background: #f0fdf4;
        }
        .file-upload-area input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%;
        }
        .file-upload-icon { font-size: 28px; margin-bottom: 8px; }
        .file-upload-text { font-size: 13px; font-weight: 600; color: var(--muted); }
        .file-upload-hint { font-size: 11px; color: #94a3b8; margin-top: 4px; }
        .file-preview {
            width: 80px; height: 80px; border-radius: 10px;
            object-fit: cover; margin: 0 auto 8px;
            display: none; border: 2px solid var(--success);
        }
        .file-name { font-size: 12px; color: var(--success); font-weight: 600; }

        /* ── Step navigation buttons ── */
        .step-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            gap: 12px;
        }
        .btn {
            padding: 13px 28px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-prev {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid var(--border);
        }
        .btn-prev:hover { background: #e2e8f0; }
        .btn-next {
            background: var(--blue);
            color: white;
            box-shadow: 0 4px 12px rgba(30,61,143,0.25);
            margin-left: auto;
        }
        .btn-next:hover { background: #162e6f; transform: translateY(-1px); }
        .btn-submit-final {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
            margin-left: auto;
            padding: 14px 32px;
            font-size: 15px;
        }
        .btn-submit-final:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(16,185,129,0.35); }

        /* ── ID chip preview ── */
        .id-preview {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 1.5px solid #bfdbfe;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .id-preview-icon { font-size: 28px; }
        .id-preview-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .id-preview-value { font-size: 22px; font-weight: 800; color: var(--blue); font-family: monospace; letter-spacing: 1px; }

        /* ── Summary card on step 3 ── */
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 24px;
        }
        .summary-item {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 14px;
        }
        .summary-item label { font-size: 10px; color: #94a3b8; display: block; margin-bottom: 3px; }
        .summary-item span  { font-size: 14px; font-weight: 600; color: var(--text); }
        .summary-item.full  { grid-column: span 2; }

        @media (max-width: 640px) {
            .grid-2 { grid-template-columns: 1fr; }
            .full { grid-column: span 1; }
            .summary-grid { grid-template-columns: 1fr; }
            .summary-item.full { grid-column: span 1; }
            .topbar { padding: 14px 20px; }
            .card-body { padding: 20px; }
        }
    </style>
</head>
<body>

<!-- Top bar -->
<div class="topbar">
    <div class="topbar-title">
        CGEMS <span>/</span> Register New Student
    </div>
    <a href="<?php echo $back_url; ?>" class="btn-back">← Back to Dashboard</a>
</div>

<div class="page">

    <!-- Progress steps -->
    <div class="steps">
        <div class="step active" id="step-ind-1">
            <div class="step-circle">1</div>
            <div class="step-label">Institution</div>
        </div>
        <div class="step-line"></div>
        <div class="step" id="step-ind-2">
            <div class="step-circle">2</div>
            <div class="step-label">Personal</div>
        </div>
        <div class="step-line"></div>
        <div class="step" id="step-ind-3">
            <div class="step-circle">3</div>
            <div class="step-label">Academic</div>
        </div>
        <div class="step-line"></div>
        <div class="step" id="step-ind-4">
            <div class="step-circle">4</div>
            <div class="step-label">Documents</div>
        </div>
    </div>

    <form action="save_student.php" method="POST" enctype="multipart/form-data" id="reg-form">

        <!-- ── STEP 1: Institution & ID ─────────────────────────────── -->
        <div class="card active" id="card-1">
            <div class="card-header">
                <div class="card-header-icon">🏛️</div>
                <div>
                    <h2>Select Institution</h2>
                    <p>Choose the institution to auto-generate the student's unique ID</p>
                </div>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="field full">
                        <label>Institution *</label>
                        <?php if ($is_admin): ?>
                            <select name="institution" id="inst_select" required onchange="updateID(this.value)">
                                <option value="">-- Select Institution --</option>
                                <option value="GNDEC">GNDEC – Guru Nanak Dev Engineering College</option>
                                <option value="GNDPC">GNDPC – Guru Nanak Dev Polytechnic College</option>
                                <option value="GNDITI">GNDITI – Guru Nanak Dev ITI</option>
                            </select>
                        <?php else: ?>
                            <input type="text" value="<?php echo htmlspecialchars($default_inst); ?>" readonly
                                   style="background:#f0fdf4;border-color:#86efac;color:#166534;font-weight:700;">
                            <input type="hidden" name="institution" value="<?php echo htmlspecialchars($default_inst); ?>">
                            <script>window.onload = function(){ updateID('<?php echo addslashes($default_inst); ?>'); };</script>
                        <?php endif; ?>
                    </div>

                    <div class="field full">
                        <label>Unique ID (Auto-generated)</label>
                        <div class="id-preview" id="id-preview-box" style="display:none;">
                            <div class="id-preview-icon">🪪</div>
                            <div>
                                <div class="id-preview-label">Assigned Student ID</div>
                                <div class="id-preview-value" id="id-preview-val">—</div>
                            </div>
                        </div>
                        <input type="text" name="u_id" id="unique_id" class="readonly-id" readonly
                               placeholder="Select institution first..." style="display:none;">
                    </div>
                </div>

                <div class="step-nav">
                    <button type="button" class="btn btn-next" onclick="goTo(2)">
                        Next: Personal Details →
                    </button>
                </div>
            </div>
        </div>

        <!-- ── STEP 2: Personal Details ─────────────────────────────── -->
        <div class="card" id="card-2">
            <div class="card-header">
                <div class="card-header-icon">👤</div>
                <div>
                    <h2>Personal Details</h2>
                    <p>Enter the student's personal information</p>
                </div>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="field">
                        <label>Full Name *</label>
                        <input type="text" name="name" id="f-name" placeholder="e.g. Manpreet Kaur" required>
                    </div>
                    <div class="field">
                        <label>Father's Name *</label>
                        <input type="text" name="father_name" placeholder="e.g. Rajinder Singh" required>
                    </div>
                    <div class="field">
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Phone Number *</label>
                        <input type="text" name="phone" placeholder="10-digit mobile number" required>
                    </div>
                    <div class="field full">
                        <label>Email Address <span class="optional">(optional — used to send ID pass)</span></label>
                        <input type="text" name="email" placeholder="student@example.com">
                    </div>
                    <div class="field full">
                        <label>Permanent Address *</label>
                        <textarea name="address" placeholder="House No., Street, City, State" required></textarea>
                    </div>
                </div>

                <div class="step-nav">
                    <button type="button" class="btn btn-prev" onclick="goTo(1)">← Back</button>
                    <button type="button" class="btn btn-next" onclick="goTo(3)">Next: Academic Details →</button>
                </div>
            </div>
        </div>

        <!-- ── STEP 3: Academic Details ─────────────────────────────── -->
        <div class="card" id="card-3">
            <div class="card-header">
                <div class="card-header-icon">🎓</div>
                <div>
                    <h2>Academic Details</h2>
                    <p>Course enrollment and batch information</p>
                </div>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="field">
                        <label>Roll Number *</label>
                        <input type="text" name="roll" placeholder="University roll number" required>
                    </div>
                    <div class="field">
                        <label>Course *</label>
                        <input type="text" name="course" placeholder="e.g. MCA, B.Tech CSE" required>
                    </div>
                    <div class="field">
                        <label>Admission Year *</label>
                        <select name="adm_year" required>
                            <option value="">Select Year</option>
                            <?php for ($y = 2021; $y <= 2030; $y++) echo "<option value='$y'>$y</option>"; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Passing Year *</label>
                        <select name="pass_year" required>
                            <option value="">Select Year</option>
                            <?php for ($y = 2021; $y <= 2030; $y++) echo "<option value='$y'>$y</option>"; ?>
                        </select>
                    </div>
                </div>

                <div class="step-nav">
                    <button type="button" class="btn btn-prev" onclick="goTo(2)">← Back</button>
                    <button type="button" class="btn btn-next" onclick="goTo(4)">Next: Upload Documents →</button>
                </div>
            </div>
        </div>

        <!-- ── STEP 4: Documents & Submit ───────────────────────────── -->
        <div class="card" id="card-4">
            <div class="card-header">
                <div class="card-header-icon">📎</div>
                <div>
                    <h2>Upload Documents</h2>
                    <p>Student photo and signature scan for the ID pass</p>
                </div>
            </div>
            <div class="card-body">
                <div class="grid-2">

                    <!-- Photo upload -->
                    <div class="field">
                        <label>Student Photo *</label>
                        <div class="file-upload-area" id="photo-area">
                            <input type="file" name="photo" accept="image/*" required
                                   onchange="previewFile(this, 'photo-preview', 'photo-name', 'photo-area', 'photo-icon', 'photo-text')">
                            <img id="photo-preview" class="file-preview" src="" alt="">
                            <div id="photo-icon" class="file-upload-icon">🖼️</div>
                            <div id="photo-text" class="file-upload-text">Click to upload photo</div>
                            <div class="file-upload-hint">JPG, PNG — passport size preferred</div>
                            <div id="photo-name" class="file-name" style="display:none;"></div>
                        </div>
                    </div>

                    <!-- Signature upload -->
                    <div class="field">
                        <label>Signature Scan *</label>
                        <div class="file-upload-area" id="sig-area">
                            <input type="file" name="signature" accept="image/*" required
                                   onchange="previewFile(this, 'sig-preview', 'sig-name', 'sig-area', 'sig-icon', 'sig-text')">
                            <img id="sig-preview" class="file-preview" src="" alt=""
                                 style="height:50px;width:auto;max-width:160px;">
                            <div id="sig-icon" class="file-upload-icon">✍️</div>
                            <div id="sig-text" class="file-upload-text">Click to upload signature</div>
                            <div class="file-upload-hint">Scan on white background</div>
                            <div id="sig-name" class="file-name" style="display:none;"></div>
                        </div>
                    </div>

                </div>

                <div class="step-nav">
                    <button type="button" class="btn btn-prev" onclick="goTo(3)">← Back</button>
                    <button type="submit" name="save" class="btn btn-submit-final">
                        ✅ Generate Pass & Register Student
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

<script>
    var currentStep = 1;

    function goTo(step) {
        // Validate step 1 — institution must be selected
        if (step > 1) {
            var uid = document.getElementById('unique_id').value;
            if (!uid) {
                alert('Please select an institution first.');
                return;
            }
        }

        // Hide all cards, deactivate all steps
        for (var i = 1; i <= 4; i++) {
            document.getElementById('card-' + i).classList.remove('active');
            var ind = document.getElementById('step-ind-' + i);
            ind.classList.remove('active', 'done');
            if (i < step) ind.classList.add('done');
        }

        // Activate current
        document.getElementById('card-' + step).classList.add('active');
        document.getElementById('step-ind-' + step).classList.add('active');
        currentStep = step;

        // Scroll to top of card
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function updateID(inst) {
        if (!inst) {
            document.getElementById('unique_id').value = '';
            document.getElementById('id-preview-box').style.display = 'none';
            return;
        }
        fetch('get_next_id.php?institution=' + encodeURIComponent(inst))
            .then(r => r.text())
            .then(data => {
                document.getElementById('unique_id').value = data;
                document.getElementById('id-preview-val').textContent = data;
                document.getElementById('id-preview-box').style.display = 'flex';
            })
            .catch(err => console.error('ID fetch error:', err));
    }

    function previewFile(input, previewId, nameId, areaId, iconId, textId) {
        var file = input.files[0];
        if (!file) return;

        var area    = document.getElementById(areaId);
        var preview = document.getElementById(previewId);
        var nameEl  = document.getElementById(nameId);
        var icon    = document.getElementById(iconId);
        var text    = document.getElementById(textId);

        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            icon.style.display = 'none';
            text.style.display = 'none';
            nameEl.textContent = '✓ ' + file.name;
            nameEl.style.display = 'block';
            area.classList.add('has-file');
        };
        reader.readAsDataURL(file);
    }
</script>
</body>
</html>