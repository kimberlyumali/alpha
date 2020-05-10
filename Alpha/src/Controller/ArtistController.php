<?php
namespace App\Controller;

use Cake\Http\Client;
use Cake\Controller\Component\RequestHandlerComponent;
use Cake\Event\Event;

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
    * @param: $artistId = 49tQo2QULno7gxHutgccqF (LANY)
    */
    public function Albums($artistId)
    {

      $http = new Client();
      $curl = curl_init();

      $clientId = '242b0d27e0a44e759edd945b7fe1085d';
      $clientSecret = 'ae999b667e7a4b12b7481b937c00e6d6';

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
        CURLOPT_HTTPHEADER => ["Authorization: Basic " . base64_encode($clientId.':'.$clientSecret)],
      ]);

      $responseToken = curl_exec($curl);
      curl_close($curl);

      $http = new Client(['headers' => ['Authorization' => 'Bearer ' . json_decode($responseToken)->access_token]]);

      // Spotify Endpoint
      $playlist_url = 'https://api.spotify.com/v1/artists/'. $artistId .'/albums';

      $response = $http->get($playlist_url);
      $data = $response->getJson();


      $datum = [];
      foreach($data['items'] as $item) {

        array_push($datum, $item['name']);

      } 
    
      $this->set([
        'code'=> 200,
        'message' => 'Success',
        'data' => $datum,
        '_serialize' => ['code','message','data']
      ]);

    }
}