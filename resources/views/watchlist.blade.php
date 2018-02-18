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
        <h1>Watch list</h1>
        @if(count($products) > 0)
            @foreach ($products as $product)	
                <div>
                    <!--Image-->
                    <div></div>
                    <p>ProductID - {{ $product->id }}</p>
                    <p>Product name - {{ $product->name }}</p>
                    <p>Price - {{ $product->last_known_price }}</p>
                </div>
                <hr>
            @endforeach
        @endif
        <a href="/">Back to home page</a>
    </body>
</html>
