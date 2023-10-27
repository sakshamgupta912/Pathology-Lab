<?php
require 'connection.php';

// Function to add a patient
function addPatient($name, $dob, $gender, $contact, $address)
{
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
function makeAppointment($patientID, $appointmentDate, $appointmentTime)
{
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
function isSlotAvailable($appointmentDate, $appointmentTime)
{
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
        } else {
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
        } else {
            echo '<script>alert("Success");</script>';
        }
    }
}

// Function to update patient details
function updatePatient($patientID, $name, $dob, $gender, $contact, $address)
{
    global $mysqli;

    // Fetch the current patient details
    $stmt = $mysqli->prepare("SELECT Name, DOB, Gender, Contact, Address FROM patient WHERE PatientID = ?");
    $stmt->bind_param("i", $patientID);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($currentName, $currentDOB, $currentGender, $currentContact, $currentAddress);
    $stmt->fetch();
    $stmt->close();

    // Construct the SQL update query based on the non-empty fields
    $updateQuery = "UPDATE patient SET ";
    $updateParams = array();

    if (!empty($name)) {
        $updateQuery .= "Name = ?, ";
        $updateParams[] = $name;
    }
    if (!empty($dob)) {
        $updateQuery .= "DOB = ?, ";
        $updateParams[] = $dob;
    }
    if (!empty($gender)) {
        $updateQuery .= "Gender = ?, ";
        $updateParams[] = $gender;
    }
    if (!empty($contact)) {
        $updateQuery .= "Contact = ?, ";
        $updateParams[] = $contact;
    }
    if (!empty($address)) {
        $updateQuery .= "Address = ?, ";
        $updateParams[] = $address;
    }

    // Remove the trailing comma and space
    $updateQuery = rtrim($updateQuery, ", ");

    // Append the WHERE clause to specify the patient
    $updateQuery .= " WHERE PatientID = ?";

    // Bind the updated parameters to the query
    $stmt = $mysqli->prepare($updateQuery);
    $bindTypes = str_repeat('s', count($updateParams)) . 'i';
    // Construct the bind_param dynamically
    $bindParams = array_merge(array($bindTypes), $updateParams, array($patientID));

    $bindParamsReferences = array();
    foreach ($bindParams as $key => $value) {
        $bindParamsReferences[$key] = &$bindParams[$key];
    }

    call_user_func_array(array($stmt, 'bind_param'), $bindParamsReferences);


    if ($stmt->execute()) {
        return "Patient details updated successfully!";
    } else {
        return "Error: " . $stmt->error;
    }
    $stmt->close();
}

if (isset($_POST["update_patient"])) {
    $editPatientID = $_POST["edit_patient_id"];
    $editName = $_POST["edit_name"];
    $editDOB = $_POST["edit_dob"];
    $editGender = $_POST["edit_gender"];
    $editContact = $_POST["edit_contact"];
    $editAddress = $_POST["edit_address"];

    $result = updatePatient($editPatientID, $editName, $editDOB, $editGender, $editContact, $editAddress);

    if (strpos($result, "Error:") === 0) {
        echo '<script>alert("' . $result . '");</script>';
    } else {
        echo '<script>alert("Success");</script>';
    }
}

// Function to edit an appointment
function editAppointment($appointmentID, $newAppointmentDate, $newAppointmentTime)
{
    global $mysqli;

    // Check if the new slot is available before making the edit
    if (!isSlotAvailable($newAppointmentDate, $newAppointmentTime)) {
        return "Error: The new appointment slot is already booked.";
    }

    $stmt = $mysqli->prepare("UPDATE appointment SET AppointmentDate = ?, AppointmentTime = ? WHERE AppointmentID = ?");
    $stmt->bind_param("ssi", $newAppointmentDate, $newAppointmentTime, $appointmentID);

    if ($stmt->execute()) {
        return "Appointment edited successfully!";
    } else {
        return "Error: " . $stmt->error;
    }
    $stmt->close();
}

if (isset($_POST["edit_appointment"])) {
    $appointmentID = $_POST["edit_appointment_id"];
    $newAppointmentDate = $_POST["new_appointment_date"];
    $newAppointmentTime = $_POST["new_appointment_time"];

    $result = editAppointment($appointmentID, $newAppointmentDate, $newAppointmentTime);

    if (strpos($result, "Error:") === 0) {
        echo '<script>alert("' . $result . '");</script>';
    } else {
        echo '<script>alert("Success");</script>';
    }
}



?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin Panel</title>
    <style>
        select[name="edit_appointment_id"] {
            width: 300px;
            /* Adjust the width as needed */
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        /* Style the dropdown options */
        select[name="edit_appointment_id"] option {
            padding: 5px;
            font-size: 16px;
            background-color: #fff;
            color: #333;
        }
    </style>
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

    <h1>Edit Patient Details</h1>
    <form method="post">
        <select name="edit_patient_id" required>
            <!-- Display a list of patients for selection -->
            <?php
            $result = $mysqli->query("SELECT PatientID, Name FROM patient");
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['PatientID'] . "'>" . $row['PatientID'] . " " . $row['Name'] . "</option>";
            }
            ?>
        </select><br>
        <input type="text" name="edit_name" placeholder="Name">
        <input type="date" name="edit_dob" placeholder="Date of Birth">
        <select name="edit_gender">
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>
        <input type="text" name="edit_contact" placeholder="Contact">
        <textarea name="edit_address" placeholder="Address"></textarea>
        <input type="submit" name="update_patient" value="Update Patient Details">
    </form>

    <!-- HTML Form for Editing Appointment -->
    <h1>Edit Appointment</h1>
    <form method="post">
        <select name="edit_appointment_id" required>
            <!-- Display a list of appointments for selection -->
            <?php
            $sql = "SELECT appointment.AppointmentID, appointment.PatientID, appointment.AppointmentDate, appointment.AppointmentTime, patient.Name FROM appointment INNER JOIN patient ON appointment.PatientID = patient.PatientID";
            $result = $mysqli->query($sql);

            while ($row = $result->fetch_assoc()) {
                $appointmentID = $row['AppointmentID'];
                $patientID = $row['PatientID'];
                $name = $row['Name'];
                $date = $row['AppointmentDate'];
                $time = $row['AppointmentTime'];

                $displayText = "Appointment ID: $appointmentID - Patient ID: $patientID - Patient Name: $name - Date: $date - Time: $time";

                echo "<option value='$appointmentID'>$displayText</option>";
            }
            ?>
        </select><br>

        <input type="date" name="new_appointment_date" required min="<?php echo date('Y-m-d'); ?>"><br>
        <select name="new_appointment_time" required>
            <?php
            foreach ($fixedAppointmentTimes as $time) {
                echo "<option value='$time'>$time</option>";
            }
            ?>
        </select><br>
        <input type="submit" name="edit_appointment" value="Edit Appointment">
    </form>


</body>

</html>