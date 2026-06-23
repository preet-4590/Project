<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Mobile Scanner</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        body {
            font-family: sans-serif;
            background: #121212;
            color: white;
            text-align: center;
            padding: 20px;
        }

        #reader {
            width: 100%;
            max-width: 400px;
            margin: 20px auto;
            border-radius: 15px;
            overflow: hidden;
            border: 2px solid #333;
        }

        .info {
            color: #888;
            font-size: 0.9em;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <h3>Gate Control: Scan QR</h3>
    <div id="reader"></div>
    <p class="info">Point your camera at the Student QR Code</p>

    <script>
        function onScanSuccess(decodedText) {
            console.log("Scanned Text: ", decodedText);

            // Extract the ID from the URL inside the QR
            // Works for: https://.../page.php?id=#E1 or just #E1
            let url = new URL(decodedText.includes('http') ? decodedText : 'http://x.com?id=' + decodedText);
            let studentId = url.searchParams.get("id") || decodedText;

            // Redirect to the result page
            window.location.href = "guard_scan_result.php?id=" + encodeURIComponent(studentId);
        }

        // Configuration for mobile
        let config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };

        let html5QrcodeScanner = new Html5QrcodeScanner("reader", config, false);
        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>

</html>