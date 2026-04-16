<?php
/**
 * CivicTrack — citizen/submit.php
 */

// Define APP_ROOT relative to this folder
define('APP_ROOT', dirname(__DIR__)); 

require_once APP_ROOT . '/includes/config.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/lang.php';

// SECURITY: Ensure only citizens can access
requireCitizen(); 

// Pre-fill data from the verified session
$prefillName  = citizenName();
$prefillPhone = citizenPhone();
$currentId    = citizenId();

$pageTitle  = 'Report Issue';
$activePage = 'submit';

// --- FIX 1: Point to the correct header path ---
require_once APP_ROOT . '/includes/header.php';

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old    = $_POST;
    $errors = validateComplaintForm($_POST);

    if (empty($errors)) {
        $imagePath = null;
        
        // Handle Image Upload if provided
        if (!empty($_FILES['image']['name'])) {
            try { 
                $imagePath = handleImageUpload($_FILES['image']); 
            } catch (RuntimeException $ex) { 
                $errors[] = $ex->getMessage(); 
            }
        }

        if (empty($errors)) {
            try {
                // If the user selected a toilet sub-category, append it to the description
                $finalDesc = trim($_POST['description']);
                if (!empty($_POST['toilet_sub'])) {
                    $finalDesc = "[" . $_POST['toilet_sub'] . "] " . $finalDesc;
                }

                $newId = createComplaint([
                    'type'        => trim($_POST['type']),
                    'description' => $finalDesc,
                    'address'     => trim($_POST['address']),
                    'landmark'    => trim($_POST['landmark'] ?? ''),
                    'priority'    => trim($_POST['priority']),
                    'name'        => $prefillName,
                    'phone'       => $prefillPhone,
                ], $imagePath, $currentId);

                flash('success', "Complaint #{$newId} submitted!");
                
                // --- FIX 2: Correct Redirect Path ---
                redirect(APP_URL . '/citizen/view.php?id=' . urlencode($newId));

            } catch (Exception $ex) {
                $errors[] = 'Server error: ' . $ex->getMessage();
            }
        }
    }
}

$categories = getCategories();
?>

<div class="page-hero">
  <div class="breadcrumb">
    <a href="<?= APP_URL ?>/index.php"><?= te('nav_home') ?></a><span>›</span>
    <?= te('submit_title') ?>
  </div>
  <h1>📢 <?= te('submit_title') ?></h1>
  <p><?= te('submit_sub') ?></p>
</div>

<div class="section">
  <?php if (!empty($errors)): ?>
    <div class="flash-msg flash-error">
      ⚠️ Please fix the following:
      <ul style="margin:6px 0 0 16px"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <div class="card form-card">
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data" novalidate>

        <div class="form-section-title"><?= te('issue_details') ?></div>
        <div class="form-row">
          <div class="form-group">
            <label><?= te('issue_category') ?> <span class="req">*</span></label>
            <select class="form-control" name="type" id="typeSelect" required onchange="handleCategoryChange(this.value)">
              <option value="">— Select —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= ($old['type'] ?? '') === $cat ? 'selected' : '' ?>>
                  <?= e($cat) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label><?= te('issue_priority') ?> <span class="req">*</span></label>
            <select class="form-control" name="priority" required>
              <option value="">— Select —</option>
              <option value="High"   <?= ($old['priority']??'')==='High'   ?'selected':'' ?>><?= te('pri_high') ?></option>
              <option value="Medium" <?= ($old['priority']??'')==='Medium' ?'selected':'' ?>><?= te('pri_medium') ?></option>
              <option value="Low"    <?= ($old['priority']??'')==='Low'    ?'selected':'' ?>><?= te('pri_low') ?></option>
            </select>
          </div>
        </div>

        <div class="form-group" id="toiletSubGroup" style="display:none">
          <label>Toilet Issue Type <span class="req">*</span></label>
          <select class="form-control" name="toilet_sub" id="toiletSub">
            <option value="">— Select type —</option>
            <option value="Cleanliness Problem"><?= te('toilet_cleanliness') ?></option>
            <option value="No Water Supply"><?= te('toilet_water') ?></option>
            <option value="Broken Fixtures"><?= te('toilet_broken') ?></option>
            <option value="Poor Lighting / Safety"><?= te('toilet_lighting') ?></option>
            <option value="Locked / Inaccessible"><?= te('toilet_locked') ?></option>
            <option value="Other Toilet Issue"><?= te('toilet_other') ?></option>
          </select>
        </div>

        <div class="form-group">
          <label><?= te('issue_desc') ?> <span class="req">*</span></label>
          <textarea class="form-control" name="description" rows="4"
            placeholder="<?= te('issue_desc_ph') ?>" required minlength="20"><?= e($old['description'] ?? '') ?></textarea>
        </div>

        <div class="form-section-title"><?= te('location_details') ?></div>
        <div class="form-row">
          <div class="form-group">
            <label><?= te('full_address') ?> <span class="req">*</span></label>
            <input type="text" class="form-control" name="address"
                   value="<?= e($old['address'] ?? '') ?>" placeholder="e.g., 42 MG Road" required/>
          </div>
          <div class="form-group">
            <label><?= te('landmark') ?></label>
            <input type="text" class="form-control" name="landmark"
                   value="<?= e($old['landmark'] ?? '') ?>" placeholder="e.g., Near City Hall"/>
          </div>
        </div>

        <div class="form-section-title"><?= te('contact_evidence') ?></div>
        <div class="form-row">
          <div class="form-group">
            <label><?= te('your_name') ?></label>
            <input type="text" class="form-control" name="name"
                   value="<?= e($prefillName) ?>" readonly style="background:#f9f9f9; cursor:not-allowed;"/>
          </div>
          <div class="form-group">
            <label><?= te('phone_number') ?></label>
            <div class="phone-input-wrap">
              <span class="phone-prefix">+91</span>
              <input type="tel" class="form-control" name="phone"
                     value="<?= e($prefillPhone) ?>" readonly style="background:#f9f9f9; cursor:not-allowed;"/>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label><?= te('upload_photo') ?></label>
          <div class="upload-zone" onclick="document.getElementById('imgFile').click()">
            <div class="upload-icon">📷</div>
            <p><strong>Click to upload</strong></p>
          </div>
          <input type="file" id="imgFile" name="image" accept="image/*" style="display:none" onchange="previewImg(this)"/>
          <img id="imgPreview" alt="Preview" style="display:none;width:100%;max-height:180px;object-fit:cover;border-radius:9px;margin-top:10px"/>
        </div>

        <div class="form-actions">
          <a href="<?= APP_URL ?>/index.php" class="btn-ghost"><?= te('btn_cancel') ?></a>
          <button type="submit" class="btn-submit"><?= te('btn_submit') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function previewImg(input) {
  const p = document.getElementById('imgPreview');
  if (input.files && input.files[0]) {
    const r = new FileReader();
    r.onload = e => { p.src = e.target.result; p.style.display = 'block'; };
    r.readAsDataURL(input.files[0]);
  }
}

function handleCategoryChange(val) {
  const toiletGroup = document.getElementById('toiletSubGroup');
  const isToilet = val.toLowerCase().includes('toilet');
  toiletGroup.style.display = isToilet ? 'block' : 'none';
  document.getElementById('toiletSub').required = isToilet;
}
</script>

<?php 
// --- FIX 3: Point to the correct footer path ---
require_once APP_ROOT . '/includes/footer.php'; 
?>