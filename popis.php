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
                <h2> Popis vytvorených metód API,</h2>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-4 d-grid">
                <span class="badge text-bg-success fs-5 me-3">GET</span>
                <span>/api_be.php?date={YYYY-MM-DD}</span>
                <p>Pre zadaný dátum vráti menu jedál aj s cenou</p>
            </div>

            <div class="col-4 d-grid">
                <span class="badge text-bg-primary fs-5 me-3">GET</span>
                <span>/api_be.php</span>
                <p>Pre všetky reštaurácie vráti menu jedál aj s cenou na aktuálny týždeň</p>
            </div>

            <div class="col-4 d-grid">
                <span class="badge text-bg-warning fs-5 me-3">POST</span>
                <span>/api_be.php</span>
                <p>Do ponuky reštaurácie pridá nové jedlo</p>
                <code>{
                    "nazov": "Vyprážaný kurací rezeň(1,3,7,9,10)",<br>
                    "restauracia_id": 2,<br>
                    "popis":"",<br>
                    "cena": "4,10€",<br>
                    "zaciatok_datum": "2023-04-17",<br>
                    "koniec_datum": "2023-04-23"<br>

                    }</code>
            </div>

            <div class="col-4 d-grid mt-3">
                <span class="badge text-bg-info fs-5 me-3">PUT</span>
                <span>/api_be.php?id={id}</span>
                <p>Podľa zadaného id jedla ,upraví to jedlo</p>
                <code>{
                    "nazov": "Vyprážaný kurací rezeň(1,3,7,9,10)",<br>
                    "restauracia_id": 2,<br>
                    "popis":"",<br>
                    "cena": "4,10€",<br>
                    "zaciatok_datum": "2023-04-17",<br>
                    "koniec_datum": "2023-04-23"<br>

                    }</code>

            </div>

            <div class="col-4 d-grid mt-3">
                <span class="badge text-bg-danger fs-5 me-3">DELETE</span>
                <span>/api_be.php?id={id}</span>
                <p>Podľa zadaného id reštaurácie vymaže ponuku jedál v reštaurácií</p>
            </div>
        </div>





    </div>













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