<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['role'])) {
    header("Location: index.php"); // Redirect to the login page if not logged in
    exit();
}

// Check if the user's role is "pathologist"
if ($_SESSION['role'] !== 'pathologist') {
    echo "Access denied. You do not have permission to access this page.";
    exit();
}

// The rest of your "pathologist.php" code here
// Only users with the "pathologist" role can access this content
?>

<?php
require 'connection.php';

// Function to fetch all appointments by test type
function getAppointmentsByTestType($testType) {
    global $mysqli;
    $sql = "SELECT appointment.AppointmentID, appointment.PatientID, appointment.AppointmentDate, appointment.AppointmentTime, patient.Name 
            FROM appointment 
            INNER JOIN patient ON appointment.PatientID = patient.PatientID
            WHERE appointment.AppointmentID IN (
                SELECT AppointmentID FROM $testType
            )";
    $result = $mysqli->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to add test readings for Blood Test
// Function to add or update test readings for Blood Test
function addBloodTestReadings($bloodType, $haemoglobinLevel, $wbCount, $rbcCount, $plateletCount, $appointmentID) {
    global $mysqli;

    // Check if a row with the same AppointmentID already exists
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM bloodtest WHERE AppointmentID = ?");
    $stmt->bind_param("s", $appointmentID);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        // A row with the same AppointmentID exists, update the values
        $stmt = $mysqli->prepare("UPDATE bloodtest SET BloodType = ?, HaemoglobinLevel = ?, WBCount = ?, RBCCount = ?, PlateletCount = ? WHERE AppointmentID = ?");
        $stmt->bind_param("ssssss", $bloodType, $haemoglobinLevel, $wbCount, $rbcCount, $plateletCount, $appointmentID);
    } else {
        // No row with the same AppointmentID, insert a new row
        $stmt = $mysqli->prepare("INSERT INTO bloodtest (BloodType, HaemoglobinLevel, WBCount, RBCCount, PlateletCount, AppointmentID) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $bloodType, $haemoglobinLevel, $wbCount, $rbcCount, $plateletCount, $appointmentID);
    }
    
    if ($stmt->execute()) {
        return "Blood Test readings added or updated successfully!";
    } else {
        return "Error: " . $stmt->error;
    }
    $stmt->close();
}


// Function to add test readings for Urine Test
function addUrineTestReadings($urineColor, $urineAppearance, $pHLevel, $specificGravity, $proteinPresence, $glucoseLevel, $ketoneLevel, $appointmentID) {
    global $mysqli;

    // Check if a row with the same AppointmentID already exists
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM urinetest WHERE AppointmentID = ?");
    $stmt->bind_param("s", $appointmentID);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        // A row with the same AppointmentID exists, update the values
        $stmt = $mysqli->prepare("UPDATE urinetest SET UrineColor = ?, UrineAppearance = ?, pHLevel = ?, SpecificGravity = ?, ProteinPresence = ?, GlucoseLevel = ?, KetoneLevel = ? WHERE AppointmentID = ?");
        $stmt->bind_param("ssssssss", $urineColor, $urineAppearance, $pHLevel, $specificGravity, $proteinPresence, $glucoseLevel, $ketoneLevel, $appointmentID);
    } else {
        // No row with the same AppointmentID, insert a new row
        $stmt = $mysqli->prepare("INSERT INTO urinetest (UrineColor, UrineAppearance, pHLevel, SpecificGravity, ProteinPresence, GlucoseLevel, KetoneLevel, AppointmentID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $urineColor, $urineAppearance, $pHLevel, $specificGravity, $proteinPresence, $glucoseLevel, $ketoneLevel, $appointmentID);
    }
    
    if ($stmt->execute()) {
        return "Urine Test readings added or updated successfully!";
    } else {
        return "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Function to add test readings for Radiology Test
function addRadiologyTestReadings($scanType, $scanDate, $appointmentID) {
    global $mysqli;

    // Check if a row with the same AppointmentID already exists
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM radiologytest WHERE AppointmentID = ?");
    $stmt->bind_param("s", $appointmentID);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        // A row with the same AppointmentID exists, update the values
        $stmt = $mysqli->prepare("UPDATE radiologytest SET ScanType = ?, ScanDate = ? WHERE AppointmentID = ?");
        $stmt->bind_param("sss", $scanType, $scanDate, $appointmentID);
    } else {
        // No row with the same AppointmentID, insert a new row
        $stmt = $mysqli->prepare("INSERT INTO radiologytest (ScanType, ScanDate, AppointmentID) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $scanType, $scanDate, $appointmentID);
    }
    
    if ($stmt->execute()) {
        return "Radiology Test added or updated successfully!";
    } else {
        return "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Initialize the error message
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["add_readings"])) {
        $selectedAppointmentID = $_POST["appointment_id"];
        $bloodType = $_POST["blood_type"];
        $haemoglobinLevel = $_POST["haemoglobin_level"];
        $wbCount = $_POST["wb_count"];
        $rbcCount = $_POST["rbc_count"];
        $plateletCount = $_POST["platelet_count"];

        $result = addBloodTestReadings($bloodType, $haemoglobinLevel, $wbCount, $rbcCount, $plateletCount, $selectedAppointmentID);

        if (strpos($result, "Error:") === false) {
            $error_message = "Blood Test readings added successfully!";
        } else {
            $error_message = $result;
        }
    } elseif (isset($_POST["add_urine_readings"])) {
        $selectedAppointmentID = $_POST["appointment_id_urine"];
        $urineColor = $_POST["urine_color"];
        $urineAppearance = $_POST["urine_appearance"];
        $pHLevel = $_POST["ph_level"];
        $specificGravity = $_POST["specific_gravity"];
        $proteinPresence = $_POST["protein_presence"];
        $glucoseLevel = $_POST["glucose_level"];
        $ketoneLevel = $_POST["ketone_level"];

        $result = addUrineTestReadings($urineColor, $urineAppearance, $pHLevel, $specificGravity, $proteinPresence, $glucoseLevel, $ketoneLevel, $selectedAppointmentID);

        if (strpos($result, "Error:") === false) {
            $error_message = "Urine Test readings added successfully!";
        } else {
            $error_message = $result;
        }
    } elseif (isset($_POST["add_radiology_test"])) {
        $selectedAppointmentID = $_POST["appointment_id_radiology"];
        $scanType = $_POST["scan_type"];
        $scanDate = $_POST["scan_date"];

        $result = addRadiologyTestReadings($scanType, $scanDate, $selectedAppointmentID);

        if (strpos($result, "Error:") === false) {
            $error_message = "Radiology Test added successfully!";
        } else {
            $error_message = $result;
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Pathologist</title>
    <!-- Add Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Pathologist Dashboard</h1>

        <h2 class="mt-4">Add Blood Test Readings</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="appointment_id">Select an Appointment for Blood Test:</label>
                <select class="form-control" name="appointment_id" id="appointment_id">
                    <?php
                    $appointments = getAppointmentsByTestType('bloodtest');
                    foreach ($appointments as $appointment) {
                        echo "<option value='" . $appointment['AppointmentID'] . "'>" . $appointment['Name'] . " (ID: " . $appointment['AppointmentID'] . ")</option>";
                    }
                    ?>
                </select>
            </div>

            <div id="bloodTest">
                <!-- Blood Test fields here -->
                <div class="form-group">
                    <label for="blood_type">Blood Type:</label>
                    <input class="form-control" type="text" name="blood_type" id="blood_type">
                </div>
                <div class="form-group">
                    <label for="haemoglobin_level">Haemoglobin Level:</label>
                    <input class="form-control" type="text" name="haemoglobin_level" id="haemoglobin_level">
                </div>
                <div class="form-group">
                    <label for="wb_count">White Blood Cell (WBC) Count:</label>
                    <input class="form-control" type="text" name="wb_count" id="wb_count">
                </div>
                <div class="form-group">
                    <label for="rbc_count">Red Blood Cell (RBC) Count:</label>
                    <input class="form-control" type="text" name="rbc_count" id="rbc_count">
                </div>
                <div class="form-group">
                    <label for="platelet_count">Platelet Count:</label>
                    <input class="form-control" type="text" name="platelet_count" id="platelet_count">
                </div>
            </div>

            <input class="btn btn-primary" type="submit" name="add_readings" value="Add Blood Test Readings">
        </form>

        <h2 class="mt-4">Add Urine Test Readings</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="appointment_id_urine">Select an Appointment for Urine Test:</label>
                <select class="form-control" name="appointment_id_urine" id="appointment_id_urine">
                    <?php
                    $appointments = getAppointmentsByTestType('urinetest');
                    foreach ($appointments as $appointment) {
                        echo "<option value='" . $appointment['AppointmentID'] . "'>" . $appointment['Name'] . " (ID: " . $appointment['AppointmentID'] . ")</option>";
                    }
                    ?>
                </select>
            </div>

            <div id="urineTest">
                <!-- Urine Test fields here -->
                <div class="form-group">
                    <label for="urine_color">Urine Color:</label>
                    <input class="form-control" type="text" name="urine_color" id="urine_color">
                </div>
                <div class="form-group">
                    <label for="urine_appearance">Urine Appearance:</label>
                    <input class="form-control" type="text" name="urine_appearance" id="urine_appearance">
                </div>
                <div class="form-group">
                    <label for="ph_level">pH Level:</label>
                    <input class="form-control" type="text" name="ph_level" id="ph_level">
                </div>
                <div class="form-group">
                    <label for="specific_gravity">Specific Gravity:</label>
                    <input class="form-control" type="text" name="specific_gravity" id="specific_gravity">
                </div>
                <div class="form-group">
                    <label for="protein_presence">Protein Presence:</label>
                    <input class="form-control" type="text" name="protein_presence" id="protein_presence">
                </div>
                <div class="form-group">
                    <label for="glucose_level">Glucose Level:</label>
                    <input class="form-control" type="text" name="glucose_level" id="glucose_level">
                </div>
                <div class="form-group">
                    <label for="ketone_level">Ketone Level:</label>
                    <input class="form-control" type="text" name="ketone_level" id="ketone_level">
                </div>
            </div>

            <input class="btn btn-primary" type="submit" name="add_urine_readings" value="Add Urine Test Readings">
        </form>

        <h2 class="mt-4">Add Radiology Test</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="appointment_id_radiology">Select an Appointment for Radiology Test:</label>
                <select class="form-control" name="appointment_id_radiology" id="appointment_id_radiology">
                    <?php
                    $appointments = getAppointmentsByTestType('radiologytest');
                    foreach ($appointments as $appointment) {
                        echo "<option value='" . $appointment['AppointmentID'] . "'>" . $appointment['Name'] . " (ID: " . $appointment['AppointmentID'] . ")</option>";
                    }
                    ?>
                </select>
            </div>

            <div id="radiologyTest">
                <!-- Radiology Test fields here -->
                <div class="form-group">
                    <label for="scan_type">Scan Type:</label>
                    <input class="form-control" type="text" name="scan_type" id="scan_type">
                </div>
                <div class="form-group">
                    <label for="scan_date">Scan Date:</label>
                    <input class="form-control" type="text" name="scan_date" id="scan_date">
                </div>
            </div>

            <input class="btn btn-primary" type="submit" name="add_radiology_test" value="Add Radiology Test">
        </form>

        <?php
        if ($error_message) {
            echo "<p>" . $error_message . "</p>";
        }
        ?>
    </div>

    <!-- Add Bootstrap JS (Popper.js and Bootstrap.js) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
