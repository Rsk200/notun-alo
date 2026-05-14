<?php
ob_start(); // Must be first — prevents "headers already sent" when navbar does redirects
// ============================================
// edit_profile.php - Edit User Profile
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireLogin();
startSession();

// Each user edits ONLY their own profile — enforced by session user_id
$userId = (int)$_SESSION['user_id'];
$flashMsg = null;
$user = getCurrentUser($pdo);

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName    = trim($_POST['name'] ?? '');
    $newPhone   = trim($_POST['phone'] ?? '');
    $newAddress = trim($_POST['address'] ?? '');
    $newPicture = $user['picture_url'] ?? '';
    $uploadedPicture = null;

    if (!empty($_FILES['picture_file']) && $_FILES['picture_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $fileType = mime_content_type($_FILES['picture_file']['tmp_name']);
        $maxFileSize = 4 * 1024 * 1024; // 4 MB

        if ($_FILES['picture_file']['error'] !== UPLOAD_ERR_OK) {
            $flashMsg = ['type' => 'error', 'message' => 'Image upload failed. Please try again.'];
        } elseif (!array_key_exists($fileType, $allowedTypes)) {
            $flashMsg = ['type' => 'error', 'message' => 'Only JPG, PNG, and WEBP images are allowed.'];
        } elseif ($_FILES['picture_file']['size'] > $maxFileSize) {
            $flashMsg = ['type' => 'error', 'message' => 'Image must be smaller than 4MB.'];
        } else {
            $uploadDir = __DIR__ . '/uploads/profile_pictures';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = $allowedTypes[$fileType];
            $fileName = 'profile_' . $userId . '_' . time() . '.' . $extension;
            $destination = $uploadDir . '/' . $fileName;

            if (move_uploaded_file($_FILES['picture_file']['tmp_name'], $destination)) {
                $uploadedPicture = 'uploads/profile_pictures/' . $fileName;

                if (!empty($user['picture_url']) && strpos($user['picture_url'], 'uploads/profile_pictures/') === 0) {
                    @unlink(__DIR__ . '/' . $user['picture_url']);
                }
            } else {
                $flashMsg = ['type' => 'error', 'message' => 'Unable to save the uploaded image.'];
            }
        }
    }

    if (empty($flashMsg)) {
        if ($uploadedPicture) {
            $newPicture = $uploadedPicture;
        }

        if ($newName) {
            // SECURITY: WHERE id = $userId ensures only own record is updated
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ?, picture_url = ? WHERE id = ?");
            $stmt->execute([$newName, $newPhone, $newAddress, $newPicture, $userId]);
            $_SESSION['name'] = $newName;
            $flashMsg = ['type' => 'success', 'message' => 'Profile updated successfully!'];
        } else {
            $flashMsg = ['type' => 'error', 'message' => 'Full Name is required.'];
        }
    }
}

