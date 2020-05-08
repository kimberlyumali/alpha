<?php
namespace App\Controller;

use Cake\Http\Client;

class ArtistController extends AppController
{
    public function initialize()
    {
      
        parent::initialize();
        $this->loadComponent('RequestHandler');


    }

    /*
    * Stage 1 Artist Query
    * 
    * STILL IN PROGRESS
    * 
    * @params: $artistId = 49tQo2QULno7gxHutgccqF (LANY)
    */
    public function Albums($artistId)
    {

      $http = new Client();

      // $getToken = $http->post('https://accounts.spotify.com/api/token', 
      //     ['grant_type' => 'client_credentials'], [  
      //     'headers' => [
      //           'Authorization' => 'Basic MjQyYjBkMjdlMGE0NGU3NTllZGQ5NDViN2ZlMTA4NWQ6YWU5OTliNjY3ZTdhNGIxMmI3NDgxYjkzN2MwMGU2ZDY=',
      //           'Content_Type' => 'application/x-www-form-urlencoded',
      //     ]
      // ]);

      // debug($getToken);
      // die;

      $curl = curl_init();

      curl_setopt_array($curl, [
        CURLOPT_URL => "https://accounts.spotify.com/api/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_HTTPHEADER => [
          "Authorization: Basic MjQyYjBkMjdlMGE0NGU3NTllZGQ5NDViN2ZlMTA4NWQ6YWU5OTliNjY3ZTdhNGIxMmI3NDgxYjkzN2MwMGU2ZDY=",
          "Content-Type: application/x-www-form-urlencoded",
        ],
      ]);

      $responseToken = curl_exec($curl);
      curl_close($curl);

      $http = new Client(['headers' => ['Authorization' => 'Bearer ' . json_decode($responseToken)->access_token]]);

      // Spotify Endpoint
      $playlist_url = 'https://api.spotify.com/v1/artists/'. $artistId .'/albums';

      $data = [];
      $response = $http->get($playlist_url);
      $data = $response->getJson();

      foreach($data['items'] as $item) {
        echo $item['name'] . '<br>';
        // array_push($data,  $item['name']);
      }

      unset($data['items']);

      debug($item);
      die;
    }
}