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
        
        // $token = $request->session()->get('token', 'not found');
        // if($token == 'not found'){
        //     $token = $this->getToken();
        //     $request->session()->put('token', $token);
        // }

        $response = $this->searchItem('', $productName);

        if($response->total > 0){
            // Insert new record to 'products' table if has not been searched perviously
            $items = $response->itemSummaries;
            foreach ($items as $item) {
                $isProductExist = DB::select("SELECT last_known_price FROM products WHERE ebay_id = ?", array($item->itemId));
                if($isProductExist == null){

                    DB::insert("INSERT INTO products (ebay_id, name, image, last_known_price) VALUES (?, ?, ?, ?)", array($item->itemId, $item->title, $item->image->imageUrl, $item->price->value));   
                    $record = DB::select("SELECT id FROM products WHERE ebay_id = ?", array($item->itemId));

                    // TODO: Input correct timestamp 
                    DB::insert("INSERT INTO price_history (product_id, price) VALUES (?, ?)", array($record[0]->id, $item->price->value));   
                }else{
                    // Update the last_known_price if the product exists
                    DB::update("UPDATE products SET last_known_price = ? WHERE ebay_id = ?", array($item->price->value, $item->itemId));
                }   
            }
        }else{
            $items = null;
        }
       
        return view('search', [
            'products' => $items,
        ]);
    }

    // Search 'price_history' table for selected product record(s) using $productId 
    public function item($id){
        // return: product_id, price, timestamp (JOIN 'products' table to for name)
        $query = "SELECT price_history.product_id, price_history.price, price_history.timestamp 
                    FROM products JOIN price_history 
                    ON price_history.product_id = products.id
                    WHERE products.ebay_id = ?
                    ORDER BY price_history.timestamp DESC";

        // Product price history
        $records = DB::select($query, array($id));
        // Product latest information 
        $response = $this->getItem($id);

        // Check if $response->shortDescription property is empty
        $shortDescription = '';
        if(property_exists($response, 'shortDescription')){
            $shortDescription = $response->shortDescription;
        }

        $product = (object)['title'=>$response->title, 
                            'details'=>$shortDescription,
                            'price'=>$response->price->value,
                            'image'=>$response->image->imageUrl];

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



    // TODO: Fix scope issue
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

    // TODO: If above is fixed, this will also be fixed
    private function searchItem($token, $keyword){
        $client = new Client(['base_uri' => 'https://api.ebay.com']);
    
        $authorization = 'Bearer v^1.1#i^1#r^0#f^0#p^1#I^3#t^H4sIAAAAAAAAAOVXbWwURRju9gsrUBARDJJwLMWIuLuzu/e5cGeOK02P0PbkaqmcpNnbnW0X9nY3O3OUgx/UmhCjvzAaQkKA4EcgIgGBxIghTTQYMAS/QGNQEmMTQYkYAiYaP2b3jnKtBChUIfH+XOadd955n+d93pkd0Fdb9/jG5o2/TqTGVe7oA32VFMWPB3W1NfPrqypn1FSAMgdqR19DX3V/1Q8LkZwzbGkZRLZlIuhbmzNMJHnGKJ13TMmSkY4kU85BJGFFSsdblkoCCyTbsbClWAbtSzZG6YgW4EWZF0KaIAeyKiRW82rMditKK3xIA0GohYNBNRKJKGQeoTxMmgjLJo7SAuDDDBAYIdQOgMT7JT/PhsXwCtrXAR2kWyZxYQEd89KVvLVOWa43TlVGCDqYBKFjyXhTui2ebFzc2r6QK4sVK/GQxjLOo+GjhKVCX4ds5OGNt0Get5TOKwpEiOZixR2GB5XiV5O5jfQ9qkVV5AOqXwjxETEShKExobLJcnIyvnEerkVXGc1zlaCJdVy4GaOEjewqqODSqJWESDb63L+n8rKhazp0ovTiRfFn4qkUHWux1vWosCfOIMWRbegoTGpZI+MPqGGgilmVCYRASBRUtbRRMVqJ5hE7JSxT1V3SkK/VwosgyRqO5AaUcUOc2sw2J65hN6NyP+Eqh4K4wi1qsYp53GO6dYU5QoTPG968AkOrMXb0bB7DoQgjJzyKorRs27pKj5z0tFiSz1oUpXswtiWO6+3tZXtF1nK6OQEAnutsWZpWemBOpomv2+tFf/3mCxjdg6KQNib+Ei7YJJe1RKskAbObjhHtBUPBEu/D04qNtP7DUIaZG94RY9UhfID0BlBgRAwHFEXhx6JDYiWRcm4eMCsXmJzsrIbYNmQFMgrRWT4HHV2VxIAmiGENMmowojH+iKYx2YAaZHgNQgBhNqtEwv+nRrlVqacVy4Ypy9CVwpgIfszELjpqSnZwIQ0NgxhuVfXXBYlckP86PLfXRwXRjYFIENnWWVfbrGLlOEsmh5pr6vKyviPcOrkP76miEoBFpLpavMhYDy6L1iisA5GVd8gdzra553q7tRqapEuwYxkGdDr4O2Ji7E70u3SaXxeVYuiExq57Ddkoj8nb1LaM7yLq6n4qcx3kfICPBCPAH74ztSa8urYX/oNDa1SFbbYQhrdctlF8gHDDn0OxCu/H91OHQD+1n7yoAAfm8nPA7Nqqp6urJsxAOoasLmss0rtN8pXvQHY1LNiy7lTWUpmZ+3Z3lT3AdqwEDw89weqq+PFl7zEw89pMDT9p+kQ+DAQhRFjx+/kVYM612Wp+WvXUM6v+gC25qguvn7lw5ZMNXCIz8PbXYOKQE0XVVBBlVDQv+NbeErl0NtOrJ6bMb9i89dw6Tuk8nHjgWGvHmXF7D1ZsGdxjrcp+eTl+/juHvsw/Vz/YebjjcKLQenRl4sUDu79AGfGJRzfJ2658/Fa9nTm5dbO1/1gCv5s8MXXJzqBxWn1hUvx4z/K6Cd8fP3Fi7qntr31EzfylbiB2X/6bNw8+0hBE9M/TJp8P/pkZFz2wbUq7+cG+eZ2fH2pakGp+ef9jT743feeSi+xf087NeyjXN9ggNpyD9qYj7+Dug13r7z9wqRB9dmD5LG4DNSt18lTLnsHfjc5P699XP0sePfL8Wf2rV9dw0xed/vDUby+df7Cx8idqlzOwsyJT++PFyXtfWbBk/frqN2bvKpbvb1cj8ygaDwAA';
        $headers = ['Authorization' => $authorization];

        $request = new GuzzleRequest('GET', '/buy/browse/v1/item_summary/search?q=' . $keyword . '&limit=12', $headers);
        $response = $client->send($request, ['timeout' => 5]);
        $reponseBody = json_decode($response->getBody());

        return $reponseBody;
    }

    private function getItem($id){
        $client = new Client(['base_uri' => 'https://api.ebay.com']);

        $authorization = 'Bearer v^1.1#i^1#r^0#f^0#p^1#I^3#t^H4sIAAAAAAAAAOVXbWwURRju9gsrUBARDJJwLMWIuLuzu/e5cGeOK02P0PbkaqmcpNnbnW0X9nY3O3OUgx/UmhCjvzAaQkKA4EcgIgGBxIghTTQYMAS/QGNQEmMTQYkYAiYaP2b3jnKtBChUIfH+XOadd955n+d93pkd0Fdb9/jG5o2/TqTGVe7oA32VFMWPB3W1NfPrqypn1FSAMgdqR19DX3V/1Q8LkZwzbGkZRLZlIuhbmzNMJHnGKJ13TMmSkY4kU85BJGFFSsdblkoCCyTbsbClWAbtSzZG6YgW4EWZF0KaIAeyKiRW82rMditKK3xIA0GohYNBNRKJKGQeoTxMmgjLJo7SAuDDDBAYIdQOgMT7JT/PhsXwCtrXAR2kWyZxYQEd89KVvLVOWa43TlVGCDqYBKFjyXhTui2ebFzc2r6QK4sVK/GQxjLOo+GjhKVCX4ds5OGNt0Get5TOKwpEiOZixR2GB5XiV5O5jfQ9qkVV5AOqXwjxETEShKExobLJcnIyvnEerkVXGc1zlaCJdVy4GaOEjewqqODSqJWESDb63L+n8rKhazp0ovTiRfFn4qkUHWux1vWosCfOIMWRbegoTGpZI+MPqGGgilmVCYRASBRUtbRRMVqJ5hE7JSxT1V3SkK/VwosgyRqO5AaUcUOc2sw2J65hN6NyP+Eqh4K4wi1qsYp53GO6dYU5QoTPG968AkOrMXb0bB7DoQgjJzyKorRs27pKj5z0tFiSz1oUpXswtiWO6+3tZXtF1nK6OQEAnutsWZpWemBOpomv2+tFf/3mCxjdg6KQNib+Ei7YJJe1RKskAbObjhHtBUPBEu/D04qNtP7DUIaZG94RY9UhfID0BlBgRAwHFEXhx6JDYiWRcm4eMCsXmJzsrIbYNmQFMgrRWT4HHV2VxIAmiGENMmowojH+iKYx2YAaZHgNQgBhNqtEwv+nRrlVqacVy4Ypy9CVwpgIfszELjpqSnZwIQ0NgxhuVfXXBYlckP86PLfXRwXRjYFIENnWWVfbrGLlOEsmh5pr6vKyviPcOrkP76miEoBFpLpavMhYDy6L1iisA5GVd8gdzra553q7tRqapEuwYxkGdDr4O2Ji7E70u3SaXxeVYuiExq57Ddkoj8nb1LaM7yLq6n4qcx3kfICPBCPAH74ztSa8urYX/oNDa1SFbbYQhrdctlF8gHDDn0OxCu/H91OHQD+1n7yoAAfm8nPA7Nqqp6urJsxAOoasLmss0rtN8pXvQHY1LNiy7lTWUpmZ+3Z3lT3AdqwEDw89weqq+PFl7zEw89pMDT9p+kQ+DAQhRFjx+/kVYM612Wp+WvXUM6v+gC25qguvn7lw5ZMNXCIz8PbXYOKQE0XVVBBlVDQv+NbeErl0NtOrJ6bMb9i89dw6Tuk8nHjgWGvHmXF7D1ZsGdxjrcp+eTl+/juHvsw/Vz/YebjjcKLQenRl4sUDu79AGfGJRzfJ2658/Fa9nTm5dbO1/1gCv5s8MXXJzqBxWn1hUvx4z/K6Cd8fP3Fi7qntr31EzfylbiB2X/6bNw8+0hBE9M/TJp8P/pkZFz2wbUq7+cG+eZ2fH2pakGp+ef9jT743feeSi+xf087NeyjXN9ggNpyD9qYj7+Dug13r7z9wqRB9dmD5LG4DNSt18lTLnsHfjc5P699XP0sePfL8Wf2rV9dw0xed/vDUby+df7Cx8idqlzOwsyJT++PFyXtfWbBk/frqN2bvKpbvb1cj8ygaDwAA';
        $headers = ['Authorization' => $authorization];
       
        $request = new GuzzleRequest('GET', '/buy/browse/v1/item/' . $id , $headers);
        $response = $client->send($request, ['timeout' => 5]);
        $reponseBody = json_decode($response->getBody()); 

        return $reponseBody;
    }
}
