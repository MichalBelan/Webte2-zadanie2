<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once('config.php');

$pdo = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
// Nastavenie režimu chybového hlásenia
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['date']))
            citajDatum($pdo, $_GET['date']);
        else
            citajJedlo($pdo);
        break;
    case 'POST':
        $json = json_decode(file_get_contents('php://input'), true);
        if ($json == null) {
            echo json_encode(array('error' => 'Nepodarilo sa vytvoriť, nepodarilo sa prečítať '));
            http_response_code(422);
            break;
        }
        vytvorJedlo($pdo, $json);
        break;
    case 'PUT':
        if (isset($_GET['id'])) {
            $json = json_decode(file_get_contents('php://input'), true);
            if ($json == null) {
                echo json_encode(array('error' => 'Nepodarilo sa vytvoriť, nepodarilo sa prečítať'));
                http_response_code(422);
                break;
            }
            upravJedlo($pdo, $_GET['id'], $json);
        }
        else {
            echo json_encode(array('error' => 'chýba parameter dopytu ID'));
            http_response_code(400);
        }
        break;
    case 'DELETE':
        if (isset($_GET['id']))
        vymazJedloID($pdo, $_GET['id']);
        else {
            echo json_encode(array('error' => 'Odstránenie zlyhalo, chýba parameter dopytu ID'));
            http_response_code(400);
        }
        break;
}

function citajJedlo($pdo) {
    $pondelok = date("Y-m-d", strtotime("Monday this week"));
    $piatok = date("Y-m-d", strtotime("Friday this week"));
    $sql = "SELECT * FROM menu_jedlo WHERE zaciatok_datum >= ? AND koniec_datum <= ? ORDER BY restauracia_id, zaciatok_datum, popis ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pondelok, $piatok]);
    $meals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($meals);
}

function citajDatum($pdo, $date) {
    $sql = "SELECT * FROM menu_jedlo WHERE ? BETWEEN zaciatok_datum AND koniec_datum ORDER BY restauracia_id, zaciatok_datum, popis ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    $meals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($meals);
}

function vytvorJedlo($pdo, $data) {
    if (!existujuceKluce(['nazov', 'restauracia_id', 'popis', 'cena', 'zaciatok_datum', 'koniec_datum'], $data)) {
        echo json_encode(array('error' => 'Vytvorenie zlyhalo, chýbajú parametre'));
        http_response_code(400);
        return;
    }

    $sql = "SELECT id FROM restauracia WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['restauracia_id']]);
    if (empty($stmt->fetchColumn())) {
        echo json_encode(array('error' => 'Nepodarilo sa vytvoriť nesprávny identifikátor reštaurácie'));
        http_response_code(400);
        return;
    }

    $sql = "SELECT id FROM menu_jedlo WHERE nazov = ? AND restauracia_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['nazov'], $data['restauracia_id']]);
    if (!empty($stmt->fetchColumn())) {
        echo json_encode(array('error' => 'Vytvorte neúspešné, duplicitné hodnoty názvu a identifikátora reštaurácie'));
        http_response_code(400);
        return;
    }

    list($zaciatok_datum, $koniec_datum) = skontrolujDatum($data['zaciatok_datum'], $data['koniec_datum']);
    if ($zaciatok_datum == null) {
        echo json_encode(array('error' => 'Vytvorenie zlyhalo, nesprávny rozsah dátumov'));
        http_response_code(400);
        return;
    }
    
    $den = null;
    if ($zaciatok_datum == $koniec_datum)
        $den = date("l", strtotime($zaciatok_datum));

    $sql = "INSERT INTO menu_jedlo (nazov, restauracia_id, popis, cena, den, zaciatok_datum, koniec_datum) VALUES (:nazov, :restauracia_id, :popis, :cena, :den, :zaciatok_datum, :koniec_datum)";
    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute([
        ":nazov" => $data["nazov"],
        ":restauracia_id" => $data['restauracia_id'],
        ":popis" => $data["popis"],
        ":cena" => $data["cena"],
        ":den" => $den,
        ":zaciatok_datum" => $zaciatok_datum,
        ":koniec_datum" => $koniec_datum
    ])) {
        echo json_encode(array('error' => 'Vytvorenie zlyhalo'));
        http_response_code(400);
        return;
    }
    echo json_encode(array('success' => 'Data boli vytvorene uspesne'));
}

function upravJedlo($pdo, $id, $data) {
    $sql = "SELECT * FROM menu_jedlo WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    if (!$meal = $stmt->fetch()) {
        echo json_encode(array('error' => 'Aktualizácia zlyhala, neexistujúce ID'));
        http_response_code(400);
        return;
    }

    foreach ($meal as $key => $value)
        if (array_key_exists($key, $data))
            $meal[$key] = $data[$key];

    list($zaciatok_datum, $koniec_datum) = skontrolujDatum($meal['zaciatok_datum'], $meal['koniec_datum']);
    if ($zaciatok_datum == null) {
        echo json_encode(array('error' => 'Aktualizácia zlyhala, nesprávny rozsah dátumov'));
        http_response_code(400);
        return;
    }

    $sql = "SELECT id FROM restauracia WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$meal['restauracia_id']]);
    if (empty($stmt->fetchColumn())) {
        echo json_encode(array('error' => 'Aktualizácia zlyhala, nesprávny identifikátor reštaurácie'));
        http_response_code(400);
        return;
    }

    $sql = "SELECT id FROM menu_jedlo WHERE nazov = ? AND restauracia_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$meal['nazov'], $meal['restauracia_id']]);
    $found_id = $stmt->fetchColumn();
    if (!empty($found_id) && $found_id != $id) {
        echo json_encode(array('error' => 'Aktualizácia zlyhala, duplicitné hodnoty názvu a identifikátora reštaurácie'));
        http_response_code(400);
        return;
    }

    $den = null;
    if ($zaciatok_datum == $koniec_datum)
        $den = date("l", strtotime($zaciatok_datum));

    $sql = "UPDATE menu_jedlo SET nazov = :nazov, restauracia_id = :restauracia_id, popis = :popis, cena = :cena, den = :den, zaciatok_datum = :zaciatok_datum, koniec_datum = :koniec_datum WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute([
        ":nazov" => $meal["nazov"],
        ":restauracia_id" => $meal['restauracia_id'],
        ":popis" => $meal["popis"],
        ":cena" => $meal["cena"],
        ":den" => $den,
        ":zaciatok_datum" => $zaciatok_datum,
        ":koniec_datum" => $koniec_datum,
        ":id" => $id
    ])) {
        echo json_encode(array('error' => 'Uprava zlyhala'));
        http_response_code(400);
    }
    echo json_encode(array('success' => 'Údaje boli úspešne aktualizované'));
}

function vymazJedloID($pdo, $restaurant_id) {
    $stmt = $pdo->prepare('DELETE FROM menu_jedlo WHERE restauracia_id = ?');
    if ($stmt->execute([$restaurant_id]))
        echo json_encode(array('success' => 'Údaje boli úspešne odstránené'));
    else {
        echo json_encode(array('error' => 'Vymazanie zlyhalo'));
        http_response_code(400);
    }
}

function existujuceKluce($keys, $array){
    foreach($keys as $key)
        if(!array_key_exists($key, $array))
            return false;
    return true;
}

function skontrolujDatum($zaciatok_datum, $koniec_datum) {
    $zaciatok_datum = date("Y-m-d", strtotime($zaciatok_datum));
    $koniec_datum = date("Y-m-d", strtotime($koniec_datum));

    if ($zaciatok_datum > $koniec_datum)
        return null;
    else
        return array($zaciatok_datum, $koniec_datum);
}





?>