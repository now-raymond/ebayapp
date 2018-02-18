<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class ProductController extends Controller {

    // Search 'products' table for any relavent products using $productName 
    public function search(Request $request){
        $productName = $request->input('query');
        // return: id, ebay_id, name, last_known_price
        $products = DB::select("SELECT * FROM products WHERE name LIKE ?", array('%' . $productName . '%'));

        return view('search', [
            'products' => $products,
        ]);
    }

    // Search 'price_history' table for selected product record(s) using $productId 
    public function item($id){
        // return: product_id, price, timestamp (JOIN 'products' table to for name)
        $query = "SELECT products.name, price_history.product_id, price_history.price, price_history.timestamp 
                    FROM products 
                    JOIN price_history ON price_history.product_id = products.id
                    WHERE products.id = ?
                    ORDER BY price_history.timestamp DESC";
        $records = DB::select($query, array($id));

        return view('item', [
            'records' => $records,
        ]);
    }

    // "Watch" a product, user will be notified whenever prices of product being watched change
    public function addWatchProduct(Request $request){
        $productId = $request->input('id');
        $email = $request->input('email');
        $price = $request->input('price');

        // Check if the user already exists
        $user = DB::select("SELECT id FROM users WHERE email = ?", array($email));
        // Insert new user record if it doesn't already exist
        if($user == NULL){
            DB::insert("INSERT INTO users (email) VALUES (?)", array($email));
            $user = DB::select("SELECT id FROM users WHERE email = ?", array($email));
        }

        // Check if the product is already being watched by the user
        $isTracked = DB::select("SELECT id FROM product_watches 
                                WHERE product_id = ? AND user_id = ?", 
                                array($productId, $user[0]->id));

        if($isTracked == NULL){
            // Insert product into 'product_watches' table (if it's not already being watched)
            DB::insert("INSERT INTO product_watches (product_id, user_id, last_notified_price) 
                        VALUES (?, ?, ?)", array($productId, $user[0]->id, $price));
        }

        // Retrieve products watched by the user
        $query = "SELECT products.id, products.name, products.last_known_price
                    FROM products 
                    JOIN product_watches ON products.id = product_watches.product_id
                    WHERE product_watches.user_id = ?";
        $watching = DB::select($query, array($user[0]->id));

        return view('watchlist', [
            'products' => $watching,
        ]);
    }

    // Get user watching list
    public function getWatchProduct(Request $request){
        $email = $request->input('email');
        // Check if the user already exists
        $user = DB::select("SELECT id FROM users WHERE email = ?", array($email));
        // Insert new user record if it doesn't already exist
        if($user == NULL){
            DB::insert("INSERT INTO users (email) VALUES (?)", array($email));
            $user = DB::select("SELECT id FROM users WHERE email = ?", array($email));
        }

        // Retrieve products watched by the user
        $query = "SELECT products.id, products.name, products.last_known_price
                    FROM products 
                    JOIN product_watches ON products.id = product_watches.product_id
                    WHERE product_watches.user_id = ?";
        $watching = DB::select($query, array($user[0]->id));
        
        return view('watchlist', [
            'products' => $watching,
        ]);        
    }

}
