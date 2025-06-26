<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $address = sanitize($_POST['address']);
    $contact_person = sanitize($_POST['contact_person']);
    $contact_phone = sanitize($_POST['contact_phone']);
    $logo = sanitize($_POST['logo']);
    $industry = sanitize($_POST['industry']);
    $contract_start_date = sanitize($_POST['contract_start_date']);
    $contract_end_date = sanitize($_POST['contract_end_date']);

    if (empty($name)) {
        $errors[] = 'Organization name is required.';
    }

    if (empty($errors)) {
        if ($id > 0) {
            // Update existing organization
            $query = "UPDATE organizations SET name = ?, address = ?, contact_person = ?, contact_phone = ?, logo = ?, industry = ?, contract_start_date = ?, contract_end_date = ? WHERE user_id = ?";
            $result = executeQuery($query, [$name, $address, $contact_person, $contact_phone, $logo, $industry, $contract_start_date, $contract_end_date, $id]);
            if ($result) {
                $success = 'Organization updated successfully.';
            } else {
                $errors[] = 'Failed to update organization.';
            }
        } else {
            // Insert new organization
            $query = "INSERT INTO organizations (name, address, contact_person, contact_phone, logo, industry, contract_start_date, contract_end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $result = executeQuery($query, [$name, $address, $contact_person, $contact_phone, $logo, $industry, $contract_start_date, $contract_end_date]);
            if ($result) {
                $success = 'Organization added successfully.';
                $id = $conn->insert_id;
            } else {
                $errors[] = 'Failed to add organization.';
            }
        }
    }
}

// Fetch organization data if editing
$organization = [
    'name' => '',
    'address' => '',
    'contact_person' => '',
    'contact_phone' => '',
    'logo' => '',
    'industry' => '',
    'contract_start_date' => '',
    'contract_end_date' => ''
];

if ($id > 0) {
    $organization = executeQuery("SELECT * FROM organizations WHERE user_id = ?", [$id], ['single' => true]);
    if (!$organization) {
        die('Organization not found.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $id > 0 ? 'Edit' : 'Add'; ?> Organization | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/styles.css" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/admin-sidebar.php'; ?>

        <main class="main-content">
            <?php include '../includes/top-nav.php'; ?>

            <div class="dashboard-content">
                <div class="dashboard-header">
                    <h1><?php echo $id > 0 ? 'Edit' : 'Add'; ?> Organization</h1>
                    <a href="organizations.php" class="btn btn-outline">
                        <i data-lucide="arrow-left"></i> Back
                    </a>
                </div>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo sanitize($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="form-group">
                        <label for="name">Organization Name</label>
                        <input type="text" id="name" name="name" value="<?php echo sanitize($organization['name']); ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo sanitize($organization['address']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="contact_person">Contact Person</label>
                        <input type="text" id="contact_person" name="contact_person" value="<?php echo sanitize($organization['contact_person']); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="contact_phone">Contact Phone</label>
                        <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo sanitize($organization['contact_phone']); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="logo">Logo URL</label>
                        <input type="text" id="logo" name="logo" value="<?php echo sanitize($organization['logo']); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="industry">Industry</label>
                        <input type="text" id="industry" name="industry" value="<?php echo sanitize($organization['industry']); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="contract_start_date">Contract Start Date</label>
                        <input type="date" id="contract_start_date" name="contract_start_date" value="<?php echo sanitize($organization['contract_start_date']); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="contract_end_date">Contract End Date</label>
                        <input type="date" id="contract_end_date" name="contract_end_date" value="<?php echo sanitize($organization['contract_end_date']); ?>" />
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save"></i> <?php echo $id > 0 ? 'Update' : 'Add'; ?> Organization
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
