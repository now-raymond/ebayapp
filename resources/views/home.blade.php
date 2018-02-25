<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>EBAE</title>
        <!-- Bootstrap -->
        <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

        <!-- Font -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
        <!-- <link rel="stylesheet" href="{{ URL::asset('css/style.css') }}" type="text/css">  -->
    </head>

    <body>
        <!-- Navigation bar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light justify-content-between">
            <a class="navbar-brand" href="/">HOME</a>

            <form id="searchForm" class="form-inline" method="GET" action="/search">
                <input id="searchInput" class="form-control mr-sm-2" style="width:300px" type="text" name="query" placeholder="Search for an item here" aria-label="Search" required>
            </form>       
            
            <button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#emailModal">My watch list</button>
        </nav>

        <!-- Modal -->
        <div class="modal" id="emailModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tell us your email address...</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form id="watchListForm" method="GET" action="/watchlist">
                        <div class="modal-body">               
                            <input id="emailInput" class="form-control mr-sm-2" type="email" name="email" placeholder="EG. abc@xyz.com" aria-label="Search" required>
                        </div>

                        <div class="modal-footer">
                            <input id="confirmEmailBtn" type="submit" class="btn btn-success" value="Confirm"></input>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </form>                               
                </div>
            </div>
        </div>

    </body>


    <script type="text/javascript">
        $('#emailModal').on('shown.bs.modal', function () {
            $('#emailInput').trigger('focus')
        })  

        $("#searchInput").keypress(function(e){
            if (e.keyCode == 13){
                if($("#searchInput").val()){
                    $('#searchForm').submit();
                }
            }
        });    
    </script>
</html>
