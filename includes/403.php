<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied | SVMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="error-page">
        <div>
            <h1>403</h1>
            <h4 style="color:var(--text-primary);margin-bottom:8px;">Access Denied</h4>
            <p>You don't have permission to access this page.</p>
            <a href="<?= BASE_PATH ?>/index.php" class="btn-primary-custom mt-3">
                <i class="bi bi-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>
