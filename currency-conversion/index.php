<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "currency_table";

$connection = new mysqli($host, $username, $password, $database);

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$targetCurrencies = ["NGN", "USD"]; 

$ch = curl_init();


$sql = "SELECT from_currency, amount FROM currency WHERE Status = 0 limit 1";
$result = $connection->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $baseCurrency = $row['from_currency'];
        $amount = $row['amount'];

        foreach ($targetCurrencies as $targetCurrency) {
    
            $url = "https://currency-conversion-and-exchange-rates.p.rapidapi.com/convert?from=$baseCurrency&to=$targetCurrency&amount=$amount";
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-RapidAPI-Host: currency-conversion-and-exchange-rates.p.rapidapi.com",
                "X-RapidAPI-Key: 829f249dfdmsh3b6f02e8b0a4febp165279jsnaaec6c0dacb8"
            ]);
            $data = curl_exec($ch);
            if ($data !== false) {
                $exchangeRate = json_decode($data, true)['info']['rate'];
                $exchangeRate *= $amount; 
                updateCurrencyRate($connection, $baseCurrency, $targetCurrency, $exchangeRate);
            } else {
                echo "cURL Error for $baseCurrency to $targetCurrency: " . curl_error($ch);
            }
        }
    }

    $result->close();
} else {
    echo "ERROR: Could not execute query: " . $connection->error;
}

curl_close($ch);
$connection->close();

function updateCurrencyRate($connection, $fromCurrency, $toCurrency, $rate) {
    $fromCurrency = $connection->real_escape_string($fromCurrency);
    $toCurrency = $connection->real_escape_string($toCurrency);
    
    $sql = "UPDATE currency SET Status = 1, $toCurrency = ? WHERE from_currency = ?";
    
    $stmt = $connection->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ds", $rate, $fromCurrency);
        if ($stmt->execute()) {
            echo "$fromCurrency $toCurrency -- Records were updated successfully.<br>";
        } else {
            echo "ERROR: Could not execute prepared statement. " . $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        echo "ERROR: Could not prepare statement. " . $connection->error . "<br>";
    }
}
?>
