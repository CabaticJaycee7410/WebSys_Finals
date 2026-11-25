
<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$pdo = require __DIR__ . '/config/database.php';
$userId = current_user_id();
$errors = array();
$section = isset($_GET['section']) ? $_GET['section'] : 'personal';
$validSections = array('picture', 'personal', 'password');

if (!in_array($section, $validSections, true)) {
    $section = 'personal';
}

$stmt = $pdo->prepare('SELECT user_id, full_name, email, password_hash, profile_picture FROM users WHERE user_id = ? LIMIT 1');
$stmt->execute(array($userId));
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION = array();
    session_destroy();
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $sectionMap = array('upload_picture' => 'picture', 'update_profile' => 'personal', 'change_password' => 'password');

    if (isset($sectionMap[$action])) {
        $section = $sectionMap[$action];
    }

    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : null;

    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Your session expired. Refresh the page and try again.';
    } elseif ($action === 'update_profile') {
        $fullName = trim((string)(isset($_POST['full_name']) ? $_POST['full_name'] : ''));
        $email = strtolower(trim((string)(isset($_POST['email']) ? $_POST['email'] : '')));

        if ($fullName === '' || strlen($fullName) > 150) {
            $errors[] = 'Enter a valid name with a maximum of 150 characters.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors[] = 'Enter a valid email address with a maximum of 190 characters.';
        }

        if (empty($errors)) {
            $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1');
            $check->execute(array($email, $userId));

            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $errors[] = 'That email address is already being used by another account.';
            } else {
                $update = $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE user_id = ?');
                $update->execute(array($fullName, $email, $userId));
                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_email'] = $email;
                set_flash('success', 'Your personal information has been updated.');
                redirect('account.php?section=personal');
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = (string)(isset($_POST['current_password']) ? $_POST['current_password'] : '');
        $newPassword = (string)(isset($_POST['new_password']) ? $_POST['new_password'] : '');
        $confirmPassword = (string)(isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '');

        if ($currentPassword === '' || !password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Your current password is incorrect.';
        }

        if (strlen($newPassword) < 8 || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $errors[] = 'Your new password must contain at least 8 characters, including uppercase, lowercase, and a number.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'The new password confirmation does not match.';
        }

        if ($newPassword !== '' && $currentPassword !== '' && $newPassword === $currentPassword) {
            $errors[] = 'Your new password must be different from your current password.';
        }

        if (empty($errors)) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
            $update->execute(array($passwordHash, $userId));
            session_regenerate_id(true);
            set_flash('success', 'Your password has been changed successfully.');
            redirect('account.php?section=password');
        }
    } elseif ($action === 'upload_picture') {
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            $uploadError = isset($_FILES['profile_picture']['error']) ? $_FILES['profile_picture']['error'] : UPLOAD_ERR_NO_FILE;

            if ($uploadError === UPLOAD_ERR_NO_FILE) {
                $errors[] = 'Please select a profile picture.';
            } elseif ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
                $errors[] = 'The image must not exceed 2 MB.';
            } else {
                $errors[] = 'The image could not be uploaded. Please try again.';
            }
        } else {
            $file = $_FILES['profile_picture'];

            if ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'The image must not exceed 2 MB.';
            } else {
                $imageInfo = @getimagesize($file['tmp_name']);
                $allowedTypes = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');

                if ($imageInfo === false || !isset($allowedTypes[$imageInfo[2]])) {
                    $errors[] = 'Only valid JPG and PNG images are allowed.';
                } else {
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);

                        if (($imageInfo[2] === IMAGETYPE_JPEG && $mimeType !== 'image/jpeg') || ($imageInfo[2] === IMAGETYPE_PNG && $mimeType !== 'image/png')) {
                            $errors[] = 'The uploaded image file is invalid.';
                        }
                    }

                    if (empty($errors)) {
                        $uploadDirectory = __DIR__ . '/uploads/profile-pictures/';

                        if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
                            $errors[] = 'The profile picture folder is missing or not writable.';
                        } else {
                            try {
                                if (function_exists('random_bytes')) {
                                    $fileName = bin2hex(random_bytes(16));
                                } elseif (function_exists('openssl_random_pseudo_bytes')) {
                                    $strong = false;
                                    $randomBytes = openssl_random_pseudo_bytes(16, $strong);

                                    if ($randomBytes === false || !$strong) {
                                        throw new Exception('Secure random generation failed.');
                                    }

                                    $fileName = bin2hex($randomBytes);
                                } else {
                                    throw new Exception('Secure random generation is unavailable.');
                                }

                                $newFileName = $fileName . '.' . $allowedTypes[$imageInfo[2]];
                                $destination = $uploadDirectory . $newFileName;
                                $relativePath = 'uploads/profile-pictures/' . $newFileName;

                                if (move_uploaded_file($file['tmp_name'], $destination)) {
                                    $update = $pdo->prepare('UPDATE users SET profile_picture = ? WHERE user_id = ?');

                                    try {
                                        $update->execute(array($relativePath, $userId));
                                    } catch (Exception $e) {
                                        if (is_file($destination)) {
                                            unlink($destination);
                                        }
                                        throw $e;
                                    }

                                    $oldPicture = $user['profile_picture'];
                                    $_SESSION['user_profile_picture'] = $relativePath;

                                    if (is_string($oldPicture) && strpos($oldPicture, 'uploads/profile-pictures/') === 0) {
                                        $oldFileName = basename($oldPicture);

                                        if ($oldFileName !== $newFileName) {
                                            $oldPath = $uploadDirectory . $oldFileName;

                                            if (is_file($oldPath)) {
                                                unlink($oldPath);
                                            }
                                        }
                                    }

                                    set_flash('success', 'Your profile picture has been updated.');
                                    redirect('account.php?section=picture');
                                } else {
                                    $errors[] = 'Unable to save the uploaded image. Check the folder permissions.';
                                }
                            } catch (Exception $e) {
                                $errors[] = 'Unable to securely process the image. Please try again.';
                            }
                        }
                    }
                }
            }
        }
    } else {
        $errors[] = 'Invalid account request.';
    }
}

