<?php
namespace App\Controller;

use Cake\Http\Client;
use Cake\Controller\Component\RequestHandlerComponent;
use Cake\Event\Event;
use Cake\Core\Configure; 
use Cake\Http\ServerRequest;

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
    public function Albums($artistName)
    {

      $http = new Client();
      $curl = curl_init();

      $code = '';
      $message = '';

      curl_setopt_array($curl, [
        CURLOPT_URL => Configure::read('API_ACCESS_TOKEN'), // Spotify Access Token
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_HTTPHEADER => ["Authorization: Basic " . base64_encode(Configure::read('CLIENT_ID').':'.Configure::read('CLIENT_SECRET'))],
      ]);

      $responseToken = curl_exec($curl);
      curl_close($curl);

      $http = new Client(['headers' => ['Authorization' => 'Bearer ' . json_decode($responseToken)->access_token]]);

      /*
      * Spotify Search Artist endpoint
      * Return : Artist's Albums
      */
      $getSearchedArtistId = Configure::read('API_SEARCH_ARTIST') . $artistName . '&type=album';
      $responseSearchedArtistId = $http->get($getSearchedArtistId);
      $dataSearchedArtist = $responseSearchedArtistId->getJSON();

      $album = [];
      $sortedReleaseDate = [];

      if(isset($dataSearchedArtist['albums']['items'])) {
        foreach($dataSearchedArtist['albums']['items'] as $item) {
          $code = 200;
          $message = 'Ok';
            array_push($album, $item['release_date'] .":". $item['name']);
        } 
      } else {
          $code = 500;
          $message = 'Invalid Artist or empty album';
      }

      rsort($album);
      foreach($album as $dataToSort) {
        $albumName = explode(":",$dataToSort);
        array_push($sortedReleaseDate, $albumName[1]);
      }


      /*
      * API Response
      */
      $this->set([
        'code'=> $code,
        'message' => $message,
        'artist_name' => $artistName,
        'data' => $sortedReleaseDate,
        '_serialize' => ['code','message','artist_name','data']
      ]);

    }
}