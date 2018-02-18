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
        <h1>Product price history</h1>
        @if(count ($records) > 0)
            <form method="POST" action="/watchlist/add">
                <!--Image-->
                <div></div>
                <p>ProductID - {{ $records[0]->product_id }}</p>
                <p>Name - {{ $records[0]->name }}</p>                
                <p>Email</p>                
                <input type="email" name="email" placeholder="abc@example.com" required>
                <input type="submit" value="Add to watch list">

                <!-- Hidden values to be submitted with the form -->
                <input type="hidden" name ="id" value="{{ $records[0]->product_id }}">
                <input type="hidden" name ="price" value="{{ $records[0]->price }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
            </form>

            <h3>Price history</h3>
            @foreach ($records as $record)	
                <div>
                    <p>{{ $record->price }}</p>
                    <p>{{ $record->timestamp }}</p>
                </div>
            @endforeach
        @endif
    </body>
</html>
