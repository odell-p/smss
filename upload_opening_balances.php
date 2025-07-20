<?php
// Include the main header
require_once '../includes/header.php';

// THIS IS THE MOST IMPORTANT LINE FOR USING THE LIBRARY
// It includes the Composer autoloader, which makes all the library classes available.
require_once __DIR__ . '/../vendor/autoload.php';

// Use statements to make it easier to call the library's classes
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Initialize feedback messages
$success_message = '';
$error_message = '';
$processed_summary = [];

// --- Handle the Excel File Upload ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file']) && isset($_POST['target_session_id'])) {
    
    // --- Basic File Validation ---
    if ($_FILES['excel_file']['error'] !== UPLOAD_OK) {
        $error_message = "An error occurred during file upload. Please try again.";
    } else {
        $target_session_id = intval($_POST['target_session_id']);
        $inputFileName = $_FILES['excel_file']['tmp_name'];

        try {
            // --- Get the ID for the "Opening Balance" fee component ---
            $stmt_comp = $conn->prepare("SELECT id FROM fee_components WHERE name = 'Opening Balance B/F' LIMIT 1");
            $stmt_comp->execute();
            $comp_result = $stmt_comp->get_result();
            if ($comp_result->num_rows == 0) {
                throw new Exception("The 'Opening Balance B/F' fee component has not been created in the system yet. Please create it first.");
            }
            $opening_balance_component_id = $comp_result->fetch_assoc()['id'];

            // --- Load the Excel file using the library ---
            $spreadsheet = IOFactory::load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();

            $success_count = 0;
            $error_count = 0;
            $error_details = [];

            // Prepare the INSERT/UPDATE statement once
            $sql = "INSERT INTO session_fee_structure (academic_session_id, programme_id, fee_component_id, amount) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE amount = VALUES(amount)";
            $stmt_insert = $conn->prepare($sql);

            // --- Loop through each row of the spreadsheet (starting from row 2 to skip headers) ---
            for ($row = 2; $row <= $highestRow; ++$row) {
                $student_id = trim($worksheet->getCell('A' . $row)->getValue());
                $amount_owed = trim($worksheet->getCell('B' . $row)->getValue());

                // Skip empty rows
                if (empty($student_id) || !is_numeric($student_id)) {
                    continue;
                }
                
                // --- Validate data from the row ---
                $student_id = intval($student_id);
                $amount_owed = floatval($amount_owed);

                // Find the student's programme
                $stmt_prog = $conn->prepare("SELECT programme_id FROM users WHERE id = ?");
                $stmt_prog->bind_param("i", $student_id);
                $stmt_prog->execute();
                $prog_result = $stmt_prog->get_result();

                if ($prog_result->num_rows > 0) {
                    $student_data = $prog_result->fetch_assoc();
                    $programme_id = $student_data['programme_id'];
                    
                    if(empty($programme_id)) {
                         $error_count++;
                         $error_details[] = "Row {$row}: Student ID {$student_id} does not have a programme assigned.";
                         continue;
                    }

                    // Execute the prepared statement
                    $stmt_insert->bind_param("iiid", $target_session_id, $programme_id, $opening_balance_component_id, $amount_owed);
                    if ($stmt_insert->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $error_details[] = "Row {$row}: Database error for Student ID {$student_id}.";
                    }
                } else {
                    $error_count++;
                    $error_details[] = "Row {$row}: Student ID {$student_id} not found in the system.";
                }
            }
            
            // --- Set summary messages for display ---
            $success_message = "File processed successfully! {$success_count} records were added/updated.";
            if ($error_count > 0) {
                $error_message = "{$error_count} errors were encountered. Please check the details below.";
                $processed_summary = $error_details;
            }

        } catch (Exception $e) {
            $error_message = "Error processing file: " . $e->getMessage();
        }
    }
}

// Fetch academic sessions for the dropdown
$sessions = $conn->query("SELECT id, name FROM academic_sessions ORDER BY name DESC");
?>

<div class="page-header">
    <h1>Upload Opening Balances</h1>
    <p>Upload previous fees owed by students from an Excel file.</p>
</div>

<!-- Display Feedback -->
<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>
<?php if (!empty($processed_summary)): ?>
    <div class="content-panel">
        <h2>Processing Errors</h2>
        <ul>
            <?php foreach ($processed_summary as $detail): ?>
                <li><?php echo htmlspecialchars($detail); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="content-panel">
    <h2>Instructions</h2>
    <ol>
        <li>First, ensure you have created a fee component named exactly "<b>Opening Balance B/F</b>".</li>
        <li>Download the Excel template file.</li>
        <li>Fill the template with student data. The first column should be the Student's System ID, and the second should be the amount they owe. Do not change the column headers.</li>
        <li>Select the academic session you want to apply these balances to.</li>
        <li>Choose your completed Excel file and click "Upload".</li>
    </ol>
    <a href="../assets/templates/opening_balance_template.xlsx" class="btn btn-outline" download>Download Excel Template</a>
    <hr style="margin: 1.5rem 0;">
    
    <form action="upload_opening_balances.php" method="post" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group">
                <label for="target_session_id">Target Academic Session</label>
                <select name="target_session_id" id="target_session_id" required>
                    <option value="">-- Select a Session --</option>
                    <?php while($session = $sessions->fetch_assoc()): ?>
                        <option value="<?php echo $session['id']; ?>"><?php echo htmlspecialchars($session['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="excel_file">Upload Excel File (.xlsx)</label>
                <input type="file" name="excel_file" id="excel_file" required accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Upload and Process File</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>