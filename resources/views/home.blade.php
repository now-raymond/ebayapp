<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>EBAE</title>
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
    </head>
    <body>
        <div>
            <h1>Welcome to EBAE</h1>

            <form method="GET" action="/watchlist">       
                <input type="email" name="email" placeholder="abc@example.com" required>
                <input type="submit" value="Check my watching list">
            </form>

            <form method="GET" action="/search">       
                <input type="text" name="query" placeholder="Search for an item here" required>
                <input type="submit" value="Search">
            </form>

        </div>    
    </body>
</html>
