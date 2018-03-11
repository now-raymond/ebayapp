<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageController extends Controller {

    public function home(){
        // Find most watched products.
        // Returns: watch_count, ebay_id, name, image, url, last_known_price
        $mostWatchedQuery = "SELECT COUNT(product_id) AS watch_count, ebay_id, name, image, item_web_url AS url, last_known_price
                                FROM product_watches JOIN products
                                ON product_watches.product_id=products.id
                                GROUP BY product_id
                                ORDER BY watch_count DESC
                                LIMIT 8";

        $mostWatchedProducts = DB::select($mostWatchedQuery);

        // Find most visited (clicked) products.
        // Returns: visited_count, ebay_id, name, image, url, last_known_price
        $trendingProductsQuery = "SELECT visited_count, ebay_id, name, image, item_web_url AS url, last_known_price
                                    FROM products
                                    ORDER BY visited_count DESC";
        $trendingProducts = DB::select($trendingProductsQuery);

        return view('home', [
            'mostWatchedProducts' => $mostWatchedProducts,
            'trendingProducts' => $trendingProducts,
        ]);
    }

}
