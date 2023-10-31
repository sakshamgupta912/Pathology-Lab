<?php

require('PHPMailer-master/src/PHPMailer.php');
require('PHPMailer-master/src/Exception.php');
require('PHPMailer-master/src/SMTP.php');
require('fpdf/fpdf.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;



$mail = new PHPMailer();
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'sakshamdev3@gmail.com';
$mail->Password = 'skhiiyshmgxeqyvy';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;



// Function to send an email with the report and bill
function sendEmailWithReport($email, $report, $totalBill)
{   $patientAge = calculateAgeFromDOB($report['PatientInfo']['DOB']);
        
    $message = "Dear " . $report['PatientInfo']['Name'] . ",\n\n";
    $message .= "Here is your pathology report and bill:\n\n";

    // Include the report and bill details in the email message
    $message .= "Patient Information:\n";
    $message .= "Name: " . $report['PatientInfo']['Name'] . "\n";
    $message .= "Gender: " . $report['PatientInfo']['Gender'] . "\n";
    $message .= "DOB: " . $report['PatientInfo']['DOB'] . "\n";
    $message .= "Age: " . $patientAge . "\n";
    // Include test results in the email
    if (isset($report['BloodTest'])) {
        $message .= "\nBlood Test:\n";
        $message .= "Blood Type: " . $report['BloodTest']['BloodType'] . "\n";
        $message .= "Haemoglobin Level: " . $report['BloodTest']['HaemoglobinLevel'] . "\n";
        $message .= "White Blood Cell (WBC) Count: " . $report['BloodTest']['WBCount'] . "\n";
        $message .= "Red Blood Cell (RBC) Count: " . $report['BloodTest']['RBCCount'] . "\n";
        $message .= "Platelet Count: " . $report['BloodTest']['PlateletCount'] . "\n";
    }

    if (isset($report['UrineTest'])) {
        $message .= "\nUrine Test:\n";
        $message .= "Urine Color: " . $report['UrineTest']['UrineColor'] . "\n";
        $message .= "Urine Appearance: " . $report['UrineTest']['UrineAppearance'] . "\n";
        $message .= "pH Level: " . $report['UrineTest']['pHLevel'] . "\n";
        $message .= "Specific Gravity: " . $report['UrineTest']['SpecificGravity'] . "\n";
        $message .= "Protein Presence: " . $report['UrineTest']['ProteinPresence'] . "\n";
        $message .= "Glucose Level: " . $report['UrineTest']['GlucoseLevel'] . "\n";
        $message .= "Ketone Level: " . $report['UrineTest']['KetoneLevel'] . "\n";
    }

    if (isset($report['RadiologyTest'])) {
        $message .= "\nRadiology Test:\n";
        $message .= "Scan Type: " . $report['RadiologyTest']['ScanType'] . "\n";
        $message .= "Scan Date: " . $report['RadiologyTest']['ScanDate'] . "\n";
    }
    // Include the total bill in the email
    $message .= "\nTotal Bill: Rs." . $totalBill . "\n";

    $subject = "Pathology Report and Bill";

    // Create a PHPMailer instance and send the email
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'sakshamdev3@gmail.com';
    $mail->Password = 'skhiiyshmgxeqyvy';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->setFrom('sakshamdev3@gmail.com', 'Your Name');
    $mail->addAddress($email, $report['PatientInfo']['Name']); // Use the patient's email address

    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body = $message;

    if ($mail->send()) {
        echo "<script>alert('Email sent successfully! ');</script>";
        return "";
    } else {
        echo "<script>alert('Email could not be sent. Error: " . $mail->ErrorInfo . "');</script>";
        return "Email could not be sent. Mailer Error: " . $mail->ErrorInfo;
    }
}

?>

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
if (isset($_POST["logout"])) {
    // Unset all session variables
    session_unset();

    // Destroy the session
    session_destroy();

    // Redirect the user to the login page
    header("Location: index.php");
    exit();
}

?>

<?php
require 'connection.php';

// Function to fetch all appointments by test type
function getAppointmentsByTestType($testType)
{
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
function addBloodTestReadings($bloodType, $haemoglobinLevel, $wbCount, $rbcCount, $plateletCount, $appointmentID)
{
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
function addUrineTestReadings($urineColor, $urineAppearance, $pHLevel, $specificGravity, $proteinPresence, $glucoseLevel, $ketoneLevel, $appointmentID)
{
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
function addRadiologyTestReadings($scanType, $scanDate, $appointmentID)
{
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
<!-- Add the code for generating a report here -->
<?php
function calculateAgeFromDOB($dob)
{
    // Convert DOB to a DateTime object
    $dobDate = new DateTime($dob);

    // Get the current date
    $currentDate = new DateTime();

    // Calculate the interval (difference) between DOB and current date
    $ageInterval = $currentDate->diff($dobDate);

    // Extract the years from the interval
    $age = $ageInterval->y;

    return $age;
}
?>
<?php
// Function to generate a report and bill based on appointment ID
function generateReportAndBill($selectedAppointmentID)
{
    global $mysqli;
    $report = array();

    // Fetch patient name
    // Fetch patient information, including the email address
    $sql = "SELECT patient.PatientID, patient.Name, patient.Address, patient.Contact, patient.Gender, patient.DOB, patient.Email
            FROM patient
            JOIN appointment ON patient.PatientID = appointment.PatientID
            WHERE appointment.AppointmentID = ?";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        echo "Error: " . $mysqli->error;
    } else {
        $stmt->bind_param("s", $selectedAppointmentID);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result && $patientResult = $result->fetch_assoc()) {
                $report['PatientInfo'] = $patientResult;
            } else {
                echo "No data found for the selected appointment ID.";
            }
            $stmt->close();
        } else {
            echo "Error executing the query: " . $stmt->error;
        }
    }

    //$report['PatientInfo'] = $patientResult;

    // Fetch blood test readings
    $sql = "SELECT * FROM bloodtest WHERE AppointmentID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $selectedAppointmentID);
    $stmt->execute();
    $bloodTestResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $report['BloodTest'] = $bloodTestResult;

    // Fetch urine test readings
    $sql = "SELECT * FROM urinetest WHERE AppointmentID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $selectedAppointmentID);
    $stmt->execute();
    $urineTestResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $report['UrineTest'] = $urineTestResult;

    // Fetch radiology test details
    $sql = "SELECT * FROM radiologytest WHERE AppointmentID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $selectedAppointmentID);
    $stmt->execute();
    $radiologyTestResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $report['RadiologyTest'] = $radiologyTestResult;

    return $report;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["generate_report_and_bill"])) {
        $selectedAppointmentID = $_POST["selected_appointment_id"];
        $report = generateReportAndBill($selectedAppointmentID);
        $patientAge = calculateAgeFromDOB($report['PatientInfo']['DOB']);
        // Define prices for the tests
        $bloodTestPrice = calculateTestPrice($patientAge, 'bloodtest');
                
        
        // Calculate Urine Test Price
        $urineTestPrice = calculateTestPrice($patientAge, 'urinetest');
        

        // Calculate Radiology Test Price
        $radiologyTestPrice = calculateTestPrice($patientAge, 'radiologytest');
        
        // Calculate total bill
        $totalBill = 0;
        if (isset($report['BloodTest'])) {
            $totalBill += $bloodTestPrice;
        }
        if (isset($report['UrineTest'])) {
            $totalBill += $urineTestPrice;
        }
        if (isset($report['RadiologyTest'])) {
            $totalBill += $radiologyTestPrice;
        }

        // $email = $report['PatientInfo']['Email'];  // Get the patient's email from the report data
        // $emailResult = sendEmailWithReport($email, $report, $totalBill);

        // echo $emailResult;
    }
    if (isset($_POST["generate_report_and_bill_and_mail"])) {
        $selectedAppointmentID = $_POST["selected_appointment_id"];
        $report = generateReportAndBill($selectedAppointmentID);
        $patientAge = calculateAgeFromDOB($report['PatientInfo']['DOB']);
        // Define prices for the tests
         $bloodTestPrice = calculateTestPrice($patientAge, 'bloodtest');
                
        
                // Calculate Urine Test Price
                $urineTestPrice = calculateTestPrice($patientAge, 'urinetest');
                
        
                // Calculate Radiology Test Price
                $radiologyTestPrice = calculateTestPrice($patientAge, 'radiologytest');
                

        // Calculate total bill
        $totalBill = 0;
        if (isset($report['BloodTest'])) {
            $totalBill += $bloodTestPrice;
        }
        if (isset($report['UrineTest'])) {
            $totalBill += $urineTestPrice;
        }
        if (isset($report['RadiologyTest'])) {
            $totalBill += $radiologyTestPrice;
        }

        $email = $report['PatientInfo']['Email'];  // Get the patient's email from the report data
        $emailResult = sendEmailWithReport($email, $report, $totalBill);

        echo $emailResult;
    }
}