$pictureUrl = '';

if (!empty($user['profile_picture'])) {
    $pictureName = basename($user['profile_picture']);
    $picturePath = __DIR__ . '/uploads/profile-pictures/' . $pictureName;

    if (strpos($user['profile_picture'], 'uploads/profile-pictures/') === 0 && is_file($picturePath)) {
        $pictureUrl = url('uploads/profile-pictures/' . rawurlencode($pictureName));
    }
}

$pageTitle = 'Account Settings';
require __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div>
        <p class="eyebrow">PERSONAL SETTINGS</p>
        <h1>Account Settings</h1>
        <p>Manage your personal account details and security.</p>
    </div>
    <a href="<?= h(url('dashboard.php')) ?>" class="secondary-button">← Back to Dashboard</a>
</section>

<?php if (!empty($errors)): ?>
    <div class="alert error">
        <?php foreach ($errors as $error): ?>
            <p><?= h($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="account-layout">
    <section class="card account-form-card">
        <?php if ($section === 'picture'): ?>
            <h2>Profile Picture</h2>
            <p class="muted-text">Upload or change the picture displayed on your account.</p>
            <div class="account-profile-preview">
                <?php if ($pictureUrl !== ''): ?>
                    <img src="<?= h($pictureUrl) ?>?v=<?= time() ?>" alt="Profile picture">
                <?php else: ?>
                    <div class="account-profile-placeholder"><?= h(strtoupper(substr($user['full_name'], 0, 1))) ?></div>
                <?php endif; ?>
            </div>
            <p class="muted-text">Choose a JPG or PNG image. Maximum size: 2 MB.</p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="upload_picture">
                <div class="form-group">
                    <label for="profile_picture">Choose Image</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                </div>
                <div class="account-form-actions">
                    <button type="submit" class="btn">Save Profile Picture</button>
                    <a href="<?= h(url('dashboard.php')) ?>" class="secondary-button">Cancel</a>
                </div>
            </form>
        <?php elseif ($section === 'personal'): ?>
            <h2>Personal Information</h2>
            <p class="muted-text">Update the name and email address connected to your account.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" maxlength="150" value="<?= h(isset($_POST['action']) && $_POST['action'] === 'update_profile' ? (isset($_POST['full_name']) ? $_POST['full_name'] : '') : $user['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" maxlength="190" value="<?= h(isset($_POST['action']) && $_POST['action'] === 'update_profile' ? (isset($_POST['email']) ? $_POST['email'] : '') : $user['email']) ?>" required>
                </div>
                <div class="account-form-actions">
                    <button type="submit" class="btn">Save Changes</button>
                    <a href="<?= h(url('dashboard.php')) ?>" class="secondary-button">Cancel</a>
                </div>
            </form>
        <?php elseif ($section === 'password'): ?>
            <h2>Change Password</h2>
            <p class="muted-text">Enter your current password before choosing a new one.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="8" required>
                    <small>At least 8 characters, including uppercase, lowercase, and a number.</small>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" minlength="8" required>
                </div>
                <div class="account-form-actions">
                    <button type="submit" class="btn">Save New Password</button>
                    <a href="<?= h(url('dashboard.php')) ?>" class="secondary-button">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
