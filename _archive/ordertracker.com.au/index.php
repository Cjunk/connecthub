<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Calendar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .busy-day {background-color: #f8d7da;} /* Light red for busy days */
        .today {background-color: #cfe2ff;} /* Light blue for today */
    </style>
</head>
<body>
    <?php
    include '../php/log_website_visit.php';
    $websiteName  = "ORDERTRACKER";  // Set the website ID based on your application logic
    logVisit($conn, $websiteName );
    $conn->close();
?>
    <div class="container mt-5">
        <h1 class="mb-4">My Schedule for April 2024</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include('config.php');
                $conn = new mysqli(servername, username, password, dbname);

                $year = 2024;
                $month = 4; // April
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $dayOfWeek = date('w', mktime(0, 0, 0, $month, 1, $year)); // Day of week for 1st

                $calendar = [];
                for ($i = 0; $i < $dayOfWeek; $i++) {
                    $calendar[] = " ";
                }
                for ($i = 1; $i <= $daysInMonth; $i++) {
                    $calendar[] = $i;
                }

                $sql = "SELECT DAY(start_date) AS day FROM Events WHERE MONTH(start_date) = $month AND YEAR(start_date) = $year";
                $result = $conn->query($sql);
               
                $busyDays = [];
                while ($row = $result->fetch_assoc()) {
                    $busyDays[intval($row['day'])] = true;  // Ensure days are stored as integers for correct comparison
                }

                $weeks = array_chunk($calendar, 7);
                foreach ($weeks as $week) {
                    echo "<tr>";
                    foreach ($week as $day) {
                        $classes = [];
                        if (isset($busyDays[intval($day)])) {  // Compare integer values
                            $classes[] = 'busy-day';
                        }
                        if ($day == date('j') && $month == date('n') && $year == date('Y')) {
                            $classes[] = 'today'; // Highlight today's date
                        }
                        echo "<td" . (!empty($classes) ? " class='" . implode(' ', $classes) . "'" : "") . ">" . (is_numeric($day) ? $day : "&nbsp;") . "</td>";
                    }
                    echo "</tr>";
                }

                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>