function calculateTestPrice($age, $testType)
{
    // Connect to your database (you should have a database connection established)

    // Define variables for procedure input and output

    $patientAge = $age;
    $testType = $testType;
    $testPrice = 0.00;  // Default value

    $host = "localhost"; // Hostname
    $username = "root"; // MySQL username
    $password = ""; // MySQL password
    $database = "pathologylab"; // Database name

    try {
        $dsn = "mysql:host=$host;dbname=$database";
        $pdo = new PDO($dsn, $username, $password);

        // Set PDO to throw exceptions on error
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // Prepare and execute the procedure call
    $query = "CALL CalculateTestPrice(:patient_age, :test_type, @test_price)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':patient_age', $patientAge, PDO::PARAM_INT);
    $stmt->bindParam(':test_type', $testType, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the result from the stored procedure
    $stmt = $pdo->query("SELECT @test_price");
    $testPrice = $stmt->fetchColumn();

    // Close the database connection (if necessary)

    return $testPrice;
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
<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Pathologist</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
     
               
            </ul>
            
            <form class="form-inline my-2 my-lg-0 " method="post">
              
              <button  type="submit" name="logout" class="btn btn-danger my-2 my-sm-0" type="submit">Logout</button>
              
          </form>
        </div>
    </nav>
    <div class="container">
        

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

        <h2 class="mt-4">Add Radiology Test Readings</h2>
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
                    <input class="form-control" type="date" name="scan_date" id="scan_date">
                </div>

            </div>

            <input class="btn btn-primary" type="submit" name="add_radiology_test" value="Add Radiology Test">
        </form>

        <?php
        if ($error_message) {
            echo "<p>" . $error_message . "</p>";
        }
        ?>





        <h2 class="mt-4">Generate Report and Bill</h2>

        <form method="post" action="">
            <div class="form-group">
                <label for="selected_appointment_id">Select an Appointment ID for Report and Bill:</label>
                <select class="form-control" name="selected_appointment_id" id="selected_appointment_id">
                    <?php
                    $bloodTestAppointments = getAppointmentsByTestType('bloodtest');
                    $urineTestAppointments = getAppointmentsByTestType('urinetest');
                    $radiologyTestAppointments = getAppointmentsByTestType('radiologytest');

                    // Merge all appointment IDs into one array
                    $allAppointments = array_merge($bloodTestAppointments, $urineTestAppointments, $radiologyTestAppointments);

                    foreach ($allAppointments as $appointment) {
                        echo "<option value='" . $appointment['AppointmentID'] . "'>" . $appointment['Name'] . " (ID: " . $appointment['AppointmentID'] . ")</option>";
                    }
                    ?>
                </select>
            </div>

            <input class="btn btn-primary" type="submit" name="generate_report_and_bill" value="Generate Report and Bill">
            <input class="btn btn-primary" type="submit" name="generate_report_and_bill_and_mail" value="Send mail">
        </form>


        <?php
        if (isset($_POST["generate_report_and_bill"])) {
            $selectedAppointmentID = $_POST["selected_appointment_id"];
            $report = generateReportAndBill($selectedAppointmentID);
            $patientAge = calculateAgeFromDOB($report['PatientInfo']['DOB']);
            if ($report) {
                $patientAge = calculateAgeFromDOB($report['PatientInfo']['DOB']);

            
        
                // Calculate Blood Test Price
                $bloodTestPrice = calculateTestPrice($patientAge, 'bloodtest');
                
        
                // Calculate Urine Test Price
                $urineTestPrice = calculateTestPrice($patientAge, 'urinetest');
                
        
                // Calculate Radiology Test Price
                $radiologyTestPrice = calculateTestPrice($patientAge, 'radiologytest');
                
                

                echo "<h2 class='mt-4'>Patient Information</h2>";
                echo "<p><b>Name:</b> " . $report['PatientInfo']['Name'] . "</p>";
                echo "<p><b>Gender:</b> " . $report['PatientInfo']['Gender'] . "</p>";
                echo "<p><b>DOB:</b> " . $report['PatientInfo']['DOB'] . "</p>";
                if (isset($report['PatientInfo']['DOB'])) {
                    $age = calculateAgeFromDOB($report['PatientInfo']['DOB']);
                    echo "<p><b>Age:</b> " . $age . " years</p>";
                } else {
                    echo "<p><b>Age:</b> N/A</p>";
                }
                echo "<p><b>Address:</b> " . $report['PatientInfo']['Address'] . "</p>";
                echo "<p><b>Contact Number:</b> " . $report['PatientInfo']['Contact'] . "</p>";

                echo "<h2 class='mt-4'>Report</h2>";
                echo "<div class='table-responsive'>";
                echo "<table class='table table-bordered'>";
                echo "<thead class='thead-dark'>";
                echo "<tr>";
                echo "<th>Test Type</th>";
                echo "<th>Investigation</th>";
                echo "<th>Result</th>";
                echo "<th>Reference Value</th>";
                echo "<th>Unit</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";

                if (isset($report['BloodTest'])) {
                    echo "<tr>";
                    echo "<td>Blood Test</td>";
                    echo "<td>Blood Group</td>";
                    echo "<td>" . $report['BloodTest']['BloodType'] . "</td>";
                    echo "<td></td>"; // Reference Value
                    echo "<td></td>"; // Unit
                    echo "</tr>";

                    echo "<tr>";
                    echo "<td></td>";
                    echo "<td>Haemoglobin Level</td>";
                    echo "<td>" . $report['BloodTest']['HaemoglobinLevel'] . "</td>";
                    echo "<td>13.0-17.0</td>"; // Reference Value
                    echo "<td>g/dL</td>"; // Unit
                    echo "</tr>";

                    echo "<tr>";
                    echo "<td></td>";
                    echo "<td>White Blood Cell (WBC) Count</td>";
                    echo "<td>" . $report['BloodTest']['WBCount'] . "</td>";
                    echo "<td>4000-11000</td>"; // Reference Value
                    echo "<td>cumm</td>"; // Unit
                    echo "</tr>";

                    echo "<tr>";
                    echo "<td></td>";
                    echo "<td>Red Blood Cell (RBC) Count</td>";
                    echo "<td>" . $report['BloodTest']['RBCCount'] . "</td>";
                    echo "<td>4.5-5.5</td>"; // Reference Value
                    echo "<td>mill/cumm</td>"; // Unit
                    echo "</tr>";

                    echo "<tr>";
                    echo "<td></td>";
                    echo "<td>Platelet Count</td>";
                    echo "<td>" . $report['BloodTest']['PlateletCount'] . "</td>";
                    echo "<td>150000-410000</td>"; // Reference Value
                    echo "<td>cumm</td>"; // Unit
                    echo "</tr>";
                }

                if (isset($report['UrineTest'])) {
                    echo "<tr>";
                    echo "<td>Urine Test</td>";
                    echo "<td>Urine Color</td>";
                    echo "<td>" . $report['UrineTest']['UrineColor'] . "</td>";
                    echo "<td></td>"; // Reference Value
                    echo "<td></td>"; // Unit
                    echo "</tr>";

                    echo "<tr>";
                    echo "<td></td>";
                    echo "<td>Urine Appearance</td>";
                    echo "<td>" . $report['UrineTest']['UrineAppearance'] . "</td>";
                    echo "<td></td>"; // Reference Value
                    echo "<td></td>"; // Unit
                    echo "</tr>";

                    echo "<tr>";
                    echo "<td></td>";
                    echo "<td>pH Level</td>";
                    echo "<td>" . $report['UrineTest']['pHLevel'] . "</td>";
                    echo "<td></td>"; // Reference Value
                    echo "<td></td>"; // Unit
                    echo "</tr>";

                    echo "<tr>";
                    echo "<td></td>";
                    echo "<td>Specific Gravity</td>";
                    echo "<td>" . $report['UrineTest']['SpecificGravity'] . "</td>";
                    echo "<td></td>"; // Reference Value
                    echo "<td></td>"; // Unit
                    echo "</tr>";

                    echo "<tr>";
                    echo "<td></td>";
                    echo "<td>Protein Presence</td>";
                    echo "<td>" . $report['UrineTest']['ProteinPresence'] . "</td>";
                    echo "<td></td>"; // Reference Value
                    echo "<td></td>"; // Unit
                    echo "</tr>";

                    echo "<tr>";
                    echo "<td></td>";
                    echo "<td>Glucose Level</td>";
                    echo "<td>" . $report['UrineTest']['GlucoseLevel'] . "</td>";
                    echo "<td></td>"; // Reference Value
                    echo "<td></td>"; // Unit
                    echo "</tr>";

                    echo "<tr>";
                    echo "<td></td>";
                    echo "<td>Ketone Level</td>";
                    echo "<td>" . $report['UrineTest']['KetoneLevel'] . "</td>";
                    echo "<td></td>"; // Reference Value
                    echo "<td></td>"; // Unit
                    echo "</tr>";
                }

                if (isset($report['RadiologyTest'])) {
                    echo "<tr>";
                    echo "<td>Radiology Test</td>";
                    echo "<td>Scan Type</td>";
                    echo "<td>" . $report['RadiologyTest']['ScanType'] . "</td>";
                    echo "<td></td>"; // Reference Value
                    echo "<td></td>"; // Unit
                    echo "</tr>";

                    echo "<tr>";
                    echo "<td></td>";
                    echo "<td>Scan Date</td>";
                    echo "<td>" . $report['RadiologyTest']['ScanDate'] . "</td>";
                    echo "<td></td>"; // Reference Value
                    echo "<td></td>"; // Unit
                    echo "</tr>";
                }

                echo "</tbody>";
                echo "</table>";
                echo "</div>";

                echo "<h2 class='mt-4'>Bill</h2>";
                echo "<div class='table-responsive'>";
                echo "<table class='table table-bordered'>";
                echo "<tr>";
                echo "<td>Pathology Name:</td>";
                echo "<td>Sym Pathology</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Address:</td>";
                echo "<td>10 Downing Street, London</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Contact:</td>";
                echo "<td>9999988888</td>";
                echo "</tr>";

                $totalBill = 0;
                if (isset($report['BloodTest'])) {
                    $totalBill += $bloodTestPrice;
                } else {
                    echo "<tr>";
                    echo "<td>Blood Test:</td>";
                    echo "<td>X 0</td>";
                    echo "</tr>";
                }
                if (isset($report['UrineTest'])) {
                    $totalBill += $urineTestPrice;
                } else {
                    echo "<tr>";
                    echo "<td>Urine Test:</td>";
                    echo "<td>X 0</td>";
                    echo "</tr>";
                }
                if (isset($report['RadiologyTest'])) {
                    $totalBill += $radiologyTestPrice;
                    echo "<tr>";
                    echo "<td>Radiology Test:</td>";
                    echo "<td>$radiologyTestPrice</td>";
                    echo "</tr>";
                } else {
                    echo "<tr>";
                    echo "<td>Radiology Test:</td>";
                    echo "<td>X 0</td>";
                    echo "</tr>";
                }

                echo "<tr>";
                echo "<td>Total Bill:</td>";
                echo "<td>Rs." . $totalBill . "</td>";
                echo "</tr>";
                echo "</table>";
                echo "</div>";
            } else {
                echo "No data found for the selected appointment ID.";
            }
        }
        ?>
        <?php
        if (isset($_POST["generate_report_and_bill_and_mail"])) {
            $selectedAppointmentID = $_POST["selected_appointment_id"];
            $report = generateReportAndBill($selectedAppointmentID);
        }
        ?>
    </div> <!-- container -->


    <!-- Add Bootstrap JS (Popper.js and Bootstrap.js) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>