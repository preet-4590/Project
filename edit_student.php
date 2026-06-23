<?php
ob_start();
session_start();
include('db_config.php');

if (!isset($_SESSION['clerk_user'])) {
    header("Location: login.php");
    ob_end_clean();
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No student ID provided.");
}

$u_id = mysqli_real_escape_string($conn, $_GET['id']);
$res  = mysqli_query($conn, "SELECT * FROM students WHERE unique_id = '$u_id' AND is_deleted = 0");
$s    = mysqli_fetch_assoc($res);

if (!$s) {
    die("Student record not found.");
}

$is_admin = ($_SESSION['role'] === 'super_admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student | Gate Entry System</title>
    <style>
        :root {
            --portal-dark: #223a5e;
            --portal-blue: #1e3d8f;
            --portal-bg:   #f4f6f9;
            --border-color:#cbd5e0;
            --text-main:   #2d3748;
            --success:     #10b981;
            --danger:      #ef4444;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--portal-bg);
            margin: 0; padding: 50px 20px;
            color: var(--text-main);
            display: flex; justify-content: center;
            align-items: flex-start; min-height: 100vh;
            box-sizing: border-box;
        }
        .form-container {
            width: 100%; max-width: 720px;
            background: #fff; padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .form-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 32px;
        }
        .form-header h2 {
            color: var(--portal-dark); margin: 0;
            font-size: 22px; font-weight: 700;
        }
        .id-chip {
            background: #edf2f7; color: var(--portal-dark);
            padding: 6px 14px; border-radius: 20px;
            font-size: 13px; font-weight: 700;
            font-family: monospace;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
        }
        .full-width { grid-column: span 2; }
        label {
            font-weight: 600; color: #4a5568;
            display: block; margin-bottom: 7px;
            font-size: 13px;
        }
        input[type="text"], select, textarea {
            width: 100%; padding: 11px 14px;
            border: 1px solid var(--border-color);
            border-radius: 6px; box-sizing: border-box;
            font-size: 14px; color: var(--text-main);
            background: #fff; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input[type="text"]:focus,
        select:focus, textarea:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.15);
        }
        textarea { resize: vertical; min-height: 70px; }

        /* Current file preview */
        .file-group { display: flex; flex-direction: column; gap: 8px; }
        .current-preview {
            display: flex; align-items: center; gap: 10px;
            background: #f8fafc; border: 1px solid var(--border-color);
            border-radius: 6px; padding: 8px 12px;
        }
        .current-preview img {
            height: 48px; max-width: 80px;
            object-fit: contain; border-radius: 4px;
            border: 1px solid var(--border-color);
        }
        .current-preview span {
            font-size: 12px; color: #718096;
        }
        .file-hint {
            font-size: 11px; color: #94a3b8; margin-top: 4px;
        }
        input[type="file"] {
            font-size: 13px; color: #718096; padding: 6px 0;
        }

        .btn-row {
            display: flex; gap: 12px; margin-top: 28px;
        }
        .btn-save {
            flex: 1; background: var(--portal-blue); color: #fff;
            border: none; padding: 13px; border-radius: 6px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: background 0.2s;
            box-shadow: 0 4px 12px rgba(30,61,143,0.15);
        }
        .btn-save:hover { background: #162e6f; }
        .btn-cancel {
            flex: 0 0 auto; background: #f1f5f9; color: #475569;
            border: 1px solid var(--border-color); padding: 13px 24px;
            border-radius: 6px; font-size: 15px; font-weight: 600;
            text-decoration: none; text-align: center;
            transition: background 0.2s;
        }
        .btn-cancel:hover { background: #e2e8f0; }

        .section-divider {
            grid-column: span 2;
            border: none; border-top: 1px solid #e2e8f0;
            margin: 4px 0;
        }
        .section-label {
            grid-column: span 2;
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: #94a3b8; margin-bottom: -8px;
        }
    </style>
</head>
<body>
<div class="form-container">
    <div class="form-header">
        <h2>Edit Student Record</h2>
        <span class="id-chip"><?php echo htmlspecialchars($s['unique_id']); ?></span>
    </div>

    <form action="update_student.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="u_id" value="<?php echo htmlspecialchars($s['unique_id']); ?>">

        <div class="grid">

            <!-- Institution (read-only) -->
            <div class="full-width">
                <label>Institution</label>
                <input type="text" value="<?php echo htmlspecialchars($s['institution']); ?>" readonly
                       style="background:#f8fafc; color:#718096;">
                <input type="hidden" name="institution" value="<?php echo htmlspecialchars($s['institution']); ?>">
            </div>

            <hr class="section-divider">
            <p class="section-label">Personal Details</p>

            <div>
                <label>Full Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($s['name']); ?>" required>
            </div>
            <div>
                <label>Father's Name</label>
                <input type="text" name="father_name" value="<?php echo htmlspecialchars($s['father_name'] ?? ''); ?>">
            </div>
            <div>
                <label>Gender</label>
                <select name="gender">
                    <option value="">Select Gender</option>
                    <?php foreach (['Male','Female','Other'] as $g): ?>
                        <option value="<?php echo $g; ?>" <?php echo ($s['gender'] === $g) ? 'selected' : ''; ?>>
                            <?php echo $g; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Phone Number</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($s['phone_no']); ?>">
            </div>
            <div>
                <label>Email Address</label>
                <input type="text" name="email" placeholder="student@example.com"
                       value="<?php echo htmlspecialchars($s['email'] ?? ''); ?>">
            </div>

            <div class="full-width">
                <label>Permanent Address</label>
                <textarea name="address"><?php echo htmlspecialchars($s['address'] ?? ''); ?></textarea>
            </div>

            <hr class="section-divider">
            <p class="section-label">Academic Details</p>

            <div>
                <label>Roll Number</label>
                <input type="text" name="roll" value="<?php echo htmlspecialchars($s['roll_no']); ?>" required>
            </div>
            <div>
                <label>Course</label>
                <input type="text" name="course" value="<?php echo htmlspecialchars($s['course']); ?>">
            </div>
            <div>
                <label>Admission Year</label>
                <select name="adm_year">
                    <?php for ($y = 2021; $y <= 2030; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($s['admission_year'] == $y) ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label>Passing Year</label>
                <select name="pass_year">
                    <?php for ($y = 2021; $y <= 2030; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($s['passing_year'] == $y) ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <hr class="section-divider">
            <p class="section-label">Photo & Signature (leave blank to keep current)</p>

            <!-- Photo -->
            <div class="file-group">
                <label>Student Photo</label>
                <?php if (!empty($s['photo'])): ?>
                <div class="current-preview">
                    <img src="uploads/photos/<?php echo htmlspecialchars($s['photo']); ?>" alt="Current Photo">
                    <span>Current: <?php echo htmlspecialchars($s['photo']); ?></span>
                </div>
                <?php endif; ?>
                <input type="file" name="photo" accept="image/*">
                <span class="file-hint">Upload a new image only if you want to replace the current one.</span>
            </div>

            <!-- Signature -->
            <div class="file-group">
                <label>Signature Scan</label>
                <?php if (!empty($s['signature'])): ?>
                <div class="current-preview">
                    <img src="uploads/signatures/<?php echo htmlspecialchars($s['signature']); ?>" alt="Current Signature">
                    <span>Current: <?php echo htmlspecialchars($s['signature']); ?></span>
                </div>
                <?php endif; ?>
                <input type="file" name="signature" accept="image/*">
                <span class="file-hint">Upload a new image only if you want to replace the current one.</span>
            </div>

        </div><!-- /grid -->

        <div class="btn-row">
            <button type="submit" name="update" class="btn-save">Save Changes</button>
            <a href="<?php echo ($is_admin ? 'admin_dashboard.php' : 'dashboard.php'); ?>" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>