<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once('config.php');

try {
    $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
    // Nastavenie režimu chybového hlásenia
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Pripojenie k databáze bolo úspešné";
} catch (PDOException $e) {
    echo "Pripojenie k databáze zlyhalo: " . $e->getMessage();
}




if (isset($_GET['day_filter']) && $_GET['day_filter'] != "All") {
     $sql = "SELECT *, menu_jedlo.nazov as jedlo, restauracia.nazov as restauracia FROM menu_jedlo JOIN restauracia ON menu_jedlo.restauracia_id=restauracia.id WHERE den = ?";
    

    $stmt = $db->prepare($sql);
    $stmt->execute([$_GET["day_filter"]]);
    $meals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $sql = "SELECT *, menu_jedlo.nazov as jedlo, restauracia.nazov as restauracia FROM menu_jedlo JOIN restauracia ON menu_jedlo.restauracia_id=restauracia.id";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $meals = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$sql = "SELECT nazov FROM restauracia";
$stmt = $db->prepare($sql);
$stmt->execute();
$restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);




?>



<!DOCTYPE html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Jedálne</title>
</head>

<body>
    <div class="row">
        <div class="col-12 bg-clip-content text-black text-center">
            <header>
                <h1>Stravovacie zariadenia FEI a okolie</h1>
            </header>
        </div>
    </div>



    <div class="row">
        <nav class="col-12 bg-clip-content text-black text-center bg-primary">
            <ul class="row justify-content-center">
                <div class="col-4 d-grid">
                    <li>
                        <a href="index.php">Jedálny lístok</a>
                    </li>
                </div>

                <div class="col-4 d-grid">
                    <li>
                        <a href="overenie.php">Overenie metód API</a>
                    </li>
                </div>
                <div class="col-4 d-grid">
                    <li>
                        <a href="popis.php">Popis metód API</a>
                    </li>
                </div>
            </ul>
        </nav>
    </div>


    <div class="page-content p-3">
        <div class="col-12 bg-clip-content text-black text-center">
            <h2>Jedálny lístok</h2>
        </div>

        <form action="" method="get">

            <div class="row mb-3">
                <div class="col-6">
                    <select class="form-select" name="day_filter">
                        <option value="All">All</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                    </select>
                </div>
                <div class="col-6 d-grid">
                    <button type="submit" name="akcia" value="filter" class="btn btn-success">Filter</button>
                </div>
            </div>
        </form>
    </div>

    <table>

        <?php
        foreach ($restaurants as $restaurant) {
            echo "<h3>" . $restaurant['nazov'] . "</h3>";
        }


        echo date("Y-m-d", strtotime("Monday this week")) . " - " . date("Y-m-d", strtotime("Friday this week"));
        ?>

        <tr>
            <th>Deň</th>
            <th>Názov</th>
            <th>Reštaurácia</th>
            <th>Popis</th>
            <th>Cena</th>
        </tr>

        <?php

        $previous_restauracia = null;
        foreach ($meals as $meal) {
            if ($previous_restauracia !== null && $meal['restauracia'] !== $previous_restauracia) {
                echo '<tr>';
                echo '<td colspan="5" style="border: none;"></td>';
                echo '</tr>';
                // echo '<tr>';
                // echo '<td colspan="5" style="border: none;"></td>';
                // echo '</tr>';
                // echo '<tr>';
                // echo '<td colspan="5" style="border: none;"></td>';
                // echo '</tr>';
            }

            echo '<tr>';
            echo '<td>' . $meal['den'] . '</td>';
            echo '<td>' . $meal['jedlo'] . '</td>';
            echo '<td>' . $meal['restauracia'] . '</td>';
            echo '<td>' . $meal['popis'] . '</td>';
            echo '<td>' . $meal['cena'] . '</td>';
            echo '</tr>';

            $previous_restauracia = $meal['restauracia'];
        }


        ?>
    </table> <br>








    <div class="row">
        <div class="col-12 bg-clip-content text-white text-center bg-primary">
            <footer class="px-2">
                Michal Belan, &copy; 2023
            </footer>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.3.4/axios.min.js"></script>
</body>

</html>