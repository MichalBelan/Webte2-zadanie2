<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();


require_once('config.php');
require_once('den.php');

function alerty($msg) {
    echo "<script type='text/javascript'>alert('$msg');</script>";
}

function downloadCurl($url)
{
    $x = curl_init();
    curl_setopt($x, CURLOPT_URL, $url);
    curl_setopt($x, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($x, CURLOPT_RETURNTRANSFER, 1);
    $vystup = curl_exec($x);
    curl_close($x);
    return $vystup;
}

function getDOM($html)
{
    $d = new DOMDocument();
    $internalErrors = libxml_use_internal_errors(true);
    $d->loadHTML($html);
    libxml_use_internal_errors($internalErrors);
    return $d;
}


try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
    // Nastavenie režimu chybového hlásenia
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Pripojenie k databáze zlyhalo: " . $e->getMessage();
}

// Dotaz na získanie zoznamu reštaurácií
// $sql = "SELECT id, nazov FROM restauracia";
// $result = $pdo->query($sql);

// Zatvorenie spojenia s databázou
 $db = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['akcia']) && $_POST['akcia'] == 'api-request') {
        $b_url = 'https://site48.webte.fei.stuba.sk/webte2_zadanie2/api_be.php?';
        $options = ['http' => ['header' => 'Content-type: application/json', 
        'ignore_errors' => true, 
        'content' => $_POST['json_body']
        ]];

        switch ($_POST['request_method']) {

            case 'post':
                $options['http']['method'] = "post";
                $context = stream_context_create($options);
                alerty(file_get_contents($b_url, false, $context));
                
                break;
            case 'put':
                $options['http']['method'] = "put";
                $context = stream_context_create($options);
                alerty(file_get_contents($b_url . $_POST['query_parameter'], false, $context));
                
                break;
            case 'delete':
                $options['http']['method'] = "delete";
                $context = stream_context_create($options);
                alerty(file_get_contents($b_url . $_POST['query_parameter'], false, $context));
                break;

            default:
                echo "Neznamy request";
                break;
        }
    }


   


    if (isset($_POST['akcia']) && $_POST['akcia'] == 'stiahni') {
        $fail = false;
        $pdo->beginTransaction();

        $d = getDOM(downloadCurl('https://www.novavenza.sk/tyzdenne-menu'));
        $sql = "INSERT INTO restauracia_html (restauracia_nazov,html) VALUES (?,?)";
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute(["Venza", $d->saveHTML()])) {
            $fail = true;
        }

        $d = getDOM(downloadCurl('http://eatandmeet.sk/tyzdenne-menu'));
        $sql = "INSERT INTO restauracia_html (restauracia_nazov,html) VALUES (?,?)";
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute(["Eat & Meet", $d->saveHTML()])) {
            $fail = true;
        }

        $d = getDOM(downloadCurl('http://www.freefood.sk/menu/#fiit-food'));
        $sql = "INSERT INTO restauracia_html (restauracia_nazov,html) VALUES (?,?)";
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute(["FiitFood", $d->saveHTML()])) {
            $fail = true;
        }

        if ($fail) {
            $pdo->rollBack();
            echo "chyba";
        } else {
            $pdo->commit();
            echo "Menu bolo úspešne stiahnuté a uložené do databázy.";
        }
    }

   



    if (isset($_POST['akcia']) && $_POST['akcia'] == 'rozparsuj') {
        $sql = "SELECT * FROM restauracia_html ORDER BY created_at DESC LIMIT 3";
        $restauracia_html_array = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($restauracia_html_array as $restauracia_html) {
            $sql = "INSERT INTO restauracia(nazov) VALUES(?)
                    ON DUPLICATE KEY UPDATE id=id";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$restauracia_html["restauracia_nazov"]])) {
                $sql = "SELECT id FROM restauracia WHERE nazov = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$restauracia_html["restauracia_nazov"]]);
                $restauracia_id = $stmt->fetchColumn();

                $d = getDOM($restauracia_html["html"]);
                $xpath = new DOMXPath($d);

                $fail = false;
                $pdo->beginTransaction();

                if ($restauracia_html["restauracia_nazov"] == "FiitFood") {
                    $denne_menu_list =  $xpath->query('//div[@id="fiit-food"]//ul[@class="daily-offer"]/li/ul[@class="day-offer"]');

                    $denna_ponuka = 1;
                    foreach ($denne_menu_list as $denne_menu) {
                        $xpath_q = './/text()';
                        $text_list = $xpath->evaluate($xpath_q, $denne_menu);
                        $menu_pole = array();
                        $jedlo_pole = array();

                        for ($i=1; $i < $text_list->length + 1; $i++) {
                            $text = trim($text_list->item($i - 1)->textContent);

                            switch ($i % 3) {
                                case 1:
                                    $jedlo_pole["popis"] = $text;
                                    break;
                                case 2:
                                    $jedlo_pole["nazov"] = $text;
                                    break;
                                case 0:
                                    $jedlo_pole["cena"] = $text;
                                    $menu_pole[] = $jedlo_pole;
                                    $jedlo_pole = array();
                                    break;
                                default:
                                    $fail = true;
                                    echo "Zlý počet parametrov";
                                    break;
                            }
                        }

                        $sql = "INSERT INTO menu_jedlo(nazov, restauracia_id, popis, cena, den, zaciatok_datum, koniec_datum) 
                                VALUES(:nazov, :restauracia_id, :popis, :cena, :den, :zaciatok_datum, :koniec_datum)
                                ON DUPLICATE KEY UPDATE popis = :popis, cena = :cena, den = :den, zaciatok_datum = :zaciatok_datum, koniec_datum = :koniec_datum";
                        $stmt = $pdo->prepare($sql);
                        foreach ($menu_pole as $polozka)
                            if (!$stmt->execute([
                                ":nazov" => $polozka["nazov"],
                                ":restauracia_id" => $restauracia_id,
                                ":popis" => $polozka["popis"],
                                ":cena" => $polozka["cena"],
                                ":den" => Day::from($denna_ponuka)->name,
                                ":zaciatok_datum" => date("Y-m-d", strtotime(Day::from($denna_ponuka)->name . " this week")),
                                ":koniec_datum" => date("Y-m-d", strtotime(Day::from($denna_ponuka)->name . " this week"))
                            ]))
                                $fail = true;
                        ++$denna_ponuka;
                    }
                }
                else if ($restauracia_html["restauracia_nazov"] == "Venza") {
                    $denne_menu_list =  $xpath->query('//div[@id="pills-tabContent"]//div[@class="menubar"]/div');

                    $denna_ponuka = 1;
                    foreach ($denne_menu_list as $denne_menu) {
                        $xpath_q = './div/div';
                        $menu_polozka_list = $xpath->evaluate($xpath_q, $denne_menu);
                        $menu_pole = array();
                        $jedlo_pole = array();
                        $jePolievka = false;

                        foreach ($menu_polozka_list as $menu_polozka) {
                            $xpath_q = './/text()[normalize-space()]';
                            $text_list = $xpath->evaluate($xpath_q, $menu_polozka);

                            if ($jePolievka) {
                                for ($i=0; $i < $text_list->length; $i++) {
                                    $text = trim($text_list->item($i)->textContent);
                                    switch ($i % 4) {
                                        case 0:
                                            $jedlo_pole["popis"] = $text;
                                            break;
                                        case 1:
                                            $jedlo_pole["nazov"] = $text;
                                            break;
                                        case 2:
                                        case 3:
                                            if ($text[0] == "(")
                                                $jedlo_pole["nazov"] .= " " . $text;
                                            else if (is_numeric($text[0])) {
                                                $jedlo_pole["cena"] = $text;
                                                $menu_pole[] = $jedlo_pole;
                                                $jedlo_pole = array();
                                            }
                                            break;
                                        default:
                                            $fail = true;
                                            echo "Zlý počet parametrov";
                                            break;
                                    }
                                }
                            }
                            else {
                                for ($i=1; $i < $text_list->length; $i++) {
                                    $text = trim($text_list->item($i)->textContent);
                                    if ($text[0] == "(")
                                        $jedlo_pole["nazov"] .= " " . $text;
                                    else if (is_numeric($text[0])) {
                                        $jedlo_pole["cena"] = $text;
                                        $jedlo_pole["popis"] = "Polievka";
                                        $menu_pole[] = $jedlo_pole;
                                        $jedlo_pole = array();
                                    }
                                    else
                                        $jedlo_pole["nazov"] = $text;
                                }
                                $jePolievka = true;
                            }
                        }

                        $sql = "INSERT INTO menu_jedlo(nazov, restauracia_id, popis, cena, den, zaciatok_datum, koniec_datum) 
                                VALUES(:nazov, :restauracia_id, :popis, :cena, :den, :zaciatok_datum, :koniec_datum)
                                ON DUPLICATE KEY UPDATE popis = :popis, cena = :cena, den = :den, zaciatok_datum = :zaciatok_datum, koniec_datum = :koniec_datum";
                        $stmt = $pdo->prepare($sql);
                        foreach ($menu_pole as $polozka)
                            if (!$stmt->execute([
                                ":nazov" => $polozka["nazov"],
                                ":restauracia_id" => $restauracia_id,
                                ":popis" => $polozka["popis"],
                                ":cena" => $polozka["cena"],
                                ":den" => Day::from($denna_ponuka)->name,
                                ":zaciatok_datum" => date("Y-m-d", strtotime(Day::from($denna_ponuka)->name . " this week")),
                                ":koniec_datum" => date("Y-m-d", strtotime(Day::from($denna_ponuka)->name . " this week"))
                            ]))
                                $fail = true;
                        ++$denna_ponuka;
                    }
                }
                else if ($restauracia_html["restauracia_nazov"] == "Eat & Meet") {
                    $dni = range(1, 7);
                    $xpath_q = '//div[';
                    foreach ($dni as $den)
                        $xpath_q .= '@id="day-' . $den . '"' . ' or ';
                    $xpath_q = rtrim($xpath_q, ' or ') . ']';

                    $denne_menu_list = $xpath->query($xpath_q);

                    $denna_ponuka = 1;
                    foreach ($denne_menu_list as $denne_menu) {
                        $xpath_q = './div';
                        $menu_polozka_list = $xpath->evaluate($xpath_q, $denne_menu);
                        $menu_pole = array();
                        $jedlo_pole = array();
                        $jePolievka = false;

                        foreach ($menu_polozka_list as $menu_polozka) {
                            $xpath_q = './/text()[normalize-space()]';
                            $text_list = $xpath->evaluate($xpath_q, $menu_polozka);

                            for ($i=0; $i < $text_list->length; $i++) {
                                $text = trim($text_list->item($i)->textContent);
                                switch ($i % 5) {
                                    case 0:
                                        $jedlo_pole["popis"] = $text;
                                        break;
                                    case 1:
                                        $jedlo_pole["cena"] = $text;
                                        break;
                                    case 2:
                                        $jedlo_pole["cena"] .= " " . $text;
                                        break;
                                    case 3:
                                        $jedlo_pole["nazov"] = $text;
                                        break;
                                    case 4:
                                        $jedlo_pole["nazov"] .= " " . $text;
                                        break;
                                }
                                if ($i == $text_list->length - 1) {
                                    $menu_pole[] = $jedlo_pole;
                                    $jedlo_pole = array();
                                }
                            }
                        }

                        $sql = "INSERT INTO menu_jedlo(nazov, restauracia_id, popis, cena, den, zaciatok_datum, koniec_datum) 
                                VALUES(:nazov, :restauracia_id, :popis, :cena, :den, :zaciatok_datum, :koniec_datum)
                                ON DUPLICATE KEY UPDATE popis = :popis, cena = :cena, den = :den, zaciatok_datum = :zaciatok_datum, koniec_datum = :koniec_datum";
                        $stmt = $pdo->prepare($sql);
                        foreach ($menu_pole as $polozka)
                            if (!$stmt->execute([
                                ":nazov" => $polozka["nazov"],
                                ":restauracia_id" => $restauracia_id,
                                ":popis" => $polozka["popis"],
                                ":cena" => $polozka["cena"],
                                ":den" => Day::from($denna_ponuka)->name,
                                ":zaciatok_datum" => date("Y-m-d", strtotime(Day::from($denna_ponuka)->name . " this week")),
                                ":koniec_datum" => date("Y-m-d", strtotime(Day::from($denna_ponuka)->name . " this week"))
                            ]))
                                $fail = true;
                        ++$denna_ponuka;
                    }
                }

                if ($fail) {
                    echo "Vznikla chyba";
                    $pdo->rollBack();
                }
                else
                    $pdo->commit();
            }
            else
                echo "Vznikla chyba";
        }
    }

    


    if (isset($_POST['akcia']) && $_POST['akcia'] == 'vymaz') {
        $pdo->beginTransaction();

        // Vymazanie dát z tabulky 'menu_jedlo'
        $sql = "DELETE FROM menu_jedlo";
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute()) {
            $pdo->rollBack();
            echo "Chyba pri vymazávaní dát z tabuľky 'menu_jedlo'";
            exit;
        }

        // Vymazanie dát z tabulky 'restauracia_html'
        $sql = "DELETE FROM restauracia_html";
        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute()) {
            $pdo->rollBack();
            echo "Chyba pri vymazávaní dát z tabuľky 'restauracia_html'";
            exit;
        }

        // Potvrdenie zmien v databáze
        $pdo->commit();

        echo "Dáta boli úspešne vymazané.";
    }
}


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
    <title>Document</title>
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
        <div class="row">
            <div class="col-12 bg-clip-content text-black text-center">
                <h2>Overenie vytvorených metód API</h2>
            </div>
        </div>

        <form action="" method="post">
            <div class="row">
                <div class="col-12">
                    <button class="btn btn-success btn-block mb-2" name="akcia" value="stiahni" type="submit">Stiahni</button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <button class="btn btn-info btn-block mb-2" name="akcia" value="rozparsuj" type="submit">Rozparsuj</button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <button class="btn btn-danger btn-block" name="akcia" value="vymaz" type="submit">Vymaž</button>
                </div>
            </div>

        </form>

    </div>


    <div class="col-12 bg-clip-content text-black text-center">
        <h2>Formulár</h2>
    </div>

    <form action="" method="post">
        <div class="row">
            <div class="col-4">
                <label for="request_method" class="form-label">Metóda</label>
                <select class="form-select" name="request_method" id="request_method">
                    <option value="post">POST</option>
                    <option value="put">PUT</option>
                    <option value="delete">DELETE</option>
                </select>
            </div>

            <div class="col-4">
                <label for="query_parameter" class="form-label">Parametre</label>
                <input type="text" name="query_parameter" id="query_parameter" class="form-control">
            </div>

            <div class="col-12">
                <label for="json_body" class="form-label">Textarea</label>
                <textarea name="json_body" id="json_body" class="form-control" cols="35" rows="10"></textarea>
            </div>

            <div class="col-4 d-grid">
                <button type="submit" name="akcia" value="api-request" class="btn btn-success btn-primary">Odošli</button>
            </div>
        </div>
    </form>







    <div class="row">
        <div class="col-12 bg-clip-content text-white text-center bg-primary">
            <footer class="px-2">
                Michal Belan, &copy; 2023
            </footer>
        </div>

    </div>



    <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>

</body>

</html>