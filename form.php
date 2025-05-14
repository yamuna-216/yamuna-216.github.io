<?php
// Include database connection and logger
require_once "db_connect.php";
require_once "logger.php";

$logger = new Logger();

// Initialize variables for form data and error messages
$name = $email = $password = $aadhar = $mobile = $address = "";
$name_err = $email_err = $password_err = $aadhar_err = $mobile_err = $address_err = "";
$is_valid = true;
$success_msg = "";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $logger->info("Form submitted");
    
    // Validate name
    $name = trim($_POST["name"]);
    if (empty($name)) {
        $name_err = "Name is required";
        $is_valid = false;
        $logger->error("Name validation failed: Empty name");
    } elseif (!preg_match("/^[a-zA-Z ]+$/", $name)) {
        $name_err = "Only letters and spaces allowed";
        $is_valid = false;
        $logger->error("Name validation failed: Invalid characters in name");
    }
    
    // Validate email
    $email = trim($_POST["email"]);
    if (empty($email)) {
        $email_err = "Email is required";
        $is_valid = false;
        $logger->error("Email validation failed: Empty email");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_err = "Invalid email format";
        $is_valid = false;
        $logger->error("Email validation failed: Invalid email format");
    } elseif (!preg_match("/@gmail\.com$/", $email)) {
        $email_err = "Only Gmail addresses are allowed";
        $is_valid = false;
        $logger->error("Email validation failed: Not a Gmail address");
    }
    
    // Validate password
    $password = $_POST["password"]; // Don't trim passwords as spaces might be intentional
    if (empty($password)) {
        $password_err = "Password is required";
        $is_valid = false;
        $logger->error("Password validation failed: Empty password");
    } elseif (strlen($password) < 8) {
        $password_err = "Password must be at least 8 characters";
        $is_valid = false;
        $logger->error("Password validation failed: Password length less than 8 characters");
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $password_err = "Password must contain an uppercase letter";
        $is_valid = false;
        $logger->error("Password validation failed: Password does not contain an uppercase letter");
    } elseif (!preg_match("/[a-z]/", $password)) {
        $password_err = "Password must contain a lowercase letter";
        $is_valid = false;
        $logger->error("Password validation failed: Password does not contain a lowercase letter");
    } elseif (!preg_match("/[0-9]/", $password)) {
        $password_err = "Password must contain a number";
        $is_valid = false;
        $logger->error("Password validation failed: Password does not contain a number");
    } elseif (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
        $password_err = "Password must contain a special character";
        $is_valid = false;
        $logger->error("Password validation failed: Password does not contain a special character");
    }
    
    // Validate Aadhar number (must be 12 digits)
    $aadhar = trim($_POST["aadhar"]);
    if (empty($aadhar)) {
        $aadhar_err = "Aadhar number is required";
        $is_valid = false;
        $logger->error("Aadhar validation failed: Empty aadhar");
    } elseif (!preg_match("/^[0-9]{12}$/", $aadhar)) {
        $aadhar_err = "Aadhar must be exactly 12 digits";
        $is_valid = false;
        $logger->error("Aadhar validation failed: Aadhar length not 12 digits");
    }
    
    // Validate mobile number (10 digits, starting with 6-9)
    $mobile = trim($_POST["mobile"]);
    if (empty($mobile)) {
        $mobile_err = "Mobile number is required";
        $is_valid = false;
        $logger->error("Mobile validation failed: Empty mobile");
    } elseif (!preg_match("/^[6-9][0-9]{9}$/", $mobile)) {
        $mobile_err = "Mobile must be 10 digits starting with 6-9";
        $is_valid = false;
        $logger->error("Mobile validation failed: Mobile format not correct");
    }
    
    // Validate address
    $address = trim($_POST["address"]);
    if (empty($address)) {
        $address_err = "Address is required";
        $is_valid = false;
        $logger->error("Address validation failed: Empty address");
    } elseif (strlen($address) < 10) {
        $address_err = "Please enter a complete address (at least 10 characters)";
        $is_valid = false;
        $logger->error("Address validation failed: Address length less than 10 characters");
    }
    
    // Check if email and password are valid (as per your requirement)
    $email_valid = empty($email_err);
    $password_valid = empty($password_err);
    
    // If email and password are valid but other fields are not
    if ($email_valid && $password_valid && !$is_valid) {
        $success_msg = "Email and password are valid. Please complete the remaining fields correctly.";
        $logger->info("Email and password are valid, but other fields are not");
    }
    
    // If all validations pass
    if ($is_valid) {
        $logger->info("All validations passed, attempting to store data");
        
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (name, email, password, aadhar, mobile, address) VALUES (?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $hashed_password, $aadhar, $mobile, $address);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                $logger->info("User registered successfully: " . $email);
                $success_msg = "Registration successful! You can now <a href='users.php'>view all users</a>.";
                // Clear form data after successful submission
                $name = $email = $password = $aadhar = $mobile = $address = "";
            } else {
                $logger->error("Database error: " . mysqli_error($conn));
                $success_msg = "Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        } else {
            $logger->error("Prepare statement error: " . mysqli_error($conn));
            $success_msg = "Something went wrong. Please try again later.";
        }
    } else {
        $logger->info("Form validation failed");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        input, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        textarea {
            height: 80px;
            resize: vertical;
        }
        
        .error {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            width: 100%;
        }
        
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Form Validation</h2>
        
        <?php if (!empty($success_msg)): ?>
            <div class="success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
                <?php if (!empty($name_err)): ?>
                    <span class="error"><?php echo $name_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Email (Gmail only)</label>
                <input type="text" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <?php if (!empty($email_err)): ?>
                    <span class="error"><?php echo $email_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password">
                <?php if (!empty($password_err)): ?>
                    <span class="error"><?php echo $password_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Aadhar Number</label>
                <input type="text" name="aadhar" value="<?php echo htmlspecialchars($aadhar); ?>">
                <?php if (!empty($aadhar_err)): ?>
                    <span class="error"><?php echo $aadhar_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Mobile Number</label>
                <input type="text" name="mobile" value="<?php echo htmlspecialchars($mobile); ?>">
                <?php if (!empty($mobile_err)): ?>
                    <span class="error"><?php echo $mobile_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address"><?php echo htmlspecialchars($address); ?></textarea>
                <?php if (!empty($address_err)): ?>
                    <span class="error"><?php echo $address_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <input type="submit" class="btn" value="Submit">
            </div>
        </form>
    </div>
</body>
</html>
