<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;


class ProductController extends Controller {

    // Search 'products' table for any relavent products using $productName 
    public function search(Request $request){
        $productName = $request->input('query');
        
        $token = $request->session()->get('token', 'not found');
        if($token == 'not found'){
            $token = $this->getToken();
            $request->session()->put('token', $token);
        }

        $response = $this->searchItem($token, $productName);
        if($response->total > 0){
            $items = $response->itemSummaries;
        }else{
            $items = null;
        }
       
        return view('search', [
            'query' => $productName,
            'products' => $items,
        ]);
    }

    // Search 'price_history' table for selected product record(s) using $productId 
    public function item(Request $request, $id){
        
        $productName = $request->input('name');
        $productImage = $request->input('image');
        $productPrice = $request->input('price');
        $productUrl = $request->input('url');

        // Insert product searched into database if it doesn't already exist
        $isProductExist = DB::select("SELECT last_known_price FROM products WHERE ebay_id = ?", array($id));
        if($isProductExist == null){
            // Insert into 'products' table
            DB::insert("INSERT INTO products (ebay_id, name, image, item_web_url, last_known_price) VALUES (?, ?, ?, ?, ?)", array($id, $productName, $productImage, $productUrl, $productPrice)); 
            
            // Insert into 'price_history' table
            $record = DB::select("SELECT id FROM products WHERE ebay_id = ?", array($id));
            DB::insert("INSERT INTO price_history (product_id, price) VALUES (?, ?)", array($record[0]->id, $productPrice));
        }

        $token = $request->session()->get('token', 'not found');
        if($token == 'not found'){
            $token = $this->getToken();
            $request->session()->put('token', $token);
        }

        // Update visited_count on 'products' table
        $updateVisitedCountQuery = "UPDATE products SET visited_count = visited_count + 1 WHERE ebay_id = ?";
        DB::statement($updateVisitedCountQuery, array($id));

        // return: product_id, price, timestamp (JOIN 'products' table to for name)
        $query = "SELECT price_history.product_id, price_history.price, price_history.timestamp 
                    FROM products JOIN price_history 
                    ON price_history.product_id = products.id
                    WHERE products.ebay_id = ?
                    ORDER BY price_history.timestamp DESC";

        // Product price history
        $records = DB::select($query, array($id));
        // Product latest information 
        $response = $this->getItem($token, $id);

        // Check if $response->shortDescription property is empty
        $shortDescription = '';
        if(property_exists($response, 'shortDescription')){
            $shortDescription = $response->shortDescription;
        }

        $product = (object)['title'=>$response->title, 
                            'details'=>$shortDescription,
                            'price'=>$response->price->value,
                            'image'=>$response->image->imageUrl,
                            'url'=>$productUrl];

        return view('item', [
            'records' => $records,
            'product' => $product,
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
        $query = "SELECT *
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
        $query = "SELECT *
                    FROM products 
                    JOIN product_watches ON products.id = product_watches.product_id
                    WHERE product_watches.user_id = ?";

        $watching = DB::select($query, array($user[0]->id));
        
        return view('watchlist', [
            'products' => $watching,
        ]);        
    }


    private function getToken(){
        $client = new Client(['base_uri' => 'https://api.ebay.com']);

        $authorization = 'Basic ' . base64_encode(env('EBAY_APP_ID') . ':' . env('EBAY_CERT_ID')); 
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded', 'Authorization' => $authorization];
        $body = 'grant_type=client_credentials&redirect_uri=' . env('EBAY_RU_NAME') . '&scope=https://api.ebay.com/oauth/api_scope';

        $request = new GuzzleRequest('POST', '/identity/v1/oauth2/token', $headers, $body);
        $response = $client->send($request, ['timeout' => 2]);
        $reponseBody = json_decode($response->getBody(), true);

        $token = $reponseBody['access_token'];
        return $token;
    }

    private function searchItem($token, $keyword){
        $client = new Client(['base_uri' => 'https://api.ebay.com']);
    
        $authorization = 'Bearer ' . $token;
        $headers = ['Authorization' => $authorization];

        $request = new GuzzleRequest('GET', '/buy/browse/v1/item_summary/search?q=' . $keyword . '&limit=12', $headers);
        $response = $client->send($request, ['timeout' => 5]);
        $reponseBody = json_decode($response->getBody());

        return $reponseBody;
    }

    private function getItem($token, $id){
        $client = new Client(['base_uri' => 'https://api.ebay.com']);

        $authorization = 'Bearer ' . $token;
        $headers = ['Authorization' => $authorization];
       
        $request = new GuzzleRequest('GET', '/buy/browse/v1/item/' . $id , $headers);
        $response = $client->send($request, ['timeout' => 5]);
        $reponseBody = json_decode($response->getBody()); 

        return $reponseBody;
    }
}
