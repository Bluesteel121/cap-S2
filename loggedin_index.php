<!DOCTYPE html>
<html>
<head>
	<title>Library Management System</title>
	<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css">
	<script type="text/javascript" src="bootstrap/js/jquery-3.4.1.min.js"></script>
	<script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>
	<style type="text/css">
		.jumbotron{
			background-color: lightgray;
		}
		.nav-link:hover{
			background-color: silver;
			color: black;
			border-radius: 5px;
		}

        /* Added styles for the profile picture and sidebar */
        .profile-pic-container {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            cursor: pointer;
            margin-right: 10px;
        }

        .profile-pic {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            z-index: 1;
            top: 0;
            left: 0;
            background-color: #f1f1f1;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 60px;
        }

        .sidebar a {
            padding: 8px 8px 8px 32px;
            text-decoration: none;
            font-size: 25px;
            color: #818181;
            display: block;
            transition: 0.3s;
        }

        .sidebar a:hover {
            color: #f1f1f1;
        }

        .sidebar .closebtn {
            position: absolute;
            top: 0;
            right: 25px;
            font-size: 36px;
            margin-left: 50px;
        }

	</style>

</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
		<div class="container-fluid">
			<div class="navbar-header">
				<a class="navbar-brand" href="loggedin_index.php">Library Management System</a>
			</div>
			<ul class="nav navbar-nav navbar-right">
                <li class="nav-item">
                    <div class="profile-pic-container" onclick="toggleSidebar()">
                        <!-- Replace with the actual user's initial profile picture -->
                        <img src="Images/initials profile/a.png" alt="Profile Picture" class="profile-pic">
                    </div>
                </li>
			</ul>
		</div>
	</nav>

    <div id="mySidebar" class="sidebar">
        <a href="javascript:void(0)" class="closebtn" onclick="closeSidebar()">&times;</a>
        <a href="#">Settings</a>
        <a href="#">Profile</a>
        <a href="index.php">Log Out</a>
    </div>

	<div class="row">
		<div class="col-md-4" id="side_bar">
			<div class="jumbotron">
				<h1 class="text-center">Librarian Login</h1>
				<br>
				<form action="" method="post">
					<div class="form-group">
						<label>Email ID:</label>
						<input type="text" name="email" class="form-control" required="">
					</div>
					<div class="form-group">
						<label>Password:</label>
						<input type="password" name="password" class="form-control" required="">
					</div>
					<button type="submit" name="login" class="btn btn-primary">Login</button>
				</form>
				<?php
				if (isset($_POST['login'])) {
					$servername = "localhost";
					$username = "root";
					$password = "";
					$dbname = "elibrary";

					$connection = mysqli_connect($servername, $username, $password, $dbname);

					if (!$connection) {
						die("Connection failed: " . mysqli_connect_error());
					}

					$query = "SELECT * FROM admins WHERE email = '$_POST[email]' AND password = '$_POST[password]'";
					$query_run = mysqli_query($connection, $query);

					if (mysqli_num_rows($query_run) > 0) {
						$_SESSION['email'] = $_POST['email'];
						while ($row = mysqli_fetch_assoc($query_run)) {
							$_SESSION['name'] = $row['name'];
						}
						header("Location: admin_dashboard.php");
					} else {
						echo "<script>alert('Please enter correct email and password');</script>";
					}
					mysqli_close($connection);
				}
				?>
			</div>
		</div>
		<div class="col-md-8" id="main_content">
			<div id="carouselExampleInterval" class="carousel slide" data-ride="carousel">
				<div class="carousel-inner">
					<div class="carousel-item active" data-interval="2000">
						<img src="Images/Da.png" class="d-block w-100" alt="...">
					</div>
					<div class="carousel-item" data-interval="2000">
						<img src="Images/Bago.png" class="d-block w-100" alt="...">
					</div>
					<div class="carousel-item" data-interval="2000">
						<img src="Images/Ph.png" class="d-block w-100" alt="...">
					</div>
				</div>
				<a class="carousel-control-prev" href="#carouselExampleInterval" role="button" data-slide="prev">
					<span class="carousel-control-prev-icon" aria-hidden="true"></span>
					<span class="sr-only">Previous</span>
				</a>
				<a class="carousel-control-next" href="#carouselExampleInterval" role="button" data-slide="next">
					<span class="carousel-control-next-icon" aria-hidden="true"></span>
					<span class="sr-only">Next</span>
				</a>
			</div>
		</div>
	</div>

    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById("mySidebar");
            if (sidebar.style.width === "250px") {
                sidebar.style.width = "0";
            } else {
                sidebar.style.width = "250px";
            }
        }

        function closeSidebar() {
            document.getElementById("mySidebar").style.width = "0";
        }
    </script>

</body>
</html>