$user = getCurrentUser($pdo);
$flash = $flashMsg ?? getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['edit_profile'] ?? 'Edit Profile' ?> — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* ── Profile Page ── */
        .profile-page { max-width: 560px; margin: 0 auto; }

        /* Card accent top line */
        .profile-page .card {
            border-top: 4px solid #22c55e;
            background: #fff;
            box-shadow: 0 8px 32px rgba(34,197,94,.10), 0 2px 8px rgba(0,0,0,.06);
        }
        body.dark-mode .profile-page .card {
            background: #1a2a1b;
            border-top-color: #22c55e;
            box-shadow: 0 8px 32px rgba(0,0,0,.4);
        }

        /* Avatar section */
        .avatar-wrap {
            display: flex; flex-direction: column; align-items: center;
            gap: 0.75rem; margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #f0fdf4;
        }
        body.dark-mode .avatar-wrap { border-bottom-color: #2d4a2e; }

        .avatar-ring {
            position: relative; width: 110px; height: 110px;
        }
        .avatar-ring img,
        .avatar-ring .avatar-placeholder {
            width: 110px; height: 110px;
            border-radius: 50%; object-fit: cover;
            border: 4px solid #22c55e;
            box-shadow: 0 0 0 5px rgba(34,197,94,.18), 0 4px 20px rgba(34,197,94,.25);
            transition: transform 0.3s ease;
        }
        .avatar-ring:hover img, .avatar-ring:hover .avatar-placeholder { transform: scale(1.04); }
        .avatar-placeholder {
            background: linear-gradient(135deg,#e8f5e9,#c8e6c9);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.8rem; color: #388e3c;
        }
        .avatar-edit-btn {
            position: absolute; bottom: 4px; right: 4px;
            width: 30px; height: 30px;
            background: linear-gradient(135deg,#22c55e,#15803d);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; cursor: pointer; color: #fff;
            box-shadow: 0 2px 8px rgba(34,197,94,.55);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .avatar-edit-btn:hover { transform: scale(1.18); box-shadow: 0 4px 16px rgba(34,197,94,.7); }

        .avatar-name { font-size: 1.25rem; font-weight: 700; color: #111827; }
        body.dark-mode .avatar-name { color: #f1f5f9; }

        .avatar-role-badge {
            display: inline-block; padding: 4px 14px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 700;
            background: linear-gradient(135deg,#d1fae5,#bbf7d0);
            color: #065f46; border: 1px solid #6ee7b7;
            letter-spacing: 0.06em; text-transform: uppercase;
        }
        body.dark-mode .avatar-role-badge {
            background: rgba(74,222,128,.15); color: #4ade80; border-color: rgba(74,222,128,.3);
        }

        /* Form labels */
        .profile-form .form-group { margin-bottom: 1.25rem; }
        .profile-form label {
            display: block; margin-bottom: 0.45rem;
            font-weight: 600; font-size: 0.875rem;
            color: #374151;
            letter-spacing: 0.01em;
        }
        body.dark-mode .profile-form label { color: #9ca3af; }

        /* Text inputs */
        .profile-form input[type="text"] {
            width: 100%; padding: 0.85rem 1rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.97rem;
            background: #ffffff;
            color: #111827;
            transition: border-color 0.25s, box-shadow 0.25s;
            outline: none;
        }
        .profile-form input[type="text"]:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 4px rgba(34,197,94,.12);
        }
        body.dark-mode .profile-form input[type="text"] {
            background: #132314; border-color: #2d4a2e; color: #f1f5f9;
        }
        body.dark-mode .profile-form input[type="text"]:focus { background: #1a3520; }

        /* File upload */
        .file-upload-label {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.85rem 1rem;
            border: 2px dashed #d1fae5;
            border-radius: 12px; cursor: pointer;
            background: #f0fdf4;
            transition: border-color 0.25s, background 0.25s;
        }
        .file-upload-label:hover { border-color: #22c55e; background: #dcfce7; }
        .file-upload-label input[type="file"] { display: none; }
        .file-upload-label .file-text { font-size: 0.9rem; color: #6b7280; }
        body.dark-mode .file-upload-label { background: #132314; border-color: #2d4a2e; }
        body.dark-mode .file-upload-label:hover { background: #1a3520; border-color: #22c55e; }

        .file-upload-hint { font-size: 0.78rem; color: #9ca3af; margin-top: 0.35rem; }

        /* Section divider */
        .form-divider { border: none; border-top: 1px solid #f0fdf4; margin: 1.25rem 0; }
        body.dark-mode .form-divider { border-color: #2d4a2e; }

        /* Read-only email field */
        .readonly-field {
            padding: 0.85rem 1rem;
            border-radius: 12px;
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.95rem;
            font-style: italic;
        }
        body.dark-mode .readonly-field { background: #0f1f10; border-color: #2d4a2e; color: #6b7280; }

        /* Save button */
        .save-btn {
            width: 100%; padding: 1rem;
            border: none; border-radius: 14px;
            background: linear-gradient(135deg,#22c55e,#15803d);
            color: #fff; font-size: 1rem; font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(34,197,94,.4);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 0.75rem;
            letter-spacing: 0.02em;
        }
        .save-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(34,197,94,.55); }
        .save-btn:active { transform: translateY(0); }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="main-content">
    <div class="container profile-page">

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" style="margin-bottom: 1.5rem;"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <section class="card">
            <!-- Avatar & identity -->
            <div class="avatar-wrap">
                <div class="avatar-ring" id="avatarRing">
                    <?php if (!empty($user['picture_url'])): ?>
                        <img id="avatarPreview" src="<?= e($user['picture_url']) ?>" alt="Profile"
                             onerror="this.outerHTML='<div class=\'avatar-placeholder\'>👤</div>'">
                    <?php else: ?>
                        <div class="avatar-placeholder" id="avatarPreview">👤</div>
                    <?php endif; ?>
                    <label for="picture_file" class="avatar-edit-btn" title="Change photo">✎</label>
                </div>
                <div class="avatar-name"><?= e($user['name']) ?></div>
                <div class="avatar-role-badge"><?= ucfirst(e($user['role'])) ?></div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="profile-form" id="profileForm">
                <input type="hidden" name="update_profile" value="1">

                <!-- Read-only email -->
                <div class="form-group">
                    <label>📧 <?= $lang['email'] ?? 'Email Address' ?></label>
                    <div class="readonly-field"><?= e($user['email']) ?></div>
                </div>

                <hr class="form-divider">

                <div class="form-group">
                    <label>👤 <?= $lang['full_name'] ?? 'Full Name' ?> <span style="color:#ef4444;">*</span></label>
                    <div class="field-icon-wrap">
                        <input type="text" name="name" value="<?= e($user['name']) ?>" required placeholder="Your full name">
                    </div>
                </div>

                <div class="form-group">
                    <label>📞 <?= $lang['phone_number'] ?? 'Phone Number' ?></label>
                    <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="+880 1X XX XXX XXX">
                </div>

                <div class="form-group">
                    <label>📍 <?= $lang['address'] ?? 'Address' ?></label>
                    <input type="text" name="address" value="<?= e($user['address'] ?? '') ?>" placeholder="Your address">
                </div>

                <hr class="form-divider">

                <div class="form-group">
                    <label>🖼 <?= $lang['upload_profile_picture'] ?? 'Profile Picture' ?></label>
                    <label class="file-upload-label" for="picture_file">
                        <span style="font-size:1.4rem;">📁</span>
                        <span class="file-text" id="fileLabel"><?= $lang['profile_picture_hint'] ?? 'Click to choose or take a photo' ?></span>
                        <input type="file" name="picture_file" id="picture_file" accept="image/*" capture="environment">
                    </label>
                    <div class="file-upload-hint">JPG, PNG or WEBP · max 4 MB</div>
                </div>

                <button type="submit" class="save-btn">💾 <?= $lang['save_changes'] ?? 'Save Changes' ?></button>
            </form>
        </section>
    </div>
</main>

<script>
// Live avatar preview
document.getElementById('picture_file').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    document.getElementById('fileLabel').textContent = file.name;
    const reader = new FileReader();
    reader.onload = e => {
        const ring = document.getElementById('avatarRing');
        const existing = ring.querySelector('img, .avatar-placeholder');
        if (existing) existing.remove();
        const img = document.createElement('img');
        img.src = e.target.result;
        img.id = 'avatarPreview';
        img.alt = 'Preview';
        img.style.cssText = 'width:110px;height:110px;border-radius:50%;object-fit:cover;border:4px solid #22c55e;box-shadow:0 0 0 6px rgba(34,197,94,.18);';
        ring.insertBefore(img, ring.querySelector('label'));
    };
    reader.readAsDataURL(file);
});
</script>
</body>
</html>