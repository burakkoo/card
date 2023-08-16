<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Include the database connection file
require_once 'dbconfig.php';

// Check if the delete button is clicked
if (isset($_POST['delete_listing'])) {
    $listingId = $_POST['listing_id'];
    $username = $_SESSION['username'];

    // Check if the listing belongs to the current user
    $checkQuery = "SELECT * FROM listings WHERE id = ? AND username = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("is", $listingId, $username);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult && $checkResult->num_rows > 0) {
        // Get the user-uploaded image path
        $imageQuery = "SELECT image_path FROM listings WHERE id = ?";
        $imageStmt = $conn->prepare($imageQuery);
        $imageStmt->bind_param("i", $listingId);
        $imageStmt->execute();
        $imageResult = $imageStmt->get_result();

        if ($imageResult && $imageResult->num_rows > 0) {
            $row = $imageResult->fetch_assoc();
            $userUploadedImagePath = $row['image_path'];

            // Delete the user-uploaded image file from the server
            if (!empty($userUploadedImagePath) && file_exists($userUploadedImagePath)) {
                unlink($userUploadedImagePath);
            }
        }

        // Delete the listing from the database
        $deleteQuery = "DELETE FROM listings WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $listingId);
        $stmt->execute();
        $stmt->close();
    }

    $checkStmt->close();
    $imageStmt->close();

    // Redirect to the my_listings.php page
    header("Location: my_listings.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Card Trading - My Listings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/css/bootstrap.min.css">
    <link rel="stylesheet" href="listings.css">
    <style>
        /* Global Styles */
        body {
            font-family: 'Roboto', sans-serif;
        }

        .container {
            margin-top: 50px;
        }
        

        .footer {
            background-color: #f8f9fa;
            padding: 20px 0;
            text-align: center;
        }

        .clickable-row {
            cursor: pointer;
        }

        .trade-for-images {
            display: flex;
            flex-wrap: nowrap;
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
        }

        .trade-for-images img {
            max-width: 100px;
            max-height: 100px;
        }

        @media (max-width: 576px) {
            .container {
                margin-top: 20px;
            }

            .trade-card-divs {
                width: 33.33%;
                display: flex;
                flex-wrap: wrap;
            }

            .modal-dialog {
            }

            .footer {
                padding: 10px 0;
            }
        }

        /* Additional Styles for Quantity Display */
        .trade-card-div {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .quantity-badge {
            background-color: #007bff;
            color: #fff;
            font-size: 14px;
            padding: 4px 8px;
            border-radius: 20px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
<header class="header">
    <nav class="navbar navbar-expand-md navbar-light bg-light">
        <a class="navbar-brand" href="#">Card Trading</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_listings.php">My Listings</a>
                </li>
            </ul>
            <span class="navbar-text mr-3 welcome-text">Welcome, <?php echo $_SESSION['username']; ?></span>
            <a href="logout.php" class="btn btn-primary">Logout</a>
        </div>
    </nav>
</header>
<div class="container">
    <h2 class="mb-4">Listings</h2>
    <div class="table-responsive table-sm">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th class="d-none d-md-table-cell">Listing ID</th>
                    <th>Price</th>
                    <th>Trade For</th>
                    <th class="d-none d-md-table-cell">Created Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Include the dbconfig.php file
                include('dbconfig.php');

                // Check if the user is logged in and retrieve the user ID
                // Modify this part based on your authentication mechanism
                // Replace with your logic to get the logged-in user ID

                // Query to retrieve data from the listings table for the logged-in user
                $username = $_SESSION['username'];
                $query = "SELECT * FROM listings WHERE username = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                // Loop through the query results and display data in table rows
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $cardNumber = $row['card_number'];

                        // Fetch edition from the cards table using cardNumber
                        $editionQuery = "SELECT edition FROM cards WHERE cardnr = ?";
                        $editionStmt = $conn->prepare($editionQuery);
                        $editionStmt->bind_param("s", $cardNumber);
                        $editionStmt->execute();
                        $editionResult = $editionStmt->get_result();

                        if ($editionResult && $editionResult->num_rows > 0) {
                            $editionRow = $editionResult->fetch_assoc();
                            $edition = $editionRow['edition'];
                            $image1 = "card_images/{$edition}/{$cardNumber}.png";
                        } else {
                            // If the edition is not found, use a default path or display an error image
                            $image1 = "path_to_default_image.png";
                        }

                        // Get trade_for from the listing
                        $tradeFor = $row['trade_for'];
                        $tradeForDisplay = '';
                        $tradeForDisplay2 = ''; // Initialize $tradeForDisplay2 before the loop

                        // Split the trade_for value to get individual card numbers
                        $tradeForArr = explode(',', $tradeFor);
                        foreach ($tradeForArr as $tradeCard) {
                            $tradeCardData = explode('x', trim($tradeCard));
                            if (count($tradeCardData) === 2) {
                                list($amount, $tradeCardNr) = $tradeCardData;
                        
                                // Fetch the cardname and edition based on the tradeCardNr from the cards table
                                $cardDetailsQuery = "SELECT cardname, edition FROM cards WHERE cardnr = ?";
                                $cardDetailsStmt = $conn->prepare($cardDetailsQuery);
                                $cardDetailsStmt->bind_param("s", $tradeCardNr);
                                $cardDetailsStmt->execute();
                                $cardDetailsResult = $cardDetailsStmt->get_result();
                        
                                if ($cardDetailsResult && $cardDetailsResult->num_rows > 0) {
                                    $cardDetailsRow = $cardDetailsResult->fetch_assoc();
                                    $cardname = $cardDetailsRow['cardname'];
                                    $edition = $cardDetailsRow['edition'];
                                    $tradeForDisplay .= "{$amount}x {$cardname} ({$tradeCardNr}) [{$edition}], ";
                                    $tradeForDisplay2 .= "{$amount}x {$cardname} ({$tradeCardNr}), ";
                                } else {
                                    // If the cardname is not found, use a default value or handle the situation accordingly
                                    $tradeForDisplay .= "{$amount}x (Unknown Card) ({$tradeCardNr}), ";
                                    $tradeForDisplay2 .= "{$amount}x (Unknown Card) ({$tradeCardNr}), ";

                                }
                        
                                $cardDetailsStmt->close();
                            } else {
                                // If the trade_for value does not have the 'x' format, consider it as just the card number
                                $tradeCardNr = trim($tradeCard);
                        
                                // Fetch the cardname and edition based on the tradeCardNr from the cards table
                                $cardDetailsQuery = "SELECT cardname, edition FROM cards WHERE cardnr = ?";
                                $cardDetailsStmt = $conn->prepare($cardDetailsQuery);
                                $cardDetailsStmt->bind_param("s", $tradeCardNr);
                                $cardDetailsStmt->execute();
                                $cardDetailsResult = $cardDetailsStmt->get_result();
                        
                                if ($cardDetailsResult && $cardDetailsResult->num_rows > 0) {
                                    $cardDetailsRow = $cardDetailsResult->fetch_assoc();
                                    $cardname = $cardDetailsRow['cardname'];
                                    $edition = $cardDetailsRow['edition'];
                                    $tradeForDisplay .= "1x {$cardname} ({$tradeCardNr}) [{$edition}], ";
                                    $tradeForDisplay2 .= "1x {$cardname} ({$tradeCardNr}), ";
                                } else {
                                    // If the cardname is not found, use a default value or handle the situation accordingly
                                    $tradeForDisplay .= "1x (Unknown Card) ({$tradeCardNr}), ";
                                    $tradeForDisplay2 .= "1x (Unknown Card) ({$tradeCardNr}), ";
                                }
                        
                                $cardDetailsStmt->close();
                            }
                        }

                        // Remove the trailing comma and space from tradeForDisplay
                        $tradeForDisplay = rtrim($tradeForDisplay, ', ');
                        $tradeForDisplay2 = rtrim($tradeForDisplay2, ', ');

                        // Remove the [Edition ...] part from the tradeForDisplay using preg_replace
                        //$tradeForDisplay = preg_replace('/\s*\[.*?\]\s*/', '', $tradeForDisplay);


                        // Get other listing details
                        echo "<tr class='clickable-row' data-toggle='modal' data-target='#listingModal' data-listing-id='" . $row['id'] . "' data-card-number='" . $cardNumber . "' data-image1='{$image1}' data-image2='{$row['image_path']}' data-trade-for-images='" . $tradeForDisplay . "'>";
                        echo "<td class='card-price' style='display:none;'>{$row['price']}</td>"; // Hidden row for fetching the card price
                        echo "<td class='created-date' style='display:none;'>{$row['date_created']}</td>"; // Hidden row for fetching the created date
                        echo "<td class='card-amount' style='display:none;'>{$row['amount']}</td>"; // Hidden row for fetching the created date
                        echo '<td class="d-none d-md-table-cell">' . $row['id'] . '</td>';
                        echo "<td>" . $row['price'] . "</td>";
                        echo "<td>" . $tradeForDisplay2 . "</td>";
                        echo '<td class="d-none d-md-table-cell">' . $row['date_created'] . '</td>';
                        echo "<td>";
                        echo "<form method='post' onsubmit='return confirm(\"Are you sure you want to delete this listing?\");'>";
                        echo "<input type='hidden' name='listing_id' value='" . $row['id'] . "' />";
                        echo "<button type='submit' name='delete_listing' class='btn btn-danger'>Delete</button>";
                        echo "</form>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No listings found for the logged-in user.</td></tr>";
                }

                // Close the database connections
                $conn->close();
                $editionStmt->close();
                ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>



<!-- Floating Popup -->
<div class="modal fade" id="listingModal" tabindex="-1" role="dialog" aria-labelledby="listingModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="listingModalLabel">Listing Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <img src="" id="modalImage1" class="img-fluid" alt="Card Image">
                        <p class="quantity-badge">Amount: <span id="modalamount"></span></p>
                    </div>
                    <div class="col-md-6">
                        <img src="" id="modalImage2" class="img-fluid" alt="Card Image">
                    </div>
                </div>
                <h6>Card Price: <span id="modalCardPrice"></span> | Created Date: <span id="modalCreatedDate"></span></h6>
                <p class="card-text">Trade For:</p>
                <!-- Trade-for card images and quantities -->
                <div class="trade-for-images"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p>&copy; 2023 Card Trading. All rights reserved.</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-alpha1/dist/js/bootstrap.min.js"></script>
<script>
    // JavaScript code for showing the floating popup
    $(document).ready(function () {
        // When a row is clicked, show the modal and set the data
        $(".clickable-row").click(function () {
            var listingId = $(this).data("listing-id");
            var cardNumber = $(this).data("card-number");
            var image1 = $(this).data("image1");
            var image2 = $(this).data("image2");
            var tradeForImages = $(this).data("trade-for-images").split(',');

            $("#modalImage1").attr("src", image1);
            $("#modalImage2").attr("src", image2);

            // Get the card price from the row and set it in the modal
            var cardPrice = $(this).find("td.card-price").text();
            $("#modalCardPrice").text(cardPrice);

            // Get the created date from the row and set it in the modal
            var createdDate = $(this).find("td.created-date").text();
            $("#modalCreatedDate").text(createdDate);

            // Get the created date from the row and set it in the modal
            var cardamount = $(this).find("td.card-amount").text();
            $("#modalamount").text(cardamount);

            $(".trade-for-images").empty();
            for (var i = 0; i < tradeForImages.length; i++) {
                // Split the trade-for card data to get quantity and card number
                var tradeCardData = tradeForImages[i].split('x');
                var quantity, cardName, cardNumber;

                if (tradeCardData.length === 2) {
                    // If trade-for card data has 'x' format, extract quantity and card name with card number
                    quantity = tradeCardData[0];
                    var cardInfo = tradeCardData[1].split('(');
                    cardName = cardInfo[0].trim();
                    cardNumber = cardInfo[1].replace(')', '').trim();
                } else {
                    // If the trade-for value does not have the 'x' format, consider it as just the card name with card number
                    quantity = '1'; // Default quantity to 1 if not specified
                    var cardInfo = tradeForImages[i].split('(');
                    cardName = cardInfo[0].trim();
                    cardNumber = cardInfo[1].replace(')', '').trim();
                }

                // Extract the edition from the cardNumber within the square brackets
var edition = cardNumber.match(/\[(.*?)\]/)[1];

// Remove the edition information from the cardNumber
var cardNumberWithoutEdition = cardNumber.replace(/\s*\[.*?\]\s*/, '');

// Fetch the image URL from the card name and number
var tradeCardImage = "card_images/" + edition + "/" + cardNumberWithoutEdition + ".png";



// Create a div to hold the trade-for card image and quantity
var tradeCardDiv = '<div class="trade-card-div">';
tradeCardDiv += '<span class="trade-card-number">' + cardNumberWithoutEdition + '</span>';
tradeCardDiv += '<img src="' + tradeCardImage + '" class="img-fluid">';
tradeCardDiv += '<span class="quantity-badge">' + quantity + 'x</span>';
tradeCardDiv += '</div>';

// Append the div to the trade-for images container
$(".trade-for-images").append(tradeCardDiv);
            }

            $("#listingModal").modal("show");
        });
    });
</script>
</body>
</html>
