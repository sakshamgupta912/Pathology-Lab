<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['role'])) {
    header("Location: index.php"); // Redirect to the login page if not logged in
    exit();
}

// Check if the user's role is "admin"
if ($_SESSION['role'] !== 'admin') {
    echo "Access denied. You do not have permission to access this page.";
    exit();
}

// The rest of your "admin.php" code here
// Only users with the "admin" role can access this content
?>

<?php
require 'connection.php';

// Function to add a patient
function addPatient($name, $email, $dob, $gender, $contact, $address)
{
    global $mysqli;
    // Validate the contact number
    if (!preg_match('/^\d{10}$/', $contact)) {
        return "Error: Contact number must be exactly 10 digits.";
    }

    $stmt = $mysqli->prepare("INSERT INTO patient (Name,Email, DOB, Gender, Contact, Address) VALUES (?,?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $dob, $gender, $contact, $address);

    if ($stmt->execute()) {
        return "Patient added successfully!";
    } else {
        return "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Function to make an appointment
function makeAppointment($patientID, $appointmentDate, $appointmentTime, $testType)
{
    global $mysqli;

    // Check if the slot is available before making an appointment
    if (!isSlotAvailable($appointmentDate, $appointmentTime)) {
        return "Error: This appointment slot is already booked.";
    }

    $stmt = $mysqli->prepare("INSERT INTO appointment (PatientID, AppointmentDate, AppointmentTime) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $patientID, $appointmentDate, $appointmentTime);
    
    if ($stmt->execute()) {
        // Get the auto-incremented AppointmentID
        $appointment_id = $mysqli->insert_id;
    
        $testType_query = "INSERT INTO $testType (AppointmentID) VALUES ('$appointment_id')";
        if ($mysqli->query($testType_query) === TRUE) {
            // echo '<script>alert("Data inserted successfully.");</script>';
        } else {
            echo '<script>alert("Error inserting data: ' . $mysqli->error . '");</script>';
        }
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
        $email = $_POST["email"];
        $dob = $_POST["dob"];
        $gender = $_POST["gender"];
        $contact = $_POST["contact"];
        $address = $_POST["address"];

        $result = addPatient($name, $email, $dob, $gender, $contact, $address);

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
        $testType = $_POST["testType"];

        $result = makeAppointment($patientID, $appointmentDate, $appointmentTime, $testType);

        if (strpos($result, "Error:") === 0) {
            echo '<script>alert("' . $result . '");</script>';
        } else {
            echo '<script>alert("Success");</script>';
        }


    }
}

// Function to search patients
function searchPatients($searchQuery)
{
    global $mysqli;

    $searchQuery = "%" . $searchQuery . "%"; // Add wildcards for partial matching
    $stmt = $mysqli->prepare("SELECT * FROM patient WHERE Name LIKE ? OR Email LIKE ?");
    $stmt->bind_param("ss", $searchQuery, $searchQuery);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result;
}

if (isset($_POST["search"])) {
    $searchQuery = $_POST["search_query"];
    $search_results = searchPatients($searchQuery);
}

// Function to update patient details
function updatePatient($patientID, $name, $email, $dob, $gender, $contact, $address)
{
    global $mysqli;

    // Fetch the current patient details
    $stmt = $mysqli->prepare("SELECT Name,Email, DOB, Gender, Contact, Address FROM patient WHERE PatientID = ?");
    $stmt->bind_param("i", $patientID);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($currentName, $currentEmail, $currentDOB, $currentGender, $currentContact, $currentAddress);
    $stmt->fetch();
    $stmt->close();

    // Construct the SQL update query based on the non-empty fields
    $updateQuery = "UPDATE patient SET ";
    $updateParams = array();

    if (!empty($name)) {
        $updateQuery .= "Name = ?, ";
        $updateParams[] = $name;
    }
    if (!empty($email)) {
        $updateQuery .= "Email = ?, ";
        $updateParams[] = $email;
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
    $editEmail = $_POST["edit_email"];
    $editDOB = $_POST["edit_dob"];
    $editGender = $_POST["edit_gender"];
    $editContact = $_POST["edit_contact"];
    $editAddress = $_POST["edit_address"];

    $result = updatePatient($editPatientID, $editName, $editEmail, $editDOB, $editGender, $editContact, $editAddress);

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

// Function to delete a appointments
if (isset($_POST["delete_appointment"])) {
    $deleteAppointmentID = $_POST["delete_appointment_id"];
    
    // Delete test data first
    $result = deleteTestData($deleteAppointmentID);

    if (strpos($result, "Error:") === 0) {
        echo '<script>alert("' . $result . '");</script>';
    } else {
        // If test data is deleted successfully, proceed to delete the appointment
        $deleteResult = deleteAppointment($deleteAppointmentID);

        if (strpos($deleteResult, "Error:") === 0) {
            echo '<script>alert("' . $deleteResult . '");</script>';
        } else {
            echo '<script>alert("Success");</script>';
        }
    }
}

function deleteTestData($appointmentID) {
    global $mysqli;
    $testType = "bloodtest";  // Update this based on the test type
    
    $deleteTestData = $mysqli->prepare("DELETE FROM $testType WHERE AppointmentID = ?");
    $deleteTestData->bind_param("i", $appointmentID);

    if (!$deleteTestData->execute()) {
        return "Error: " . $deleteTestData->error;
    }

    return "Test data for Appointment ID $appointmentID deleted successfully!";
}


// Function to delete an appointment by AppointmentID
function deleteAppointment($appointmentID)
{
    global $mysqli;

    // Check if the appointment exists
    $stmt = $mysqli->prepare("SELECT AppointmentID FROM appointment WHERE AppointmentID = ?");
    $stmt->bind_param("i", $appointmentID);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        return "Error: Appointment with ID $appointmentID does not exist.";
    }

    // Delete the appointment
    $deleteAppointment = $mysqli->prepare("DELETE FROM appointment WHERE AppointmentID = ?");
    $deleteAppointment->bind_param("i", $appointmentID);
    if (!$deleteAppointment->execute()) {
        return "Error: " . $deleteAppointment->error;
    }

    return "Appointment with ID $appointmentID deleted successfully!";
}



if (isset($_POST["delete_patient"])) {
    $deletePatientID = $_POST["delete_patient_id"];
    $result = deletePatient($deletePatientID);

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
    <!-- Add Bootstrap CSS Link -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

</head>

<body>
    <div class="container ">
        <h1 style='text-align:center'>Admin Dashboard</h1>
        <h2>Appointments for a Date</h2>
        <form method="post">
            <input type="date" name="selected_date" required>
            <input type="submit" name="display_appointments" value="Display Appointments">
        </form>
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            if (isset($_POST["display_appointments"])) {
                $selectedDate = $_POST["selected_date"];

                // Query to fetch appointments for the selected date from the SQL view
                $query = "SELECT * FROM appointments_for_date WHERE AppointmentDate = ?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param("s", $selectedDate);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {

                    echo '<h5 class="mt-3">Appointments for ' . $selectedDate . '</h5>';
                    echo '</div>'; // Close the previous container

                    echo '<div class="container mt-3">';
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-striped">';
                    echo '<thead class="thead-dark">';
                    echo '<tr><th>Appointment ID</th><th>Patient Name</th><th>Appointment Date</th><th>Appointment Time</th></tr>';
                    echo '</thead><tbody>';

                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row['AppointmentID'] . '</td>';
                        echo '<td>' . $row['Name'] . '</td>';
                        echo '<td>' . $row['AppointmentDate'] . '</td>';
                        echo '<td>' . $row['AppointmentTime'] . '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                    echo '</div>'; // .table-responsive

                } else {

                    echo 'No appointments found for ' . $selectedDate;
                }

                $stmt->close();
            }
        }
        ?>
        <hr>
        <h2>Add Patient</h2>
        <form method="post">
            <div class="form-group">
                <input type="text" name="name" placeholder="Name" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="date" name="dob" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender:</label>
                <select name="gender" id="gender" class="form-control" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <input type="text" name="contact" placeholder="Contact" class="form-control" required pattern="[0-9]{10}" title="Contact number must be exactly 10 digits.">
            </div>
            <div class="form-group">
                <textarea name="address" placeholder="Address" class="form-control" required></textarea>
            </div>
            <button type="submit" name="add_patient" class="btn btn-primary">Add Patient</button>
            <?php
            if ($error_message) {
                echo '<div style="color: red;">' . $error_message . '</div>';
            }
            ?>
        </form>
        <hr>
        <h2>Make Appointment</h2>
        <form method="post">
            <div class="form-group">
                <select name="patient_id" class="form-control" required>
                    <!-- Display a list of patients for selection -->
                    <?php
                    $result = $mysqli->query("SELECT PatientID, Name FROM patient");
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['PatientID'] . "'>" . $row['PatientID'] . " " . $row['Name'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <input type="date" name="appointment_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <select name="appointment_time" class="form-control" required>
                    <?php
                    foreach ($fixedAppointmentTimes as $time) {
                        echo "<option value='$time'>$time</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="testType">Select Test Type:</label>
                <select name="testType" class="form-control" required>
                    <option value="bloodtest">Blood Test</option>
                    <option value="urinetest">Urine Test</option>
                    <option value="radiologytest">Radiology Test</option>
                </select>
            </div>
            <button type="submit" name="make_appointment" class="btn btn-primary">Make Appointment</button>
        </form>
        <hr>
        <h2>Edit Patient Details</h2>
        <form method="post">
            <div class="form-group">
                <select name="edit_patient_id" class="form-control" required>
                    <!-- Display a list of patients for selection -->
                    <?php
                    $result = $mysqli->query("SELECT PatientID, Name FROM patient");
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['PatientID'] . "'>" . $row['PatientID'] . " " . $row['Name'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <input type="text" name="edit_name" placeholder="Name" class="form-control">
            </div>
            <div class="form-group">
                <input type="email" name="edit_email" placeholder="Email" class="form-control">
            </div>
            <div class="form-group">
                <input type="date" name="edit_dob" class="form-control">
            </div>
            <div class="form-group">
                <select name="edit_gender" class="form-control">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <input type="text" name="edit_contact" placeholder="Contact" class="form-control">
            </div>
            <div class="form-group">
                <textarea name="edit_address" placeholder="Address" class="form-control"></textarea>
            </div>
            <button type="submit" name="update_patient" class="btn btn-primary">Update Patient Details</button>
        </form>
        <hr>
        <!-- HTML Form for Editing Appointment -->
        <h2>Edit Appointment</h2>
        <form method="post">
            <div class="form-group">
                <select name="edit_appointment_id" class="form-control" required>
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
                </select>
            </div>
            <div class="form-group">
                <input type="date" name="new_appointment_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <select name="new_appointment_time" class="form-control" required>
                    <?php
                    foreach ($fixedAppointmentTimes as $time) {
                        echo "<option value='$time'>$time</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" name="edit_appointment" class="btn btn-primary">Edit Appointment</button>
        </form>
        <hr>
        <h2>Delete Appointment</h2>
<form method="post">
    <div class="form-group">
        <select name="delete_appointment_id" class="form-control" required>
            <?php
            // Fetch the list of appointments
            $result = $mysqli->query("SELECT appointment.AppointmentID, appointment.PatientID, appointment.AppointmentDate, appointment.AppointmentTime, patient.Name FROM appointment INNER JOIN patient ON appointment.PatientID = patient.PatientID");
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['AppointmentID'] . "'>" . "Appointment ID: " . $row['AppointmentID'] . " - Patient Name: " . $row['Name'] . " - Date: " . $row['AppointmentDate'] . " - Time: " . $row['AppointmentTime'] . "</option>";
            }
            ?>
        </select>
    </div>
    <button type="submit" name="delete_appointment" class="btn btn-danger">Delete Appointment</button>
</form>

        <hr>
        <h2>Search</h2>
        <form method="post">
            <div class="input-group mb-3">
                <input type="text" name="search_query" class="form-control" placeholder="Search for patients by name or email" aria-label="Search" aria-describedby="search-button">
                <div class="input-group-append">
                    <button type="submit" name="search" class="btn btn-primary" id="search-button">Search</button>
                </div>
            </div>
        </form>

        <?php if (isset($_POST["search"])) : ?>
            <h2>Search Results</h2>

            <?php if ($search_results->num_rows > 0) : ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Patient ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>DOB</th>
                                <th>Gender</th>
                                <th>Contact</th>
                                <th>Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $search_results->fetch_assoc()) : ?>
                                <tr>
                                    <td><?= $row['PatientID'] ?></td>
                                    <td><?= $row['Name'] ?></td>
                                    <td><?= $row['Email'] ?></td>
                                    <td><?= $row['DOB'] ?></td>
                                    <td><?= $row['Gender'] ?></td>
                                    <td><?= $row['Contact'] ?></td>
                                    <td><?= $row['Address'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p>No patient records found.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Add Bootstrap JavaScript and jQuery If Needed -->
    <!-- <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> -->
</body>

</html>
