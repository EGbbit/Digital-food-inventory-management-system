<?php
// index.php - FoodFlow Landing Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodFlow | Digital Food Inventory Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f8f9fa;
            color: #333;
        }
        
        .navbar {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .navbar-links a {
            color: white;
            margin-left: 1.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }
        
        .navbar-links a:hover {
            opacity: 0.8;
        }
        
        .hero {
            text-align: center;
            padding: 4rem 1.5rem;
            background: white;
            margin: 2rem auto;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            max-width: 1000px;
        }
        
        .hero h1 {
            color: #4facfe;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .hero p {
            color: #555;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 12px 28px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
        }
        
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">FoodFlow</div>
        <div class="navbar-links">
            <a href="auth/login.php">Login</a>
        </div>
    </nav>

    <section class="hero">
        <h1>Welcome to FoodFlow</h1>
        <p>Track stock in real time, reduce wastage, and keep kitchen operations coordinated across admin, waiter, chef, and manager roles.</p>
        <a href="auth/login.php" class="btn-primary">Login to Continue</a>
    </section>

</body>
</html>
