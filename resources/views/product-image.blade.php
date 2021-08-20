<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Zaveri Bazaar image viewer</title>

    <style>
        * {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        html,
        body {
            margin: 0px;
            padding: 0px;
            height: 100%;
            background: #222;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        img {
            margin: 0px auto;
        }
    </style>
</head>

<body>
    <img src="{{ $imageUrl }}" alt="Zaveri Bazaar product image" style="max-width: 100%; max-height: 90vh; object-fit: contain;">
</body>

</html>