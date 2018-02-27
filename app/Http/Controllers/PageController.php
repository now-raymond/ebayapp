<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller {

    // Retrieve trendy products from ebay
    public function home(){
        // Query from database 
        // We can have a counter COL, everytime people search for that product, increment it by 1
        $trendyProducts = [];
        return view('home', [
            'trendyProducts' => $trendyProducts,
        ]);
    }

}
