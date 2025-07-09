<?php
include 'cors.php'; // ✅ Enforces session validation
// include 'secure.php';
include 'connect_db.php';


// include 'post.php'; // for post services 

// Function to validate and sanitize input
function validateInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate date format
function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Function to validate phone number
function validatePhone($phone)
{
    return preg_match('/^[\d\-\+\(\)\s]+$/', $phone);
}

// Function to validate zip code
function validateZipCode($zip)
{
    return preg_match('/^\d{5}(-\d{4})?$/', $zip);
}

// Function to validate state
function validateState($state)
{
    $validStates = [
        'AL',
        'AK',
        'AZ',
        'AR',
        'CA',
        'CO',
        'CT',
        'DE',
        'FL',
        'GA',
        'HI',
        'ID',
        'IL',
        'IN',
        'IA',
        'KS',
        'KY',
        'LA',
        'ME',
        'MD',
        'MA',
        'MI',
        'MN',
        'MS',
        'MO',
        'MT',
        'NE',
        'NV',
        'NH',
        'NJ',
        'NM',
        'NY',
        'NC',
        'ND',
        'OH',
        'OK',
        'OR',
        'PA',
        'RI',
        'SC',
        'SD',
        'TN',
        'TX',
        'UT',
        'VT',
        'VA',
        'WA',
        'WV',
        'WI',
        'WY'
    ];
    return in_array(strtoupper($state), $validStates);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new investor
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
        exit;
    }

    // Validate required fields
    $requiredFields = ['first_name', 'last_name', 'date_of_birth', 'phone_number', 'street_address', 'state', 'zip_code'];
    $errors = [];

    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            $errors[] = "Field '$field' is required";
        }
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Validation errors', 'errors' => $errors]);
        exit;
    }

    // Validate specific fields
    if (!validateDate($input['date_of_birth'])) {
        $errors[] = 'Invalid date format. Use YYYY-MM-DD';
    }

    if (!validatePhone($input['phone_number'])) {
        $errors[] = 'Invalid phone number format';
    }

    if (!validateState($input['state'])) {
        $errors[] = 'Invalid state code';
    }

    if (!validateZipCode($input['zip_code'])) {
        $errors[] = 'Invalid zip code format';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Validation errors', 'errors' => $errors]);
        exit;
    }

    // Sanitize inputs
    $first_name = validateInput($input['first_name']);
    $last_name = validateInput($input['last_name']);
    $date_of_birth = $input['date_of_birth'];
    $phone_number = validateInput($input['phone_number']);
    $street_address = validateInput($input['street_address']);
    $state = strtoupper(validateInput($input['state']));
    $zip_code = validateInput($input['zip_code']);

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO investors (first_name, last_name, date_of_birth, phone_number, street_address, state, zip_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $first_name, $last_name, $date_of_birth, $phone_number, $street_address, $state, $zip_code);

    if ($stmt->execute()) {
        $investor_id = $conn->insert_id;
        echo json_encode([
            'status' => 'success',
            'message' => 'Investor created successfully',
            'investor_id' => $investor_id,
            'data' => [
                'id' => $investor_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'date_of_birth' => $date_of_birth,
                'phone_number' => $phone_number,
                'street_address' => $street_address,
                'state' => $state,
                'zip_code' => $zip_code
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create investor']);
    }

    $stmt->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Retrieve all investors
    $query = "SELECT id, first_name, last_name, date_of_birth, phone_number, street_address, state, zip_code, created_at FROM investors ORDER BY created_at DESC";
    $result = $conn->query($query);

    if ($result) {
        $investors = [];
        while ($row = $result->fetch_assoc()) {
            $investors[] = $row;
        }
        echo json_encode([
            'status' => 'success',
            'data' => $investors
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve investors']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}

$conn->close();
?>