<?php
// ---------------------------------------------------------------
// Database connection (local MariaDB, root user, no password)
// ---------------------------------------------------------------
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

// Make mysqli report errors as exceptions so we can catch them below
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$successMessage = "";
$errorMessage   = "";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (Exception $e) {
    $conn = null;
    $errorMessage = "Error: Could not connect to the database.";
}

// ---------------------------------------------------------------
// Handle the POST request
// ---------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && $conn) {
    // Capture all four variables from the form
    $FirstName   = trim($_POST["FirstName"] ?? "");
    $LastName    = trim($_POST["LastName"] ?? "");
    $GovPosition = trim($_POST["GovPosition"] ?? "");
    $Location    = trim($_POST["Location"] ?? "");

    try {
        // =========================================================
        // INTENTIONAL FLAW (for QA testing purposes):
        // The form collects four fields, but the Location column is
        // completely OMITTED from both the SQL string below and the
        // bind_param() call. Only FirstName, LastName, and
        // GovPosition are saved. The user sees a success message,
        // but the Location value is silently lost in the database.
        // A QA tester should catch this by checking the table.
        // =========================================================
        $stmt = $conn->prepare(
            "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)"
        );

        if ($stmt) {
            $stmt->bind_param("sss", $FirstName, $LastName, $GovPosition); // No $Location here (the flaw)
            $stmt->execute();
            $stmt->close();
            $successMessage = "Success! The wish note has been sent.";
        } else {
            $errorMessage = "Error: The query could not be prepared.";
        }
    } catch (Exception $e) {
        // Fallback error if the query fails to prepare or run
        $errorMessage = "Error: The query could not be prepared.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kara David's Wish Note</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 24px 16px;
            min-height: 100vh;
            background: #eceff1;
            font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #263238;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-radius: 8px;
            padding: 32px 28px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }

        h2 {
            margin: 0 0 24px;
            text-align: center;
            color: #1976d2;
            font-size: 1.6rem;
        }

        .message {
            padding: 12px 14px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        .message.success { background: #e3f2fd; color: #0d47a1; border-left: 4px solid #1976d2; }
        .message.error   { background: #ffebee; color: #b71c1c; border-left: 4px solid #d32f2f; }

        label {
            display: block;
            margin: 16px 0 6px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        input[type="text"] {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #b0bec5;
            border-radius: 4px;
            font-size: 1rem;
            background: #fafafa;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #1976d2;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.2);
        }

        .btn {
            border: none;
            border-radius: 4px;
            padding: 12px 20px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary { background: #1976d2; color: #ffffff; }
        .btn-primary:hover { background: #0d47a1; }
        .btn-secondary { background: #cfd8dc; color: #263238; }
        .btn-secondary:hover { background: #b0bec5; }

        #sendBtn { width: 100%; margin-top: 24px; }

        /* Modal */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 100;
        }
        .overlay.open { display: flex; }

        .modal {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.3);
        }
        .modal h3 { margin: 0 0 16px; color: #1976d2; }
        .modal dl { margin: 0 0 20px; }
        .modal dt { font-weight: 600; font-size: 0.85rem; color: #546e7a; margin-top: 10px; }
        .modal dd { margin: 2px 0 0; word-break: break-word; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Kara David's Wish Note</h2>

        <?php if ($successMessage): ?>
            <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <form id="wishForm" method="POST" action="">
            <label for="FirstName">Target's First Name</label>
            <input type="text" id="FirstName" name="FirstName" maxlength="30" required>

            <label for="LastName">Target's Last Name</label>
            <input type="text" id="LastName" name="LastName" maxlength="30" required>

            <label for="GovPosition">Target's Government Position</label>
            <input type="text" id="GovPosition" name="GovPosition" maxlength="20" required>

            <label for="Location">Target's Location</label>
            <input type="text" id="Location" name="Location" maxlength="40">

            <button type="submit" id="sendBtn" class="btn btn-primary">Send Wish Note</button>
        </form>
    </div>

    <!-- Verification modal -->
    <div class="overlay" id="modalOverlay">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <h3 id="modalTitle">Verify Target Data</h3>
            <dl>
                <dt>Target's First Name</dt><dd id="mFirstName"></dd>
                <dt>Target's Last Name</dt><dd id="mLastName"></dd>
                <dt>Target's Government Position</dt><dd id="mGovPosition"></dd>
                <dt>Target's Location</dt><dd id="mLocation"></dd>
            </dl>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmBtn">Confirm &amp; Send</button>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById("wishForm");
        const overlay = document.getElementById("modalOverlay");

        // Intercept the submission and show the verification modal
        form.addEventListener("submit", function (e) {
            e.preventDefault();

            document.getElementById("mFirstName").textContent   = form.FirstName.value;
            document.getElementById("mLastName").textContent    = form.LastName.value;
            document.getElementById("mGovPosition").textContent = form.GovPosition.value;
            document.getElementById("mLocation").textContent    = form.Location.value || "(not provided)";

            overlay.classList.add("open");
        });

        // Cancel closes the modal
        document.getElementById("cancelBtn").addEventListener("click", function () {
            overlay.classList.remove("open");
        });

        // Confirm & Send bypasses the interception and posts the form
        document.getElementById("confirmBtn").addEventListener("click", function () {
            form.submit();
        });
    </script>
</body>
</html>