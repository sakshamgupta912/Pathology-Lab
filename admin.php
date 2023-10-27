<?php
require 'connection.php';

// Function to add a patient
function addPatient($name, $dob, $gender, $contact, $address) {
    global $mysqli;

    // Validate the contact number
    if (!preg_match('/^\d{10}$/', $contact)) {
        return "Error: Contact number must be exactly 10 digits.";
    }

    $stmt = $mysqli->prepare("INSERT INTO patient (Name, DOB, Gender, Contact, Address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $dob, $gender, $contact, $address);

    if ($stmt->execute()) {
        return "Patient added successfully!";
    } else {
        return "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Function to make an appointment
function makeAppointment($patientID, $appointmentDate, $appointmentTime) {
    global $mysqli;

    // Check if the slot is available before making an appointment
    if (!isSlotAvailable($appointmentDate, $appointmentTime)) {
        return "Error: This appointment slot is already booked.";
    }

    $stmt = $mysqli->prepare("INSERT INTO appointment (PatientID, AppointmentDate, AppointmentTime) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $patientID, $appointmentDate, $appointmentTime);

    if ($stmt->execute()) {
        return "Appointment made successfully!";
    } else {
        return "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Function to check if an appointment slot is available
function isSlotAvailable($appointmentDate, $appointmentTime) {
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT AppointmentID FROM appointment WHERE AppointmentDate = ? AND AppointmentTime = ?");
    $stmt->bind_param("ss", $appointmentDate, $appointmentTime);
    $stmt->execute();
    $stmt->store_result();

    return $stmt->num_rows === 0;
}

$fixedAppointmentTimes = [
    "10:00:00",
    "11:00:00",
    "12:00:00",
    "13:00:00",
    "14:00:00",
    "15:00:00",
    "16:00:00",
];

// Initialize the error message
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["add_patient"])) {
        $name = $_POST["name"];
        $dob = $_POST["dob"];
        $gender = $_POST["gender"];
        $contact = $_POST["contact"];
        $address = $_POST["address"];

        $result = addPatient($name, $dob, $gender, $contact, $address);

        // Check for errors and display error message
        if (strpos($result, "Error:") === 0) {
            echo '<script>alert("' . $result . '");</script>';
        }
        else{
            echo '<script>alert("Success");</script>';
        }
    } elseif (isset($_POST["make_appointment"])) {
        $error_message = "";

        $patientID = $_POST["patient_id"];
        $appointmentDate = $_POST["appointment_date"];
        $appointmentTime = $_POST["appointment_time"];

        $result = makeAppointment($patientID, $appointmentDate, $appointmentTime);

        if (strpos($result, "Error:") === 0) {
            echo '<script>alert("' . $result . '");</script>';
        }
        else{
            echo '<script>alert("Success");</script>';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
</head>
<body>
    <h1>Add Patient</h1>
    <form method="post">
        <input type="text" name="name" placeholder="Name" required><br>
        <input type="date" name="dob" required><br>
        <label for="gender">Gender:</label>
        <select name="gender" id="gender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select><br>
        <input type="text" name="contact" placeholder="Contact" required pattern="[0-9]{10}" title="Contact number must be exactly 10 digits."><br>
        <textarea name="address" placeholder="Address" required></textarea><br>
        <input type="submit" name="add_patient" value="Add Patient">
        <?php
        if ($error_message) {
            echo '<div style="color: red;">' . $error_message . '</div>';
        }
        ?>
    </form>

    <h1>Make Appointment</h1>
    <form method="post">
        <select name="patient_id" required>
            <!-- Display a list of patients for selection -->
            <?php
            $result = $mysqli->query("SELECT PatientID, Name FROM patient");
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['PatientID'] . "'>" . $row['PatientID'] . " " . $row['Name'] . "</option>";
            }
            ?>
        </select><br>
        <input type="date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>"><br>
        <select name="appointment_time" required>
            <?php
            foreach ($fixedAppointmentTimes as $time) {
                echo "<option value='$time'>$time</option>";
            }
            ?>
        </select><br>
        <input type="submit" name="make_appointment" value="Make Appointment">
      
    </form>
</body>
</html>
